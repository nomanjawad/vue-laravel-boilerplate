# Template Feedback & Fixes

Findings from real projects built on this template, with root causes and fixes to
fold back into the base `webTemplate`.

---

## 1. Response cache serves HTML to Inertia requests → "iframe error" / dead navigation

**Severity:** High — breaks SPA navigation on any page whenever the response cache is on.

### Symptoms
- Clicking an internal link (logo, nav, footer) does nothing, or briefly flashes a
  broken overlay, and the page never changes.
- Console shows, on click:
  - `Access to script at '.../build/assets/app-*.js' from origin 'null' has been blocked by CORS policy`
  - `SecurityError: Failed to set the 'cookie' property on 'Document': The document is sandboxed and lacks the 'allow-same-origin' flag`
  - errors referencing `about:srcdoc`
- Appears "random" — only some links, only sometimes, mostly on production. It depends
  on which request type warmed the cache first, and only happens when
  `RESPONSE_CACHE_ENABLED=true` (which is the **default**).
- Easy to misdiagnose as a **browser extension** (Scribe, link-preview tools, etc.):
  their content scripts inject into the error iframe and log their own errors on top.
  The extension is a bystander, not the cause. Tell: the same extension does not break
  other projects — the difference is the response cache, not the extension.

### Root cause
The iframe is **Inertia's own error modal**. From `@inertiajs/core`:

```js
createElement("iframe")
setAttribute("sandbox", "allow-scripts")   // no allow-same-origin → origin is null
.srcdoc = page.outerHTML                    // the raw response it received
```

Inertia calls this when an XHR visit receives a response that is **not a valid Inertia
response** (i.e. an HTML document without the `X-Inertia` header).

Chain:
1. Inertia SPA visits are XHRs that expect JSON carrying an `X-Inertia: true` header.
2. Spatie `laravel-responsecache` `DefaultHasher` keys the cache on **host + URI +
   method only** — NOT the `X-Inertia` header. See
   `vendor/spatie/laravel-responsecache/src/Hasher/DefaultHasher.php::getHashFor()`.
3. So `/` requested as a full page (HTML) and `/` requested by an Inertia click share
   ONE cache entry. Whichever warms it first is served to both.
4. When the cached **HTML** is handed to an Inertia XHR, Inertia rejects it and calls
   `showHtmlModal()` → the sandboxed `srcdoc` iframe above.
5. Inside that null-origin sandbox the page's `<script type="module">` bundle is
   CORS-blocked and `document.cookie` throws — the console errors — and navigation dies.

### Fix
Split the cache key so Inertia and full-page requests never collide.

**New file — `app/Support/InertiaAwareCacheProfile.php`:**

```php
<?php

namespace App\Support;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;

/**
 * Keeps Inertia (XHR/JSON) and full-page (HTML) requests in SEPARATE cache
 * entries. Without this, a cached HTML document gets served to an Inertia XHR,
 * which Inertia renders in a sandboxed <iframe srcdoc> error modal (null origin
 * → CORS-blocked bundle → dead navigation).
 */
class InertiaAwareCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function useCacheNameSuffix(Request $request): string
    {
        $suffix = parent::useCacheNameSuffix($request);

        if ($request->headers->has('X-Inertia')) {
            $suffix .= '-inertia'
                .'-'.$request->header('X-Inertia-Version', '')
                .'-'.$request->header('X-Inertia-Partial-Component', '')
                .'-'.$request->header('X-Inertia-Partial-Data', '');
        }

        return $suffix;
    }
}
```

**`config/responsecache.php`:**

```php
// replace
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
// with
use App\Support\InertiaAwareCacheProfile;

// and
'cache_profile' => InertiaAwareCacheProfile::class,
```

Then: `php artisan config:clear && php artisan responsecache:clear`
(production: the `optimize:clear` in the deploy pipeline handles this).

### Verification
Full-page and Inertia requests to the same URL now hash to different cache keys:

```
full-page key : e3d98a2fbff7c7b7a5d9b0b47d15caa0
inertia key   : 6453d98854d94c06273545444762a914   ← different ✓
```

### Notes
- Also keep `<IfModule mod_headers.c> Header always set Vary "X-Inertia, Accept"` in
  `public/.htaccess` — that handles browser/proxy caching; the cache profile handles
  the server-side response cache. Both layers must distinguish Inertia requests.
- Disabling the cache (`RESPONSE_CACHE_ENABLED=false`) masks the bug but costs
  performance — the cache-key split is the correct fix.

---

## 2. `data/*.json` content is cached for 1 hour (edits don't show until cleared)

**Severity:** Low — dev friction, not a production bug.

`JsonDataService::get()` wraps each file in `Cache::remember($key, 3600, …)`. Editing a
`data/*.json` file will not appear until the cache entry expires or is cleared.

- **Dev:** run `php artisan cache:clear` after editing any `data/*.json`.
- **Prod:** the deploy's `optimize:clear` already flushes it, so it's automatic there.
- Optional DX improvement: skip the cache when `config('app.debug')` is true, or add a
  file-mtime check to the cache key so edits invalidate automatically in local dev.

---

## 3. Global CSS bundle — unscoped selectors leak across pages

**Severity:** Medium — causes hard-to-trace layout bugs.

All per-page CSS (`resources/css/pages/*.css`) is compiled into **one global bundle**, so
a bare selector in one page's stylesheet applies on **every** page. Real cases hit:

- `home.css` `.hero-ctas { justify-content: center }` centered the hero buttons on the
  service pages (should be left-aligned).
- `services.css` `.hero-right .btn-primary { margin-top: 28px }` pushed the primary
  button out of vertical alignment with its sibling link on the service hero.

**Fix / rule of thumb:** scope page-specific rules to a page root class (e.g.
`.page-hero .hero-ctas { … }`), and when overriding a leaked rule, out-specify it on the
scoped selector. Prefer page-scoped selectors over bare element/utility class names in
any `pages/*.css` file.

---

## 4. Production `.env` go-live checklist (things caught set wrong on a live deploy)

- `SEO_INDEXABLE=true` in production — defaults/false leaves the site `noindex, nofollow`
  + blocking `robots.txt`, invisible to Google.
- Configure real SMTP (`MAIL_*`) and a real `MAIL_FROM_ADDRESS` before enabling the
  contact form / newsletter, or submissions silently fail.
- `APP_URL` with no trailing slash and matching the canonical host (see below).
- `LOG_LEVEL=warning` (or `error`) in production, not `debug`.
- Canonical host: add HTTPS + www→apex (or apex→www) 301 redirects in `public/.htaccess`,
  and make sure the SEO canonical/OG tags use the same host, e.g.:

  ```apache
  # force HTTPS + non-www → https://example.com
  RewriteCond %{HTTP_HOST} ^www\. [NC,OR]
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://example.com%{REQUEST_URI} [R=301,L]
  ```
  (If a CDN/proxy terminates SSL, check `%{HTTP:X-Forwarded-Proto}` instead of `%{HTTPS}`
  to avoid a redirect loop.)

---

## 5. Literal `'WebTemplate'` fallback leaks into the browser tab title on client sites

**Severity:** Medium — cosmetic but visible on every page, in front of visitors and in
search-result previews (see screenshot case below).

### Symptoms
- Browser tab shows `<Page Title> - WebTemplate` instead of the client's actual brand,
  e.g. a real deployed site showed:
  `SkyFreight Squad | Operational Partner For 3PL & Freight Brokers - WebTemplate`
- Happens even though the client's own `.env` has `APP_NAME` set correctly — the site
  name is right everywhere *except* this one suffix.

### Root cause
Several places in the base template hardcode the literal string `'WebTemplate'` as a
**silent fallback**, rather than something that fails loudly or always resolves to the
real config:

- `resources/js/app.js` — Inertia's `title` callback:
  ```js
  title: (title) => title ? `${title} - ${import.meta.env.VITE_APP_NAME || 'WebTemplate'}` : ...
  ```
  This fallback fires whenever `import.meta.env.VITE_APP_NAME` is empty/undefined *at
  Vite build time* — e.g. a CI build step that doesn't load `.env` the same way local
  dev does, or a `.env` where `VITE_APP_NAME="${APP_NAME}"` didn't get resolved before
  the build ran. When that happens, every page title silently gets the wrong brand
  baked into the compiled JS bundle — nothing errors, nothing warns, it just ships wrong.
- `resources/views/app.blade.php` — `<title inertia>{{ config('app.name', 'WebTemplate') }}</title>`
- `app/Http/Middleware/HandleInertiaRequests.php` — SEO `resolveSeo()`:
  `config('app.name', 'WebTemplate')` (feeds `<title>`, OG, and Twitter meta tags)
- `resources/js/Components/Shared/BrandLogo.vue` — `page.props.settings?.site_name || 'WebTemplate'`
- `resources/js/Layouts/AuthLayout.vue` — not even a fallback, just a hardcoded literal
  `WebTemplate` string in the login/register/forgot-password/reset-password header link.

### Fix
On the affected client project, replaced every hardcoded `'WebTemplate'` fallback/literal
above with the client's real site name (or, for `AuthLayout.vue`, made it reactive off
`page.props.settings.site_name` like `PublicLayout.vue` already does, instead of a flat
string).

### Recommendation for the base template
- `resources/js/Layouts/AuthLayout.vue` should never hardcode a brand name at all — pull
  from `page.props.settings.site_name` (or an app-name shared prop) like the public
  layout does, so `template:init` doesn't need a manual follow-up fix here.
- For `app.js`'s title callback and any other place keyed off `import.meta.env.VITE_APP_NAME`:
  the fallback string should not be a fake brand name. Prefer failing the build (or at
  least logging a build-time warning) when `VITE_APP_NAME` is unset, since a silently
  wrong title in production is worse than a build that stops and tells you why.
- Grep new client repos for `WebTemplate` as a pre-launch check (`TemplateInit.php`'s own
  prompt defaults are fine to leave — those are dev-time-only and never reach a real page).

---

## 6. Deploy pipeline: `template:doctor` ordering deadlock, and seeders that aren't safe to repeat

**Severity:** High — a fresh production deploy can never fully complete, and naively
"fixing" that by seeding on every deploy risks silently reverting live admin edits.

### Symptoms
- First deploy to a brand-new server: `php artisan template:doctor --production` reports
  `✗ migrations table missing` and `✗ public/storage symlink missing`, exits 1, and (via
  `set -e` + `&&` chaining in the SSH command) aborts the *entire* post-deploy step before
  `migrate`/`storage:link`/cache-rebuild ever run.
- After fixing the ordering and re-running: `db:seed --class=AdminUserSeeder --force` then
  fails with `There is no role named 'super-admin' for guard 'web'.`

### Root cause (part 1 — doctor ordering)
`TemplateDoctor::checkDatabase()` and `::checkStorage()` check
`Schema::hasTable('migrations')` and `is_link(public_path('storage'))` — both of which are
only true *after* `migrate`/`storage:link` have run. The deploy pipeline called
`template:doctor --production` **before** those steps, so on any server that hasn't
already completed a full deploy once, doctor always fails and aborts everything after it.
Chicken-and-egg: migrate never gets to run because doctor (checking for migrate's own
output) fails first.

**Fix:** move `template:doctor` to run *last*, after migrate/seed/storage:link/cache, and
never let it gate the deploy: `(php artisan template:doctor --production || true)`. It's a
post-deploy health report now, not a precondition.

### Root cause (part 2 — seeders assumed idempotent-safe, some aren't)
`AdminUserSeeder::syncRoles(['super-admin'])` requires the `super-admin` `Role` row to
already exist, which only `RoleAndPermissionSeeder` creates. The deploy step only ran
`--class=AdminUserSeeder`, never the seeder that creates the role it depends on.

The obvious fix — just run the full `DatabaseSeeder` chain on every deploy so this class of
dependency gap can't happen — is a trap. Checking each default seeder:

| Seeder | Method | Safe to re-run forever? |
|---|---|---|
| `RoleAndPermissionSeeder` | `Role::firstOrCreate` + `PermissionSyncer::sync()` | ✅ yes (own doc comment confirms) |
| `AdminUserSeeder` | `User::firstOrCreate` + `syncRoles()` | ✅ yes — won't touch an existing user's password |
| `MenuSeeder` | `Menu::firstOrCreate` | ✅ yes |
| `PageMetaSeeder` | `PageMeta::firstOrCreate` | ✅ yes |
| `ModulesSeeder` | `Module::updateOrCreate` | ❌ **no** — resets `enabled` on every run |
| `SettingSeeder` | `Setting::updateOrCreate` | ❌ **no** — resets every setting value on every run |

`updateOrCreate` unconditionally overwrites the row's value columns. If `ModulesSeeder` or
`SettingSeeder` were in a recurring deploy step, the next deploy after a client toggles a
module in `/admin/modules` or edits `site_name`/`address`/etc. in `/admin/settings` would
silently revert that edit back to the hardcoded seed default — no error, no log, just wrong
data after the next release.

**Fix:** only `RoleAndPermissionSeeder` and `AdminUserSeeder` belong in the automated,
repeating deploy step. `ModulesSeeder`/`MenuSeeder`/`SettingSeeder`/`PageMetaSeeder` are
one-time bootstrap content — run once by hand (`php artisan db:seed --force` on the very
first deploy, when nothing exists yet to clobber), never wired into CI.

### Recommendation for the base template
- Convert `ModulesSeeder` and `SettingSeeder` to `firstOrCreate` (matching the other four),
  so the *entire* `DatabaseSeeder` chain becomes genuinely safe to leave in a deploy
  pipeline permanently — this removes the whole footgun category rather than just working
  around it per-project.
- If `updateOrCreate` semantics are wanted intentionally for a "reset to defaults" admin
  action, expose that as an explicit opt-in command/button, never as a passive side effect
  of deploying.
- Document, in the deploy template itself, that first-time production setup needs one
  manual `php artisan db:seed --force`, distinct from what the automated pipeline seeds on
  every release.

---

## 7. `admin-dark` theme: incomplete utility remap + brand-color collision → invisible/low-contrast admin content

**Severity:** Medium — admin tables and links silently render dark-on-dark; only shows up
once a project re-themes the brand color or uses a gray shade the remap missed.

### Context
`resources/css/admin.css` re-themes standard light utility classes under a `.admin-dark`
root (`AdminLayout.vue`), so admin pages written with `bg-white` / `text-gray-900` render
correctly on the dark shell without rewriting. Good idea — but it's a **hand-maintained
allow-list**, and it breaks in two ways.

### Bug A — the remap has holes
Only specific gray shades are remapped:

```css
.admin-dark .text-gray-900 { color: #f8fafc; }
.admin-dark .text-gray-700 { color: #cbd5e1; }
.admin-dark .text-gray-600 { color: #94a3b8; }
```

`text-gray-800` (and `-500`, `-400`, `-300`) are **not** in the list. Any admin markup
using `text-gray-800` — a common, natural choice — keeps Tailwind's real near-black value
and renders dark-on-dark, effectively invisible. The Leads module tables shipped with
exactly this and were unreadable in the non-hover state (hover worked only because
`hover:bg-gray-50/100` *are* remapped to light-on-dark, coincidentally lifting contrast).

### Bug B — remapping the brand color kills links
Admin links/buttons use the brand/`indigo` token. When a project retunes that token to a
dark navy (`hsl(220 55% …)`) to match its site palette, every `text-indigo-*` link on the
dark admin shell becomes dark-on-dark. The template gives no signal that the brand token
has a hard **minimum-lightness** requirement for use as text/links on the dark admin shell.

### Fix applied on the project
- Cells moved to only the remapped grays (`text-gray-900/700/600`), never `-800`.
- Brand/indigo token retuned to a **mid** lightness that stays legible on the dark shell
  while still giving white-text buttons enough contrast; links set to `text-indigo-400`.

### Recommendation for the base template
- Make the remap exhaustive: cover the full gray scale (`text-gray-300…900`,
  `bg-gray-*`, `border-gray-*`) so no natural class choice can fall through to a
  light-mode value on the dark shell.
- Better still, define admin surfaces/text against **semantic tokens** (`--admin-fg`,
  `--admin-fg-muted`, `--admin-surface`) instead of intercepting Tailwind's literal
  `gray-*` utilities — then a project can't accidentally use an un-remapped shade.
- Document that the brand token, when overridden, must clear a minimum contrast ratio
  against the admin shell background (it's used for links/buttons there, not just on the
  light public site).

---

## 8. Settings pattern leaks secret values to the browser (`Setting::all()` → Inertia props)

**Severity:** High — a stored SMTP/API password is serialized into the admin page payload
and is visible in view-source / the Inertia JSON, to anyone who can open the settings page
or intercept the response.

### Root cause
The admin Settings screen renders every row straight from `Setting::all()` into an Inertia
prop so the form can be pre-filled. The base template has **no notion of a secret setting**
— once a project adds a password-type setting (SMTP password, payment gateway secret,
webhook signing key), its cleartext value ships to the browser on every settings page load,
and the update path will happily overwrite it with a blank if the field is submitted empty.

### Fix applied on the project
Added a `type === 'password'` convention and taught the controller both directions:

```php
// index(): never ship secret values to the browser
$settings = Setting::all()->map(function (Setting $s) {
    if ($s->type === 'password') $s->value = '';
    return $s;
})->groupBy('group');

// update(): a blank password field means "keep the existing one", don't wipe it
if (array_key_exists('mail_password', $settings) && $settings['mail_password'] === '') {
    unset($settings['mail_password']);
}
```

Front end blanks password fields in the form model and renders them as `type=password`
inputs with a "leave blank to keep current" hint.

### Recommendation for the base template
- Bake the `password` (secret) setting type into the base `Setting` model + settings
  controller: redact on read, keep-on-blank on write — so any project adding a secret
  setting is safe by default rather than having to remember this.
- Consider encrypting secret settings at rest (cast/`encrypted`) too, so a DB dump doesn't
  expose gateway/SMTP credentials in cleartext.

---

## 9. Vite build fails on static asset paths in `src="/…"` attributes (UNRESOLVED_IMPORT)

**Severity:** Medium — a `pnpm build` that works fine in dev suddenly hard-fails at deploy.

### Symptoms
`pnpm build` aborts with `UNRESOLVED_IMPORT` / "Could not resolve `/images/…` from …"
pointing at a Vue component, even though the same markup renders fine under `vite` dev.

### Root cause
A static, literal path in a bound-looking attribute — e.g.
`<img src="/images/logo.png">` inside a `.vue` file — is treated by Vite's asset pipeline
as an import to resolve at build time. Files that live in `public/` (served as-is, not part
of the bundle graph) have no module to resolve, so Rollup fails the build. Dev doesn't
resolve it the same way, so the failure only appears at build/deploy time.

### Fix
Bind public-dir paths as a plain string expression so Vite treats them as runtime URLs,
not imports:

```vue
<script setup>const logo = '/images/prestige-fund-logo.png'</script>
<template><img :src="logo"></template>
```

(Hit in `AuthLayout.vue` / `AdminLayout.vue` when swapping the placeholder brand for a
real logo.)

### Recommendation for the base template
- Use `:src="'/…'"` (bound string) for every `public/`-served asset in template components,
  and add a one-line comment where it's done so `template:init`-generated logo swaps don't
  reintroduce a static `src="/images/…"` that breaks CI.

---

## 10. Stale `public/hot` silently breaks assets after switching build ↔ dev

**Severity:** Low — pure DX, but very confusing when it hits.

### Symptoms
Every asset 404s (`app-*.js`, `app-*.css` not found) and the page renders unstyled /
non-interactive, even though `public/build/manifest.json` exists and is current.

### Root cause
The Vite dev server writes `public/hot`; while it exists, `@vite`/`@vite`-driven asset URLs
point at the dev server (`http://localhost:5173/…`) instead of the built manifest. If the
dev server is killed without cleanly removing `public/hot` (crash, force-quit, switching a
machine over to serving the production build), the stale file makes Laravel keep emitting
dev-server URLs that nothing is answering → 404s.

### Fix
`rm public/hot` (then `php artisan responsecache:clear` if the cached HTML captured the
dev URLs).

### Recommendation for the base template
- Add `public/hot` to the deploy rsync `--exclude` list (so a stray local `hot` can never
  ship) and mention it in the troubleshooting docs as the first thing to check for
  "assets 404 but build exists".

---

## 11. Core modules can't have their admin nav hidden without throwing

**Severity:** Low — friction when trimming the admin for a project that doesn't use a feature.

### Symptoms
Trying to disable a feature a project doesn't need (e.g. `page_metas`, `menus`,
`subscribers`) via the modules system throws `ModuleException: … is core and cannot be
disabled`, so there's no supported way to get it out of the admin sidebar.

### Root cause
Several always-on features are registered as **core** modules (can't be disabled), but their
admin sidebar entries come from the same module `nav` config. So "I don't want this in the
nav" is only expressible as "disable the module", which core modules forbid — an all-or-
nothing coupling.

### Workaround applied
Emptied the `nav` array for those modules in `config/modules.php` (keeps the module enabled
and functional, just removes its sidebar entries).

### Recommendation for the base template
- Separate "module enabled" from "module shown in nav" — e.g. a per-module
  `nav_visible` flag (or let the admin toggle nav visibility) so a core module can stay
  active but be hidden from the sidebar without hand-editing config and without tripping the
  core-module guard.
