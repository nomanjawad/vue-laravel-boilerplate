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

---

## 12. Public-site global CSS (`global.css`) leaks into admin and beats Tailwind utilities

**Severity:** Medium — admin UI looks broken (dark text on dark buttons, unwanted header
chrome) even though the Vue markup uses correct utility classes.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Admin primary actions (`bg-gray-900 text-white` on an Inertia `<Link>`) render with
  **dark gray text on a dark button** — e.g. "Add User" is nearly unreadable.
- Admin top bar / page headers pick up a light-gray background and bottom border that belong
  to the public site header styles.
- `text-white` is present in the class list but has no visible effect on `<a>` elements.

### Root cause
Two compounding issues:

1. **`global.css` is imported after Tailwind in `app.css`**, so its unlayered rules win over
   layered Tailwind utilities in the cascade.
2. **Bare element selectors** in `global.css` apply site-wide, including inside
   `.admin-dark`:
   - `a { color: inherit; }` — every admin link inherits `--admin-fg-muted` from the shell
     instead of respecting `text-white` on button-style links.
   - `header { background; border-bottom; … }` — styles the admin `<header>` rows the
     same as the public sticky nav.

### Fix applied on the project
- Scope public link reset to `.site-shell a { color: inherit; … }`.
- Scope public header chrome to `.site-shell > header { … }`.
- Add admin.css safety net for white labels on dark/brand button links:
  `.admin-dark a.bg-gray-900`, `.admin-dark .text-white`, etc.

### Recommendation for the base template
- Treat `global.css` as **public-site-only** CSS. Either wrap the whole file under
  `.site-shell`, or split into `public.css` (imported only on public layouts) vs admin.
- Never use bare `a`, `header`, `button`, etc. in a file that loads on admin pages unless
  scoped under `.admin-dark` or a public root class.
- Document this in the page-content / dev-workflow skills — it's easy to reintroduce when
  porting reference HTML.

---

## 13. `contact_submissions` schema upgrade trap when the table already exists

**Severity:** High — contact form 500s in production with "Unknown column 'organization'".

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- POST `/contact` throws `SQLSTATE[42S22]: Column not found: 1054 Unknown column
  'organization'`.
- `php artisan migrate:status` shows the `create_contact_submissions_table` migration as
  **Pending**, even though the table exists and already holds rows.

### Root cause
The boilerplate shipped an older `contact_submissions` schema (`name`, `email`, `phone`,
`subject`, `message`, `is_read`). A later site-specific form added `organization`,
`org_type`, `consent`, `ip`, `read_at` in the model/controller, and a new `Schema::create`
migration — but **`Schema::create` is a no-op when the table already exists**, so the
migration marks "Ran" only after manual intervention and the column mismatch persists until
then.

### Fix applied on the project
Rewrote the migration to branch:
- If table missing → `Schema::create` with the full new schema.
- If table exists → `Schema::hasColumn` checks, add missing columns, migrate `subject →
  organization` and `is_read → read_at`, drop obsolete columns.

### Recommendation for the base template
- Ship the **final** contact-form schema in the base migration from day one (`organization`,
  `org_type`, `consent`, `ip`, `read_at` — or whatever the public form validates).
- For any future column change on a table that may already exist in the wild, use an
  **alter migration** with `hasColumn` guards, never a second `create` migration.
- Add a smoke test note in the contact-form skill: after deploy, submit the form once and
  confirm a row appears in admin → Form Submissions.

---

## 14. Site settings aren't the default source of truth for public contact/branding

**Severity:** Medium — contact info, logos, and social links get duplicated across
`site_settings`, `data/header.json`, `data/footer.json`, and hardcoded Vue fallbacks; edits
in one place don't propagate.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Admin updates logo in Site Settings but header still shows the JSON/hardcoded path.
- Contact phone/email appear in footer JSON, contact page JSON, CTAs, and settings — four
  places to update for one phone-number change.
- Legal pages need the real address in prose but settings already hold it.

### Fix applied on the project
- **`PUBLIC_SETTINGS` whitelist** in `HandleInertiaRequests` — only safe keys reach the
  browser (`contact_email`, `contact_phone`, `address`, logos, social URLs, etc.).
- **`useSiteSettings` composable** — single frontend accessor for header/footer logos,
  favicon, contact, social.
- **`SettingShortcodeService`** — resolves `{{contact_email}}`, `{{contact_phone}}`,
  `{{address}}` in JSON content, SEO, layout props, custom code, and service `content` JSON
  via `JsonDataService::getForPublic()`.
- Removed duplicate logo/contact fields from the Page Content layout editor; settings + shortcodes
  are the source of truth.

### Recommendation for the base template
- Ship `useSiteSettings` (or equivalent) and the shortcode resolver in core — every client
  site needs this pattern.
- Document shortcodes in the Settings admin UI (Contact tab) so content editors know how to
  reference live contact data inside JSON.
- Page JSON files should use shortcodes for anything that also lives in settings, not
  parallel editable fields.
- `SettingService::getImageSetting()` / root-relative media URLs should be the default path
  for logos/favicons (works on any host/port + CSP `img-src 'self'`).

---

## 15. Tailwind v4 `@source` must include physical-module Vue paths

**Severity:** Medium — module page utilities silently never generate; layout looks unstyled
with no build error.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- `/services` and `/services/{slug}` render with missing spacing, colors, or responsive
  rules that are clearly present in the Vue source.
- `pnpm build` succeeds — the classes simply aren't in the CSS output.

### Root cause
Physical modules live under `app/Modules/*/Resources/js/**/*.vue`, outside
`resources/`. Tailwind v4 only scans `@source` globs; the default `@source '../**/*.vue'`
doesn't reach module folders.

### Fix
In `resources/css/app.css`:

```css
@source '../../app/Modules/*/Resources/js/**/*.vue';
```

### Recommendation for the base template
- Include this `@source` line in the base `app.css` from day one (any project using
  `make:module` will hit this).
- Mention in `create-module` skill and in a build-troubleshooting note: "module page looks
  unstyled but build is clean → check `@source`".

---

## 16. Tailwind v4: don't mix standard breakpoints with arbitrary px variants on the same property

**Severity:** Low — responsive layout silently wrong at desktop widths.

**Project:** SkyHealth Pro (2026-07-30)

### Root cause
Tailwind v4 emits px-based arbitrary variants (e.g. `min-[981px]:flex-row`) **before**
rem-based standard breakpoints (`sm:`, `lg:`) in the CSS output. When both target the same
property, `sm:`/`lg:` wins at overlapping widths — opposite of author intent when porting
reference HTML that used custom px breakpoints.

### Fix / rule
Pick one system per property: either standard breakpoints (`lg:`) or arbitrary px, not both
on the same rule. When porting `_reference/*.html`, convert custom px breakpoints to the
nearest standard `lg:` / `xl:` rather than leaving `min-[981px]:` alongside `sm:`.

---

## 17. Admin settings tabs not gated by module enable state

**Severity:** Low — confusing admin UX after disabling a module.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Shop module disabled in `/admin/modules`, but **Shop** tab still visible under Site
  Settings with fields that no longer apply.

### Fix applied
Removed the Shop tab from `Admin/Settings/Index.vue` when the shop module is off (or
unconditionally for this project).

### Recommendation for the base template
- Gate each settings tab (or tab section) on `modules.enabledFeatures` / module registry —
  same pattern as permission-gated sidebar nav.
- When a module is disabled, hide its settings tab **and** any public routes/menus that
  reference it (shop location, cart count shared prop, etc.).

---

## 18. Replacing a core module leaves a ghost module row (page_metas → page_content)

**Severity:** Low — duplicate sidebar entries, dead permissions, confusion for admins.

**Project:** SkyHealth Pro (2026-07-30)

### Context
Retired the `page_metas` virtual module in favour of SEO blocks inside `data/*.json` edited
via a new `page_content` module. The old module's DB row, permissions, and table were left
in place (non-destructive removal).

### Symptoms
- `page_metas` still shows as **enabled** in the modules table even though nothing uses it.
- Two overlapping concepts ("Page Metas" vs "Pages") in docs and permissions.

### Recommendation for the base template
- When replacing module A with module B, ship a one-time migration/seeder step that
  `Module::where('key', 'page_metas')->update(['enabled' => false, 'nav_visible' => false])`
  (or equivalent) and document the retirement in `modules-reference`.
- Provide a "module replacement" checklist: disable old module, remove nav, migrate data,
  drop or archive old admin routes, sync permissions.

---

## 19. Deploy rsync should exclude agent/dev-only paths

**Severity:** Low — ships dev tooling and reference HTML to production unnecessarily.

**Project:** SkyHealth Pro (2026-07-30)

### Paths to exclude
Add to `.github/workflows/deploy.yml` rsync `--exclude` list:

```
/agents/
/.claude/
/AGENTS.md
/CLAUDE.md
/feedback.md
/_referance/
/.cursor/
/.codex/
```

Also already worth excluding (some projects miss these): `/public/hot`, `/node_modules/`,
`/resources/js/`, `/resources/css/` (when shipping pre-built assets only).

### Recommendation for the base template
- Bake these excludes into the default deploy workflow template.
- Keep agent/skills folders in git for development but never on the production server.

---

## 20. `template:doctor` false-flags `.env` keys when DB uses socket or non-standard layout

**Severity:** Low — noisy post-deploy report; real config works fine.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Doctor reports `✗ APP_KEY not set`, `✗ DB_DATABASE not set`, `✗ DB_USERNAME not set`
  even though the app connects to MySQL and runs migrations (e.g. MAMP `DB_SOCKET=…` with
  empty-looking standard vars, or values set outside the patterns doctor regex-checks).

### Recommendation for the base template
- Doctor should validate **runtime config** (`config('app.key')`, `DB::connection()->getPdo()`)
  rather than parsing `.env` file contents literally.
- Treat "database reachable" as the gate; individual var presence is advisory only when PDO
  succeeds.

---

## 21. Page Content admin: Inertia `useForm` pitfalls with recursive JSON

**Severity:** Low — TypeScript/build friction when adding the JSON page editor.

**Project:** SkyHealth Pro (2026-07-30)

### Gotchas hit
1. **`useForm()` reserves the key `data`** — it's a method on the form instance. Name the
   JSON payload field `content` (or anything except `data`).
2. **Recursive `JsonValue` generic on `useForm<JsonObject>`** blows up `vue-tsc` with
   "Type instantiation is excessively deep". Keep the form generic as
   `Record<string, any>` and cast at the point of use in helper functions.
3. **One `useForm` per JSON file, created up front** — switching tabs in the Page Content
   panel must not recreate forms or unsaved edits are lost when changing home → about → contact.

### Recommendation for the base template
- Document these three rules in the `page-content` skill before anyone copies the
  `JsonContentEditor` / `PageContentController` pattern.
- Consider a shared `useJsonPageForm(fileKey, initial)` composable that encodes the safe
  defaults so client projects don't rediscover the `data` key collision.

---

## 22. `JsonDataService` dev cache — expand on §2

**Severity:** Low — editing `data/*.json` locally doesn't show until cache clear (up to 1 h).

**Project:** SkyHealth Pro (2026-07-30)

### Better fix than "run cache:clear manually"
- Skip `Cache::remember` entirely when `config('app.debug')` is true, **or**
- Include the file's `mtime` in the cache key:
  `json_data_home_{filemtime}` — edits invalidate automatically in dev and prod without
  waiting for TTL or deploy.

### Recommendation for the base template
- Implement mtime-keyed cache (or debug bypass) in the base `JsonDataService` so the Page
  Content panel feels live while editing locally.

---

## 23. 404 page has no Inertia shared props — document the constraint

**Severity:** Low — easy to wire shared settings into Error404 and get runtime undefined errors.

**Project:** SkyHealth Pro (2026-07-30)

### Context
Unmatched routes render `Pages/Public/Error404.vue` via the `bootstrap/app.php` respond hook,
**outside** the normal Inertia middleware stack — so `settings`, `layout`, `menus`, etc. are not
automatically available.

### Fix applied
Error404 accepts explicit props from the respond hook (site name, contact email, logo paths)
or uses safe inline fallbacks — don't assume `usePage().props.settings` exists.

### Recommendation for the base template
- Document in `page-content` / dev-workflow skills: 404 is a special case; list which props
  the respond hook must pass if the 404 page uses branding/contact from settings.
- Consider a minimal shared-props helper the respond hook can call so client projects don't
  duplicate `HandleInertiaRequests` logic by hand.

---

## 24. CI deploy must export `VITE_APP_NAME` before `pnpm build` (extends §5)

**Severity:** High — site loads blank / white screen; console throws on every page.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- After a successful GitHub Actions deploy, the public site shows a blank page.
- Browser console:
  ```
  Uncaught Error: VITE_APP_NAME is not set. Add VITE_APP_NAME="${APP_NAME}" to .env before building…
  ```
- Server `.env` has `APP_NAME` set correctly — Laravel-side config is fine; only the
  **compiled JS bundle** is broken.

### Root cause
`VITE_*` variables are **inlined at Vite build time**, not read from the server `.env` at
runtime. The deploy workflow runs `pnpm build` on the CI runner without a `.env` file, so
`import.meta.env.VITE_APP_NAME` bakes in as an empty string.

`resources/js/app.ts` throws when the name is empty (replacing the silent `'WebTemplate'`
fallback from §5). The Vite build step can still succeed — the throw runs in the **browser**
when the bundle loads. Setting `APP_NAME` on the production server does not fix an
already-shipped bundle; you must **rebuild** with `VITE_APP_NAME` set.

### Fix applied on the project
1. **`deploy.yml`** — export before `pnpm build`:
   ```yaml
   env:
     VITE_APP_NAME: ${{ vars.VITE_APP_NAME || 'SkyHealth Pro' }}
   ```
2. **`vite.config.ts`** — fail the production build if `VITE_APP_NAME` is unset.
3. Production `.env` should still include `VITE_APP_NAME="${APP_NAME}"` for manual rebuilds.

### Recommendation for the base template
- Default deploy workflow must set `VITE_APP_NAME` on the **Build frontend assets** step.
- Keep the runtime throw in `app.ts` **and** add the `vite.config.ts` production guard.
- README go-live checklist: both `APP_NAME` and `VITE_APP_NAME` required; only the latter
  affects the frontend bundle.

---

## 25. Stale `bootstrap/cache` on server references removed packages (Ziggy)

**Severity:** High — post-deploy `php artisan migrate` fails before cache clear runs.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
```
Class "Tighten\Ziggy\ZiggyServiceProvider" not found
```

### Root cause
Deploy rsync excludes `/bootstrap/cache/`, so stale `packages.php` from an older install
(Ziggy) persists. Laravel boots with the old provider list before `optimize:clear` runs.

### Fix applied on the project
Before any post-deploy artisan command:
```bash
find bootstrap/cache -mindepth 1 -name '*.php' -delete
```

### Recommendation for the base template
- Bake bootstrap-cache wipe into the default deploy workflow's first post-deploy step.

---

## 26. `pnpm-lock.yaml` out of sync when a parent pnpm workspace intercepts install

**Severity:** Medium — CI fails with `ERR_PNPM_OUTDATED_LOCKFILE`.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
Lockfile missing entries (e.g. `@fontsource/inter`, `@fontsource/poppins`) even after local
`pnpm install`.

### Root cause
Project sits inside a parent pnpm workspace; plain `pnpm install` updates the parent
lockfile, not the repo's `pnpm-lock.yaml`.

### Fix
```bash
pnpm install --ignore-workspace
```
Commit the updated lockfile.

### Recommendation for the base template
- Document `pnpm install --ignore-workspace` in dev-workflow when a parent workspace exists.

---

## 27. Deploy rsync: `data/` and media — include on first launch, exclude once admin is live

**Severity:** Medium — wrong default either wipes live admin edits or never ships seed content.

**Project:** SkyHealth Pro (2026-07-30)

### Context
Two content stores leave the repo on deploy:

| Path | What it holds |
|---|---|
| `data/*.json` | Page JSON (home, about, contact, header, footer, terms) edited via `/admin/page-content` |
| `storage/app/public/` | Files uploaded via `/admin/media` (DB `media` table points here) |
| `public/images/` | Static bundled images (logos, OG defaults, `/images/site/*` imports) |

Older deploy templates excluded **`/data/`** and all of **`/storage/`** so production
admin edits were never overwritten. That is correct for a **mature** live site, but wrong
for **first deploy** — the server never receives seed JSON or imported media from the repo.

### Approach used on SkyHealth Pro
**Include content on deploy initially**; the site owner adds rsync excludes manually once
the admin panel is the source of truth in production.

**Included (synced from repo → server):**
- `data/` — all page JSON
- `storage/app/public/` — uploaded/imported media files
- `public/images/` — static site assets

**Still excluded (runtime only — never ship these):**
```yaml
--exclude='/storage/framework/'
--exclude='/storage/logs/'
--exclude='/storage/pail/'
--exclude='/bootstrap/cache/'
```

Do **not** exclude all of `/storage/` — that blocks `storage/app/public/` media from
deploying while still letting framework cache/sessions stay server-local.

`public/storage` is a symlink — recreate with `php artisan storage:link --force` in
post-deploy rather than rsyncing the link itself.

### When to add excludes back (production is live)
Once admins edit page JSON or upload media in production, add to `deploy.yml` rsync:

```yaml
--exclude='/data/'
--exclude='/storage/app/public/'
```

Optional: also exclude `public/images/` if logos are managed only via Settings/Media in prod.

### Recommendation for the base template
- Document the two-phase deploy policy in README / dev-workflow:
  1. **Launch:** sync `data/` + media so seed content lands on the server.
  2. **Steady state:** exclude those paths so deploys only ship code + built assets.
- Default workflow comment block listing the excludes to uncomment after go-live.
- Never use `--delete` against production `data/` or `storage/app/public/` without those
  excludes — rsync `--delete` will **remove server-only uploads** not present in the repo.

---

## 28. Module enable/disable must be DB-authoritative end-to-end — avoid config/code/ schema drift

**Severity:** High — public features silently disappear or the whole site 500s after deploy.

**Project:** SkyHealth Pro (2026-07-30)

### What went wrong (Services module)
A chain of gaps between “module exists in code”, “module row in DB”, and “schema matches
code” — all while the admin UI *looked* configurable:

1. **`services` module disabled on production** — physical modules are **not** seeded by
   `ModulesSeeder` (that seeder only walks `config/modules.php` virtual modules). With no
   `modules` row, `ModuleManager::enabled('services')` falls back to
   `config('template.features.services', false)` → **false** (key not in `template.php`).
   Routes never register; `servicesNav` shared prop never mounts; `/services` → 404.

2. **Admin “enable” failed** — production already had an **old boilerplate `services`
   table**. Module enable runs `Schema::create` via module migrations → “table already
   exists” → module marked **`unhealthy = 1`**. Even with `enabled = 1`,
   `enabled()` returns **false** while unhealthy:
   ```php
   return ! empty($registry[$key]['enabled']) && empty($registry[$key]['unhealthy']);
   ```

3. **“Clear health flag” wasn’t enough** — after clearing unhealthy, code deployed that
   queries columns (`icon`, `nav_desc`, …) the old table never had → **500 on every page**
   (`servicesNav` closure in `ServicesModuleServiceProvider`).

4. **Deploy pipeline doesn’t initialize module state** — recurring deploy runs
   `RoleAndPermissionSeeder` + `AdminUserSeeder` only. No step ensures required physical
   modules are enabled, healthy, and schema-current. Module lifecycle is manual admin UI
   only — easy to miss on first production push.

5. **Two migration paths collide** — `php artisan migrate` (deploy) and
   `ModuleManager::runMigrations()` (enable) both touch module tables. If the table is
   created by one path but the migration row is missing from the other, re-enable tries
   `CREATE` again. If create is skipped because the table exists, **alter/upgrade never
   runs** unless a separate upgrade migration exists in the main `database/migrations/`
   folder (module-path migrations are not in the default deploy migrate step).

### Symptoms on the live site
- Header **Services** mega-menu missing (`servicesNav` null/empty).
- `/services` 404 while admin may still show Services screens (or enable throws).
- `/admin/modules` shows **unhealthy** with “table already exists” or similar.
- After force-enabling: sitewide **500** (`Unknown column 'icon' …`).

### Fix applied on SkyHealth Pro
- SQL: enable module row, clear unhealthy, insert missing `migrations` row.
- `2026_07_30_120000_upgrade_services_table.php` in main migrations (runs on deploy).
- Module create migration upgraded to `hasTable` + `hasColumn` alter path.
- `ServicesModuleServiceProvider`: guard on `Schema::hasColumn('services', 'icon')` +
   try/catch so schema drift doesn’t white-screen the site.

### Recommendation for the base template

**A. Single source of truth: the `modules` table**

- Treat **`modules.enabled`** (and **`unhealthy`**) as the only runtime switch for physical
  modules on production — not `.env` feature flags alone, not “code shipped = on”.
- Extend **`ModulesSeeder`** (or add `PhysicalModulesSeeder`) to `firstOrCreate` a row for
  **every** registered physical module (`app/Modules/*/*ModuleServiceProvider.php`), with
  project defaults (e.g. `services => enabled: true` for SkyHealth). Virtual modules
  already get this; physical modules currently do not.

**B. Project defaults in `config/template.php`**

- Add a `features.services` (etc.) entry for every physical module the site requires, so
  `enabled()` has a sane fallback **before** the first admin visit to `/admin/modules`:
  ```php
  'services' => env('FEATURE_SERVICES', true),
  ```

**C. First-deploy / deploy pipeline**

- Document (and optionally automate) a one-time step after first migrate:
  ```bash
  php artisan db:seed --class=ModulesSeeder --force
  php artisan db:seed --class=DatabaseSeeder --force   # incl. ServicesSeeder if needed
  ```
- Or add `composer deploy` step: `php artisan module:ensure-required` that enables a
  configured list of module keys, runs upgrade migrations, clears unhealthy — idempotent.

**D. Module migrations must always upgrade, not only create**

- Pattern used for `contact_submissions` and `services`:
  - if `!hasTable` → create full schema;
  - if `hasTable` → `hasColumn` guards, add missing columns, backfill, drop obsolete cols.
- Never `return` early on `hasTable` without running the alter path — that was the root
  cause of the missing-`icon` 500.

**E. Decouple “healthy” from “enabled” for read paths (or auto-heal)**

- Option 1: shared props like `servicesNav` must **never** throw — return `[]` on schema
  mismatch (defense in depth).
- Option 2: `enabled()` for **public routes** ignores `unhealthy` when the failure was
  migration-only and schema is now valid (harder — prefer explicit “Repair module” action).
- Admin UI: **Clear health** should re-run migration check / offer “Repair schema”, not
  only flip a flag.

**F. Cache**

- `modules.registry` is `Cache::rememberForever` — after manual SQL fixes, deploy must run
  `php artisan cache:clear` or module toggles appear stuck until cache flush.

**G. Checklist for any new physical module**

1. Row in `modules` seeder with correct default `enabled`.
2. Feature flag fallback in `config/template.php`.
3. Create migration with create **and** upgrade branches.
4. Shared Inertia props wrapped / column-guarded.
5. First-deploy doc: enable module + seed + verify public URL before DNS cutover.

---

## 29. Navigation split across Menus, Page Content, and Services — consolidate to one DB-backed admin screen

**Severity:** High — footer/header nav missing on production, duplicate admin UIs, nav tied to optional module state.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- **Three places** to edit the same links: Admin → Menus, Admin → Header/Footer (MenuLocationEditor), and Services → “Show in header dropdown”.
- Footer **Solutions** column empty on live site while Company links work.
- Header Services mega-menu missing when `servicesNav` shared prop is empty (module disabled/unhealthy or `show_in_nav` off).
- Admins confused about which screen owns nav; deploy doesn't sync menu shape across environments.

### Root cause
1. **Split ownership** — header/footer company links in `menus` table; Solutions column and header dropdown fed by **`servicesNav`** (Services module shared prop + `show_in_nav` column), not menus.
2. **`servicesNav` depends on module lifecycle** — see §28; when Services is off/unhealthy, both header dropdown and footer Solutions vanish (`v-if="servicesNav.length"`).
3. **Duplicate editors** — Page Content layout panel also embedded `MenuLocationEditor` for header/footer, so Menus wasn't the single source of truth.
4. **`MenuSeeder` not in deploy pipeline** — new `footer_solutions` location never populated on production unless seeder run manually (deploy only re-runs RoleAndPermission + AdminUser seeders per §6).

### Fix applied on SkyHealth Pro
- **Single admin UI:** Admin → Menus only — three sections: Header (with nested submenus), Footer Solutions, Footer Company.
- **`menus` table** — `location`: `header` | `footer` | `footer_solutions`; `parent_id` for dropdown children; optional `icon` + `description` for mega-menu items.
- **`MenuItemData::treeForLocation()`** — nested trees in shared `menus` Inertia prop (`header`, `footer_solutions`, `footer`).
- **`SiteHeader` / `SiteFooter`** — render from DB menus; removed `servicesNav` and hardcoded “insert Services after first link” hack.
- **Removed** menu editors from Page Content layout; removed `show_in_nav` from Services admin form (nav is Menus-only).
- **`MenuSeeder`** — seeds Services parent + children in header, flat links in `footer_solutions`.
- **Migration `backfill_footer_solutions_menus`** — if `footer_solutions` is empty on deploy, copy from header Services children or seed from `services.json`.

### Recommendation for the base template
- **One nav admin screen, one table** — never split header/footer/Solutions across JSON, a module shared prop, and Menus.
- For dropdowns: parent row + children rows (`parent_id`), not a separate prop from another feature module.
- Ship **`footer_solutions`** (or equivalent column-group locations) in base `MenuSeeder` + admin Menus UI from day one on any site with a Solutions/footer grid.
- **Backfill migrations** when adding new menu locations — don't rely on MenuSeeder in CI (§6: not safe to run every deploy if using `updateOrCreate` on titles, but empty-location backfill migrations are idempotent and belong in `migrate`).
- Remove **`servicesNav`-style shared props** — if a module owns content (service detail pages), nav links to that content still live in `menus`, edited in one place.
- Update **`page-content` skill**: header/footer JSON = copy + column headings only; links → Menus.
- **Update §23:** 404 respond hook no longer needs `servicesNav`; pass minimal branding props only.

---

## 30. Essential public features must be `core` modules — not optional toggles

**Severity:** High — `/services` and nav disappear when an admin (or missing DB row) disables the module.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Services listed in Admin → Modules with an on/off toggle, as if optional.
- Production: module disabled or unhealthy → `/services` 404, Solutions nav gone (before menus consolidation, via `servicesNav`).
- “Enable” fails on stale schema → stuck unhealthy (§28).

### Root cause
Physical **Services** module shipped without `'core' => true`. `ModuleManager::enabled()` respects DB `enabled` + `unhealthy` for non-core modules. Site-critical features were treated like optional add-ons (Blog, Shop).

### Fix applied on SkyHealth Pro
- **`app/Modules/Services/module.php`** — `'core' => true` (always enabled at runtime; cannot disable/uninstall from Modules UI).
- **Migration `make_services_module_core`** — `updateOrCreate` modules row: `enabled=1`, `unhealthy=0`, clear `disabled_at`; flush `modules.registry` cache.

### Recommendation for the base template
- Any module whose **public routes are part of the default site** (Services, Contact form module, Menus, Settings) should declare **`'core' => true`** in `module.php` — not only virtual modules in `config/modules.php`.
- Document in **`create-module` / `modules-reference` skills**: “If disabling this module would break the marketing site, mark it core.”
- **`ModulesSeeder` for physical modules** (§28-B): seed a row for every physical module with sensible defaults so first deploy isn't “module missing from DB.”
- Core ≠ hidden: core modules can still use **`nav_visible`** (§11) to trim sidebar without disabling functionality.

---

## 31. `/admin` must stay out of search indexes even when `SEO_INDEXABLE=true`

**Severity:** Medium — admin panel crawlable after go-live if only sitewide staging noindex is used.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Staging uses `SEO_INDEXABLE=false` → everything noindex (masks the gap).
- After launch (`SEO_INDEXABLE=true`), public pages become indexable but **`/admin/*` has no permanent exclusion** unless added explicitly.
- `robots.txt` when indexable was `Disallow:` (empty) → crawlers allowed everywhere except what they infer from meta tags.

### Root cause
`PreventSearchIndexing` and `app.blade.php` only sent `noindex` while **`!config('template.indexable')`**. No admin-path exception after launch. Admin uses the same Inertia shell as the public site.

### Fix applied on SkyHealth Pro
Three layers (belt + suspenders):
1. **`PreventSearchIndexing`** — always set `X-Robots-Tag: noindex, nofollow` for `admin` and `admin/*` (authoritative for crawlers).
2. **`app.blade.php`** — `<meta name="robots" content="noindex, nofollow">` when `request()->is('admin*')`.
3. **`robots.txt`** — when indexable: `Disallow: /admin` (public pages still allowed; sitemap unchanged).
4. **`AdminLayout.vue`** — Inertia `<Head>` robots meta for client navigations.

### Recommendation for the base template
- Bake admin noindex into **`PreventSearchIndexing`** + **`robots.txt`** + admin layout by default — never rely on “admin is obscure” or sitewide staging flags.
- **`/login`** is optional same treatment if the panel is admin-only (SkyHealth kept login separate; consider `Disallow: /login` on admin-only sites).
- Go-live checklist (§4): confirm `curl -I https://example.com/admin` returns `X-Robots-Tag: noindex, nofollow` **after** `SEO_INDEXABLE=true`.
- Separate admin subdomain: still noindex on that host; also block in `robots.txt` if the host serves only admin.

---

## 32. Deploy checklist addendum — menus, services, indexing (SkyHealth Pro 2026-07-30)

Quick verification after deploy (extends §4 / §28-G):

| Check | Command / action |
|---|---|
| Migrations through menu + services core | `php artisan migrate:status` |
| Footer Solutions populated | Admin → Menus → Footer Solutions; or `Menu::where('location','footer_solutions')->count()` |
| Services always on | Admin → Modules: Services shows **Core**, no disable toggle |
| Public `/services` | HTTP 200 |
| Footer shows Solutions links | View source / Inertia `menus.footer_solutions` |
| Admin not indexed | `curl -sI …/admin \| grep -i x-robots` → `noindex` |
| `robots.txt` blocks admin | `curl …/robots.txt` → `Disallow: /admin` when indexable |
| Response cache cleared | deploy step `responsecache:clear` (already in pipeline) |

**Reminder:** template fixes only help after the commit containing them is deployed and migrated on production.

---

## 33. Media library: alt-text edit popup missing (and `media.update` is a dead permission)

**Severity:** Medium — alt text can only be set at upload time; there is no way to fix
SEO/accessibility copy on existing files without re-uploading.

**Project:** SkyHealth Pro (2026-07-30) — confirmed in base template too.

### Symptoms
- Admin expects **Admin → Media → click an uploaded image → edit alt text in a
  popup/modal** (common CMS pattern).
- In the shipped template, **clicking a grid tile does nothing** — only **Delete**
  is wired. No modal, no inline editor, no detail panel.
- Alt text entered in the **upload form** is saved once; there is no UI to change it
  afterward even though the `media` table has an `alt_text` column and the grid
  renders `item.alt_text` on the thumbnail.
- `config/modules.php` declares **`media.update`** permission and several modules
  list `media` as a dependency, implying full CRUD — but **`routes/admin.php` has
  no `PUT`/`PATCH` media route** and `MediaController` has no `update()` method.

### Root cause
The media module was shipped as **upload + list + delete** only:

| Layer | Alt text support |
|---|---|
| Upload (`POST /admin/media`) | ✅ `alt_text` validated + stored |
| Index grid (`Admin/Media/Index.vue`) | ❌ read-only display; tiles not clickable |
| Update endpoint | ❌ missing entirely |
| `AppMediaPicker` library modal | ❌ pick-only; no alt edit |
| Permission `media.update` | ❌ declared, never enforced or routed |

So the “edit popup” is not a broken modal — **it was never implemented**. Any
expectation that clicking a thumbnail opens alt-text editing is unfounded against
current code; the gap is the missing feature + orphaned permission.

### Impact
- Typos or empty alt text on uploaded images **cannot be corrected** from the admin.
- `media:import-site` registers assets with `alt_text: null` — no follow-up path
  to add descriptions without a DB edit or re-import hack.
- Public `SiteImage` / content that should use `alt_text` from the media row has
  nothing to pull if admins can't maintain it.

### Fix to add to the base template
1. **Route + controller:**
   ```php
   Route::middleware('can:media.update')->group(function () {
       Route::put('media/{media}', [MediaController::class, 'update'])->name('media.update');
   });
   ```
   ```php
   public function update(Request $request, Media $media)
   {
       $validated = $request->validate([
           'alt_text' => ['nullable', 'string', 'max:255'],
       ]);
       $media->update($validated);
       return back()->with('success', 'Media updated.');
   }
   ```
2. **`Admin/Media/Index.vue`:** make each tile (or an Edit button) open a
   **teleported modal** (same pattern as `AppMediaPicker`'s library overlay —
   `z-[200]`, `@click.self` to close) with:
   - preview image
   - `alt_text` input bound to a per-item `useForm`
   - Save → `form.put(\`/admin/media/${id}\`)` with `preserveScroll: true`
   - validation errors surfaced in the modal (don't rely on console only)
3. **Optional:** allow alt edit inside `AppMediaPicker`'s browse modal (long-press
   or pencil icon) so editors don't have to leave Page Content / Settings.
4. **Docs:** update `settings-and-media` skill — alt text is editable post-upload
   via Media index, not upload-only.

### Verification
- Upload an image with alt text `"Test"` → open edit modal → change to `"Updated"`
  → save → grid thumbnail `alt` attribute and DB row both show `"Updated"`.
- User without `media.update` permission: no edit affordance (or 403 on PUT).
- PDF/non-image rows: modal shows filename + alt field (or disable alt for non-images).

### Note for project feedback sync
If a client repo adds a custom alt-edit modal locally, fold that implementation back
into the base template rather than leaving each project to reinvent it — and wire
`media.update` while doing so so the permission map matches reality.

---

## 34. Admin → Menus: reorder (sort order) fails silently after edit/save

**Severity:** Medium — changing a menu item's order number appears to do nothing; same
for submenu items under header dropdowns. Works on localhost after fix but production
may still run old JS until deployed.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Admin → Menus → **Edit** a row → change **Order** → **Save** → nothing happens (row
  stays at old `#` position, or edit mode feels "stuck").
- No visible error in the UI — only unrelated Chrome-extension noise in the console
  (`A listener indicated an asynchronous response…`, `AutoClicker`, etc.).
- Adding a new link works; **updating** an existing row (especially sort order) fails.
- Reproduces on top-level header links and nested submenu children.

### Root cause
1. **`MenuLocationEditor` never rendered `editForm.errors`** — only `newForm.errors` had
   a red error box. A failed `PUT /admin/menus/{id}` returned 422 with zero admin feedback.
2. **`sort_order` validation too strict** — `MenuController@update` used
   `'sort_order' => ['integer', 'min:0']` without `nullable`. Vue's `v-model.number` on
   the custom `AppInput` atom still emits **strings**; an empty or coerced **`null`**
   sort order fails Laravel integer validation (`null` → "must be an integer").
3. **`AppInput` ignores `type="number"` for coercion** — it always emits
   `target.value` as a string; modifiers on the parent form do not guarantee an integer
   reaches the server.

### Fix applied on SkyHealth Pro
**`MenuLocationEditor.vue`:**
- `normalizeSortOrder()` + `prepareMenuPayload()` — coerce order to a non-negative
  integer before every POST/PUT.
- `saveEdit()` / `addItem()` / `addChild()` use `.transform(prepareMenuPayload)`.
- Red error box for **`editForm.errors`** and **`childForm.errors`**.
- `startEdit()` clears errors and normalizes loaded `sort_order`.

**`MenuController.php`:**
- `'sort_order' => ['nullable', 'integer', 'min:0']` on store + update.
- On update: if `sort_order` is null/missing, keep the existing DB value.
- On store: default null `sort_order` to `0`.

### Recommendation for the base template
- Ship the error display + payload normalization in `MenuLocationEditor` by default.
- Loosen sort-order validation as above; never require a bare `integer` without
  `nullable` on optional numeric admin fields bound to text inputs.
- Optional UX: **Move up / Move down** buttons that swap `sort_order` values instead of
  raw number inputs — less error-prone for editors.
- **Debugging note:** tell admins to ignore `message channel closed` console spam from
  browser extensions when testing Menus; look for the red Inertia validation box or
  Network → `PUT admin/menus/{id}` → 422 response instead.

### Verification
- Edit header link order `1 → 3` → Save → list re-sorts, `#3` shown.
- Edit submenu child order under Services → same.
- Clear order field → Save → coerces to `0` (or keeps previous server-side), no silent fail.
- Production: deploy latest frontend **and** PHP, then hard-refresh `/admin/menus`.

---

## 35. `SiteImage` component — reserve layout space + lazy load (reduce CLS)

**Severity:** Medium — public pages jump when images load or show empty gaps when URLs
404; hurts Core Web Vitals and looks broken after partial DB imports.

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Hero, card, and collage sections **shift height** as images download.
- Missing/broken image URLs collapse sections or leave awkward empty boxes.
- Inconsistent `loading="lazy"` — some `<img>` tags had it, many did not.

### Fix applied on SkyHealth Pro
Introduced **`resources/js/Components/Public/SiteImage.vue`** with:
- **`frame` presets** (`hero`, `who`, `human`, `contact-hero`, `fill`, `avatar`, …)
  that set min-height / aspect-ratio on a wrapper **before** the image loads.
- Placeholder background (`var(--teal-tint-2)`) on the wrapper when `src` is empty or
  loading.
- Default **`loading="lazy"`** + **`decoding="async"`** on every image.

Migrated public pages + shared components (`CtaBand`, `PressureBanner`,
`ComplianceBento`, `TestimonialCard`, header/footer logos, shop/case-studies/blog).

Parent wrappers (`.hero-photo`, `.who-media`, `.mega-cta`, etc.) also got
`min-height` + fallback background for CLS.

### Recommendation for the base template
- Ship **`SiteImage`** (or `AppImage`) as the standard public-image primitive — not raw
  `<img>` on marketing pages.
- Document frame presets in `settings-and-media` or `page-content` skill.
- Grep before launch:
  `rg '<img ' resources/js/Pages/Public resources/js/Components/Public`
  — only tiny icons/favicon chips should remain as bare `<img>`.
- Only opt out of lazy (`loading="eager"`) for the single LCP hero if profiling proves
  it helps — default lazy everywhere else.

---

## 36. `SiteImage` `frame="fill"` + negative z-index hides image behind placeholder

**Severity:** High — hero (and similar) sections show only the mint/teal placeholder box
even when the image URL is valid and loads (200 in Network tab).

**Project:** SkyHealth Pro (2026-07-30)

### Symptoms
- Homepage hero right column: large rounded rectangle with **teal-tint background**,
  favicon chip + snapshot card visible, but **no photo**.
- Same class of bug on human-connection photo, shop grids, and background banners if
  `fill` + parent `background` combine wrong.
- Image is in the DOM (`<img src="…">` present) but not visible.

### Root cause
Initial `SiteImage` CSS used:

```css
.site-image--fill {
    position: absolute;
    inset: 0;
    z-index: -2;
}
```

On **non-isolated** wrappers like `.hero-photo` (which also set
`background: var(--teal-tint-2)`), a negative z-index paints the image **behind the
parent's background**, so only the placeholder color shows. The bug is not a missing
`src` — it's a **stacking-context** mistake.

Using `frame="fill"` for in-flow hero photos (instead of `frame="hero"`) made this worse.

Background banners (`mega-cta`, `pressure-banner`, `.bg-img`) need the image **above**
the parent's fallback color but **below** gradient overlays (`::after` at z-index 1).

### Fix applied on SkyHealth Pro
1. **Photo sections** — use dedicated frames (`hero`, `human`, `contact-hero`, `who`, …)
   in normal document flow; reserve `fill` for true background layers.
2. **CSS stacking:**
   - `.site-image--fill { z-index: 0; }` by default.
   - `.site-image--fill:has(.bg-img) { z-index: 0; }` with overlays at `z-index: 1`.
   - Legacy `.mega-cta .bg-img`, `.pressure-banner .bg-img`, etc. updated from `-2` to `0`.
3. **Hero gradient** — `.hero-photo::after { z-index: 1; }` so overlay sits on the photo.

### Recommendation for the base template
- **Never default `fill` to negative z-index.** Negative stacking only works inside
  `isolation: isolate` containers where the spec guarantees background vs negative-child
  order — do not rely on it for `.hero-photo`-style wrappers.
- Rule of thumb:
  - **Content photo** → sized frame preset (`hero`, `who`, …).
  - **Decorative background under text** → `fill` + `img-class="bg-img"` + parent
    `min-height` + gradient pseudo-element at `z-index: 1`.
- Add a visual regression check: homepage hero must show the photo, not a flat tint block,
  before every release.

### Verification
- Homepage hero shows caregiver photo with snapshot card overlay.
- Human-connection quote card overlays bottom of photo; top-right badge visible.
- Mega-CTA / pressure banner still show dimmed photo under gradient, not solid teal only.

---

## 37. `v-reveal` scroll animation leaves whole sections invisible

**Severity:** High — large page blocks (pressure banner, bento, runway, FAQ) render with
`opacity: 0` permanently; text appears "missing" with no console error.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
- Homepage sections show background images or tinted boxes but **no headings, body copy,
  or cards** — looks like a content bug, not an animation issue.
- Happens on first load and after Inertia client navigations; a hard refresh sometimes
  fixes individual blocks but not consistently.
- Tall sections are hit hardest (bento grid, runway track, compliance block).

### Root cause
The custom `v-reveal` directive (`resources/js/directives/reveal.ts`) uses
`IntersectionObserver` with **`threshold: 0.15`**. For tall elements, the browser may
never report 15% intersection (especially with `rootMargin: '0px 0px -5% 0px'`), so
`in-view` is never added and elements stay at `opacity: 0`.

A secondary timing gap: after hydration or Inertia navigation, the observer can miss the
first paint — above-the-fold blocks that are already visible never get a callback.

### Fix applied on SkyHealth Pro
1. **`threshold: 0`** — any pixel visible is enough to reveal.
2. **Removed** the extra `rect.height * 0.15` guard in the sync fallback.
3. **Double `requestAnimationFrame`** sync check after mount so already-visible elements
   get `in-view` immediately.

### Recommendation for the base template
- Ship `threshold: 0` (or a very low value like `0.01`) as the default in the reveal
  directive — marketing pages have many full-width tall blocks.
- Always include a post-mount sync check (rAF) for elements already in viewport.
- Document in dev-workflow: "section text missing, image shows → check `v-reveal` / `.in-view`
  before assuming JSON content is empty."
- Optional: add `prefers-reduced-motion: reduce` bypass that sets `opacity: 1` immediately.

### Verification
- Scroll through homepage: pressure, solutions featured banner, bento, runway, FAQ all show
  text without scrolling back and forth.
- Inertia navigate Home → About → Home: no sections stuck hidden.

---

## 38. Text-over-image sections need explicit foreground `z-index` (extends §36)

**Severity:** High — headings and paragraphs render **behind** gradient overlays or
background images; looks like missing content.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
- **Pressure banner**, **mega-CTA**, **solutions featured card**, **compliance bento hero**:
  background photo or teal tint visible, text invisible or faint.
- **Homepage solutions featured tile**: icon/title/description hidden under `::after`
  gradient overlay.
- `/services` featured card: `h4` and `p` direct children not covered by a wrapper — only
  `.icon-circle` had z-index, so title/body stayed under the overlay.

### Root cause
Reference HTML used **negative z-index** for background images and **positive z-index**
for foreground content. When porting to Vue + `SiteImage`, overlays landed at
`z-index: 1` (`::after` gradients, `.bg-img`) but text wrappers often had **no z-index**,
so they painted at auto (0) — **below** the overlay.

Partial fixes (only z-indexing `.icon-circle` or `div:not(.site-image)`) miss direct
`h4`/`p`/`a` children.

### Fix applied on SkyHealth Pro
- `.pressure-banner-inner`, `.mega-cta-inner` → `position: relative; z-index: 2`.
- `.sol-card.featured > :not(.site-image)` → all direct foreground children at `z-index: 2`.
- `.bento-hero > :not(.site-image)` → same pattern.
- Homepage featured banner: wrap icon + text in `.featured-inner` with `z-index: 2`;
  page-specific layout in `home.css` (centered overlay, distinct from `/services` card).

### Recommendation for the base template
- Document a **stacking recipe** for every text-over-photo pattern:
  - Background image: `z-index: 0` (never negative unless parent has `isolation: isolate`).
  - Gradient overlay (`::after`): `z-index: 1`.
  - All readable content: `z-index: 2`, `position: relative`.
- Prefer a **single inner wrapper** (`.banner-inner`, `.featured-inner`) over per-element
  z-index on arbitrary direct children.
- When auditing a new page port, grep for `::after` / `.bg-img` / `frame="fill"` and verify
  every sibling text node is above the overlay — not just the icon circle.
- Add to `page-content` / dev-workflow skill: visual QA checklist item for overlay sections.

### Verification
- Homepage: pressure quote, solutions featured banner, compliance bento hero — all readable.
- `/services`: featured card title + description visible over background image.

---

## 39. `SiteImage` / photo wrappers: `height: 100%` leaves gaps in `min-height` containers

**Severity:** Medium — hero and info photos show **empty teal/mint band** at the bottom;
image doesn't fill the reserved frame.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
- Homepage hero right column: photo stops short; placeholder background visible below.
- Services page HCBS/Tech **info-photo** blocks: same gap at bottom of rounded frame.
- Human-connection photo: wrapper taller than image content.

### Root cause
Parent wrappers (`.hero-photo`, `.info-photo`, `.human-photo`) set `min-height` and a
fallback `background`, while inner `SiteImage` used `height: 100%` without the parent
having an explicit **computed height** — percentage height resolves to auto, image shrinks
to intrinsic size, gap shows the wrapper background.

### Fix applied on SkyHealth Pro
Set explicit dimensions on **both** the frame preset and the `<img>`:

```css
.hero-photo .site-image--hero,
.hero-photo .site-image__img {
    width: 100%;
    height: clamp(320px, 38vw, 460px);
    object-fit: cover;
}
```

Same pattern for `.human-photo`, `.page-services .info-photo` (`min-height: 320px`).

### Recommendation for the base template
- **`SiteImage` frame presets should own the height**, not rely on `height: 100%` from an
  unspecified parent — document which frames are in-flow vs absolute fill.
- When adding a new photo wrapper, always pair `min-height` on the wrapper with the **same
  min/height on `.site-image__img`** + `object-fit: cover`.
- Extend §35 visual regression: hero and info-split photos must fill their frames with no
  tint gap before release.

---

## 40. Mixed content: HTTPS site serves `http://` Vite/CSS/JS behind cPanel/Cloudflare

**Severity:** High — production site loads blank/unstyled; browser console blocks every
asset as mixed content.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
```
Mixed Content: The page at 'https://example.com/' was loaded over HTTPS, but requested
an insecure stylesheet 'http://example.com/build/assets/app-*.css'.
Mixed Content: … requested an insecure script 'http://example.com/build/assets/app-*.js'.
```
- Inertia JSON payload shows `"canonical": "http://…"`, `"og_image": "http://…"`.
- Server `.env` may have `APP_URL=https://…` but assets still emit `http://` if config
  was cached when `APP_URL` was wrong, or PHP sees the request as HTTP behind the proxy.

### Root cause
1. **`APP_URL=http://…`** (or missing) → Laravel's URL generator and `@vite` emit
   absolute `http://` URLs.
2. **Proxy TLS termination** — cPanel/Cloudflare send HTTP to PHP; without
   `trustProxies()`, `request()->secure()` is false even though the browser uses HTTPS.
3. No **`URL::forceScheme('https')`** in production → generated URLs follow the (wrong)
   incoming scheme.

§4 mentions `APP_URL` but not the proxy + forceScheme stack.

### Fix applied on SkyHealth Pro
1. **`AppServiceProvider::boot()`** — `URL::forceScheme('https')` when
   `environment('production')` or `APP_URL` already starts with `https://`.
2. **`bootstrap/app.php`** — `$middleware->trustProxies(at: '*')` for
   `X-Forwarded-Proto` / `X-Forwarded-Host`.
3. **`public/.htaccess`** — canonical HTTPS redirect, skipping when
   `X-Forwarded-Proto: https` (avoid double redirect behind proxy).
4. **`SeoService::canonicalFor()`** — absolute canonical via `url()` helper (respects
   forced scheme).
5. **`template:doctor --production`** — fail/warn if `APP_URL` is not HTTPS.
6. **`.env.example`** comment documenting production `APP_URL` requirement.

### Recommendation for the base template
- Ship all three layers by default: **forceScheme + trustProxies + .htaccess HTTPS rule**.
- Go-live checklist (§4): after deploy run
  `curl -sI https://example.com/ | grep -i content-security` and confirm Inertia page
  source has no `http://example.com/build/` URLs.
- **`template:doctor --production`** should also HEAD-check one built asset URL scheme, not
  only read `APP_URL` from config.
- Document: changing `APP_URL` on server requires **`php artisan config:cache`** and a
  rebuild is **not** required for URL helpers (unlike `VITE_*` — see §24).

### Verification
- View source on HTTPS home: all `<link>` / `<script type="module">` hrefs are `https://`.
- Inertia JSON: `seo.canonical` and `og_image` use `https://` when absolute.

---

## 41. Menu URLs stored without leading slash break nav and Inertia routing

**Severity:** Medium — mega-menu child links 404 or full-page reload; Services parent
points to `/` instead of `/services`.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
- Inertia payload: `"Service"` parent `"url": "/"` while children exist (should be
  `/services` for the dropdown parent href).
- Child URL `"services/intake-referrals"` (no leading `/`) — Inertia may treat as
  relative path incorrectly.
- Header Services item works in admin but wrong link target on production.

### Root cause
Menu URLs entered in admin without a leading slash are stored verbatim. No normalization
on save or when building `MenuItemData`. Seeded/imported rows can inherit bad paths.

### Fix applied on SkyHealth Pro
- **`Menu::normalizeUrl()`** — internal paths get `/` prefix; external `http(s)://` and
  `#` unchanged.
- **`MenuController`** store/update + **`MenuItemData::fromMenu()`** call normalize.
- **Migration `normalize_menu_urls`** — backfill existing rows; set header Services
  parent (non-Home, has children, `url = /`) → `/services`.

### Recommendation for the base template
- Ship `Menu::normalizeUrl()` and apply on every write + DTO read path by default.
- Admin Menus UI hint: "Internal paths: `/about`, `/services/foo`".
- MenuSeeder should only emit normalized URLs.
- Include Services parent default `url: '/services'` in `MenuSeeder`, not `/`.

---

## 42. MySQL error 1093 when migration UPDATE uses `whereHas` on the same table

**Severity:** High — migration fails on production MySQL; deploy stops mid-migrate.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
```
SQLSTATE[HY000]: General error: 1093 You can't specify target table 'menus' for update
in FROM clause
```
SQL shape:
```sql
UPDATE menus SET url = '/services' WHERE … AND EXISTS (
  SELECT * FROM menus AS laravel_reserved_0 WHERE menus.id = laravel_reserved_0.parent_id
)
```

### Root cause
Eloquent `->whereHas('children')->update([…])` generates an `EXISTS` subquery on the
**same table** being updated. MySQL forbids this (SQLite/Postgres may allow it — easy to
miss locally if dev uses a different engine; this project is **MySQL only**).

### Fix
Two-step: **select IDs, then update by primary key**:

```php
$ids = Menu::query()
    ->where(/* … */)
    ->whereHas('children')
    ->pluck('id');

if ($ids->isNotEmpty()) {
    Menu::query()->whereIn('id', $ids)->update(['url' => '/services']);
}
```

### Recommendation for the base template
- Document in dev-workflow / migration skill: **never `update()` + `whereHas()` on the
  same table** under MySQL — always pluck IDs first or use a join subquery with an alias
  wrapper (`FROM (SELECT …) AS t`).
- Add a note to `composer dev` / CI: local dev must use MySQL (already a project rule) so
  1093 surfaces before production.
- If migration partially ran (failed mid-batch), document rollback:
  `migrate:rollback --step=1` then re-run after fix.

---

## 43. `SEO_INDEXABLE` must gate `noindex` in shared SEO props, not only middleware

**Severity:** Medium — site stays `noindex: true` in Inertia payload after setting
`SEO_INDEXABLE=true` in production.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
- `.env` has `SEO_INDEXABLE=true`, public pages should be indexable.
- Inertia JSON still shows `"noindex": true` in shared `seo` prop.
- Google sees noindex if driven off the prop/meta tag path.

### Root cause
`HandleInertiaRequests::resolveSeo()` (and service detail `seoFor()`) only checked
per-page meta and site settings — not **`config('template.indexable')`** tied to
`SEO_INDEXABLE`.

### Fix applied
```php
'noindex' => ! config('template.indexable') || $siteNoindex || (bool) ($meta['noindex'] ?? false),
```

### Recommendation for the base template
- Single source for indexability: **`config('template.indexable')`** must be OR'd into
  every code path that sets `noindex` (middleware, Inertia shared props, service pages,
  JSON page SEO blocks).
- Go-live checklist: after `SEO_INDEXABLE=true` + `config:cache`, confirm Inertia
  `seo.noindex === false` on home and `<meta name="robots">` absent or `index,follow`.

---

## 44. Same CSS component, different layout per page — scope page CSS (extends §3)

**Severity:** Low — homepage solutions "featured" banner renders as a broken horizontal
card instead of centered overlay banner.

**Project:** SkyHealth Pro (2026-08-10)

### Symptoms
- Homepage `.sol-card.featured`: icon and text left-aligned in a row; doesn't match
  reference (centered stack over full-width background image).
- `/services` featured card layout is correct — shared `.sol-card.featured` rules intended
  for the services index, not the homepage grid.

### Root cause
§3 covers selector **leakage across pages**; this is the inverse — **one shared class
used intentionally on two pages with different layouts**, and only one page's rules exist
(in `services.css` or `global.css`).

### Fix applied on SkyHealth Pro
- Vue: wrap homepage featured content in `.featured-inner`.
- CSS: **`.page-home .sol-card.featured { … }`** block in `home.css` — centered flex
  column, gradient overlay, min-height — without changing `/services` card layout.

### Recommendation for the base template
- When reusing a reference class name on multiple pages, decide upfront:
  - **Shared layout** → rules in `global.css`.
  - **Page-specific variant** → scope under `.page-{name}` in that page's CSS file.
- Document in page-content skill: homepage vs services both use "featured" tile — different
  markup wrappers (`featured-inner`) + scoped CSS, not one-size global override.

---

## 45. `skyreva.css`-style scoped shell: `.sky-app a/button { color: inherit }` breaks dark chrome

**Severity:** High — header, sticky subnav, and footer text invisible (dark on dark).

**Project:** SkyREVA (2026-07-31)

### Symptoms
- Fixed navy header: brand + nav labels render **black** on dark background.
- Sticky `.subnav` on Services / Company / Insights: same — links look black.
- Footer links may inherit body text color instead of light gray on `#061527`.

### Root cause
When porting a reference site, public CSS was scoped under `.sky-app` to avoid leaking
into admin — good — but these rules were added:

```css
.sky-app a { color: inherit; }
.sky-app button { color: inherit; }
```

Specificity `(0, 1, 1)` **beats** header class rules like `.brand { color: #fff }` `(0, 1, 0)`.
Header/subnav/footer sit **outside** `#app`, so they inherit `--text` (`#0d2338`) from
`.sky-app`.

Subnav is worse: it renders **inside** `#app` via `v-html`, so the inherit rule always
wins over `.subnav a { color: #bccddd }` unless specificity is raised.

The reference HTML used bare `a { color: inherit }` which **loses** to class selectors;
adding `.sky-app` prefix inverted that.

### Fix applied on SkyREVA
1. Limit inherit to page content only:
   ```css
   .sky-app #app a,
   .sky-app #app button { color: inherit; }
   ```
2. Subnav — higher specificity:
   ```css
   .sky-app #app .subnav a { color: #bccddd; }
   .sky-app #app .subnav a:hover,
   .sky-app #app .subnav a.active { color: #fff; }
   ```
3. Footer — explicit colors:
   ```css
   .sky-app footer .brand { color: #fff; }
   .sky-app footer .footer-links a { color: #bacada; }
   ```

### Recommendation for the base template
- If using a public root class (`.sky-app`, `.site-shell`), **never** set global
  `a/button { color: inherit }` on that root — scope to `#app` or `.site-main` only.
- Dark chrome (header, subnav, footer) should use **`.sky-app header …`** / **`.sky-app footer …`**
  selectors from day one, not bare `.brand` / `.subnav a`.
- Add to dev-workflow / page-content skill: after porting reference CSS, grep for
  `.sky-app a` and verify header/footer/subnav on a dark-background page before launch.
- Consider a lint/checklist item in `template:doctor` (visual doc only): "public layout
  uses dark header → confirm nav link contrast."

---

## 46. Sticky subnav hash links dead under Inertia — need `bindSubnav()` + `scrollToHashOnLoad()`

**Severity:** High — clicking subnav items does nothing; deep links like `/company#sec-team` don't scroll.

**Project:** SkyREVA (2026-07-31)

### Symptoms
- Services / Company / Serve / Insights sticky subnav: clicks don't scroll to sections.
- Scroll-spy (`initSpy`) may highlight on manual scroll, but navigation is broken.
- Reference site worked because it was a **hash-router SPA** that called `scrollToSection()`
  on every route/hash change.

### Root cause
Subnav markup uses `<a href="#sec-about" data-spy="about">`. In a Laravel + Inertia app:
- Inertia intercepts `<a>` clicks; hash-only navigation doesn't reliably smooth-scroll.
- `mountPageBehaviors()` shipped `initSpy()` but **not** click handlers for subnav.
- Landing with `?` or `#sec-*` in URL never scrolls without a mount-time hash reader.

### Fix applied on SkyREVA (`resources/js/Pages/Public/_shared/behaviors.ts`)
- **`bindSubnav()`** — `preventDefault`, `scrollToSection(data-spy)`, update URL hash.
- **`scrollToHashOnLoad()`** — on mount, if `location.hash` is `#sec-*`, scroll after paint.
- Call both from `mountPageBehaviors()` (after DOM from `v-html` exists).

### Recommendation for the base template
- Any project porting reference `behaviors.ts` for subnav must wire **click + hash-on-load**,
  not only `initSpy` on scroll.
- Document in page-content skill: subnav `href` can be `#sec-{slug}` or `/page#sec-{slug}`;
  handler must allow same-path hash links and defer cross-page navigation to Inertia.
- Optional base helper: export `bindSubnav` / `scrollToSection` from a shared composable
  or `_shared/behaviors.ts` template in stubs when generating marketing pages.

---

## 47. Hardcoded `content.ts` pages fail at runtime from missing imports (`@ts-nocheck` blind spot)

**Severity:** High — entire page white-screens (Company, Insights) with `ReferenceError` in console.

**Project:** SkyREVA (2026-07-31)

### Symptoms
- `/company` throws: `ic is not defined` inside `renderCompany()`.
- `/insights` throws: `cards is not defined` inside `renderInsights()`.
- `pnpm build` / `vue-tsc` pass — files use `@ts-nocheck` and string templates.

### Root cause
Marketing pages ported as **template-string renderers** (`content.ts` → `v-html`) import
helpers from `@/Components/Organisms/Marketing/templates` and `illustrations`. Easy to
call `ic()` or `cards()` in the template string without adding the import when copying
from the monolithic reference `index.html` script block.

### Fix applied
- `Company/content.ts` — `import { ic } from '@/Components/Organisms/Marketing/illustrations'`
- `Insights/hub-content.ts` — add `cards` to templates import.

### Recommendation for the base template
- If documenting the "content.ts + v-html" pattern (alternative to JSON admin pages), add
  a **pre-launch grep**:
  ```bash
  rg '\bic\(|\bcards\(|\bsubnav\(|\bhero\(' resources/js/Pages/Public/**/content.ts \
    -l | xargs -I{} sh -c 'head -20 {} | rg "import"'
  ```
- Consider a tiny smoke test or `pnpm build` step that **imports each `render*()`** in Node
  (or Vitest) without `@ts-nocheck` on a barrel file.
- In `page-content` skill: split guidance — **JSON + admin** for a handful of pages;
  **content.ts** for long-tail marketing clone — and list required imports per templates module.

---

## 48. SkyREVA-style site: hardcoded marketing copy vs `data/*.json` — document the split

**Severity:** Low — confusion about what deploy/content sync affects; stale boilerplate JSON.

**Project:** SkyREVA (2026-07-31)

### Context
SkyREVA cloned a static reference into many `content.ts` files + `PublicLayout.vue`
hardcoded header/footer. The boilerplate's `data/home.json` still contains generic
"Build Something Amazing" copy but **HomeController renders `Public/Home/Index.vue`**
which uses `renderHome()` from `content.ts`, not `JsonDataService`.

Also: `/contact` redirects to `/get-started`; `ContactController` left in place.

### Issues hit
- Editors look at `data/home.json` in repo — **not what the live home page uses**.
- Deploy `sync_content` for `data/` doesn't update visible home/services copy (it's in
  the Vite bundle via `content.ts`).
- Changing marketing copy requires **rebuild + redeploy**, not admin JSON edit.

### Recommendation for the base template
- Document two content strategies explicitly in `page-content` skill:
  1. **Admin-editable JSON** (`data/*.json` + Page Content panel) — home/about/contact scale.
  2. **Hardcoded port** (`content.ts` + `v-html`) — reference clone, rebuild to change.
- If a project picks (2), mark unused JSON files or remove from `data/` to avoid drift.
- README deploy section: clarify that **`public/build/` carries content.ts output**; excluding
  `data/` does not freeze hardcoded marketing pages.

---

## 49. `site_logo` / `site_favicon` in settings but PublicLayout still hardcoded (TODO from AGENTS.md)

**Severity:** Medium — admin uploads logo; public site shows inline SVG "SkyREVA" text mark.

**Project:** SkyREVA (2026-07-31)

### Context
Settings + `PUBLIC_SETTINGS` expose `site_logo` / `site_favicon`. `PublicLayout.vue`
header/footer use hardcoded SVG brand from the design reference — no `<link rel="icon">`,
no `settings.site_logo` in the chrome.

### Recommendation for the base template
- Wire favicon in layout `<Head>` from shared settings (with fallback).
- Header brand: use `useSiteSettings` / shared props when logo path set; keep text fallback.
- Add to go-live checklist for any project using Site Settings branding.

---

## 50. Deploy workflow gaps found on SkyREVA (extends §19, §24, §27)

**Severity:** Medium — build/deploy/docs mismatches.

**Project:** SkyREVA (2026-07-31)

### Issues
1. **`VITE_APP_NAME` unset in CI** — deploy.yml ran `pnpm build` without env; bundle throws
   in browser (§24). Fixed: `env: VITE_APP_NAME: SkyREVA` on build step.
2. **AI/agent paths not excluded** — `AGENTS.md`, `CLAUDE.md`, `agents/`, `_referance/`,
   `_tmp_*`, `.vscode/` shipped unnecessarily. Extended rsync excludes (§19 list + `_tmp_*`).
3. **`.env` / `key:generate` in docs** — production host already has `.env`; first-deploy
   SSH step should be **`php artisan db:seed --force` only**, not copy `.env.example`.
4. **`sync_content` workflow input** — toggle to include `data/*.json` on first deploy only;
   exclude after admin is live (§27 two-phase policy).
5. **Secret names** — README said `DEPLOY_*`; workflow uses `SSH_*` + `DEPLOY_PATH`.

### Recommendation for the base template
- Align default `deploy.yml` + README with §24, §27, §19 out of the box.
- Comment block at top of deploy workflow: prerequisites (.env on server), first deploy
  (sync_content + db:seed), steady state (exclude data/storage media).
- Add `feedback.md` to deploy excludes (§19 already lists it — ensure it's in template workflow).

---

## 51. WebTemplate branding leftovers — pre-launch grep still required

**Severity:** Medium — wrong tab title, seeder defaults, debug page title.

**Project:** SkyREVA (2026-07-31)

### Found in repo (not exhaustive)
| Location | Leftover |
|---|---|
| `.env.example` `APP_NAME` | `WebTemplate` |
| `SettingSeeder` `site_name` | `WebTemplate` |
| `data/header.json` `logo_alt` | `WebTemplate` |
| `data/home.json` subtitle | "modern web template…" |
| `package.json` `name` | `web-template` |
| `public/debug.php` title | `webTemplate debug` |
| `TemplateInit` / `TemplateDoctor` CLI strings | WebTemplate |
| `database/webtemplate.sql` | full dump with boilerplate data (deleted on project) |

Live DB rows seeded before rebrand **stay** until admin edit or SQL update —
`firstOrCreate` seeders won't overwrite.

### Recommendation for the base template
- §5 covers browser title; extend go-live grep to **seeders, data/*.json, package.json,
  debug.php, artisan command strings**.
- `template:init` default site name should come from `config('app.name')` with **no**
  hardcoded `'WebTemplate'` fallback in user-visible paths (dev-only prompt default OK).
- Consider `SettingSeeder` default `site_name` as empty or a placeholder that fails
  `template:doctor` until `template:init` runs.

---

## 52. SkyREVA deploy checklist addendum (2026-07-31)

| Check | Action |
|---|---|
| Header/subnav/footer readable | Dark chrome pages — nav text light gray/white |
| Subnav clicks scroll | Company → About, Services → Overview, etc. |
| Company + Insights load | No console `ReferenceError` from content.ts |
| No WebTemplate in UI | Tab title, login header, debug.php, settings |
| `VITE_APP_NAME` in CI build | deploy.yml `env` block set before `pnpm build` |
| AI paths excluded | `agents/`, `AGENTS.md`, `.claude/`, `_referance/` not on server |
| First deploy only | `sync_content=true` + SSH `db:seed --force`; `.env` pre-exists on host |
| Logo/favicon | Wire `site_logo` / `site_favicon` in PublicLayout when ready |

**Reminder:** Marketing copy in `content.ts` only updates after **`pnpm build`** and deploy
of `public/build/` — not via Page Content JSON panel.

