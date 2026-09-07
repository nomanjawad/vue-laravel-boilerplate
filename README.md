# webTemplate

Laravel 13 + Vue 3 + Inertia + Tailwind v4 boilerplate repositioned as a
**universal backend for service-based client websites** — a scaled-down,
WordPress-like CMS. Ships an admin panel, a public site, and a **toggleable
module system** so every feature (blog, testimonials, events, …) lives in its
own folder and can be turned on or off from the dashboard. Custom frontends
are built per client; the backend stays generic.

- **Backend:** Laravel 13 (PHP 8.3+)
- **Frontend:** Vue 3 (Composition API + `<script setup lang="ts">`) + Tailwind CSS v4
- **Bridge:** Inertia.js
- **Database:** MySQL 5.7+ / MariaDB 10.3+ (**no SQLite**)
- **Bundler:** Vite 8
- **Package managers:** Composer + **pnpm** (never npm)

---

## 1. Quick start

```bash
git clone <repo> my-project && cd my-project
composer install
pnpm install

cp .env.example .env               # set MySQL creds
php artisan key:generate

php artisan template:init          # interactive: site name, admin user, migrate + seed
php artisan serve                  # http://localhost:8000
pnpm run dev                       # Vite HMR
```

Admin: `/admin` with the credentials you set in `template:init`.

### Post-clone checklist

1. `.env` has MySQL creds (MAMP: `DB_PORT=8889`, `DB_USERNAME=root`, `DB_PASSWORD=root`).
2. `composer ide` — regenerates IDE helper stubs + TypeScript DTOs.
3. `php artisan template:doctor` — health check (extensions, DB, storage link, queue).
4. `php artisan optimize` — must pass cleanly (no CI enforces this — check it yourself).

---

## 2. Architecture

### 2.1 Modules — the load-bearing idea

Every feature is a **self-contained module** under `app/Modules/{Name}/`:

```
app/Modules/Testimonials/
  TestimonialsModuleServiceProvider.php   # auto-discovered
  module.php                              # manifest: permissions, nav, dependencies
  Http/Controllers/{Admin,Public}/
  Http/Requests/
  Models/
  Policies/
  Database/{Migrations,Factories,Seeders}/
  Routes/testimonials.php                 # module-owned routes
  Resources/js/Pages/{Admin,Public}/      # Vue pages resolved by Inertia
  Resources/js/Components/                # module-internal only — no cross-module imports
  Services/
  Tests/Feature/
```

**Toggle modules on/off from `/admin/modules`.** The `modules` DB table is
the source of truth; enabling runs migrations + seeders + syncs permissions;
disabling stops route registration on the next boot (data preserved);
uninstalling rolls back migrations and drops permissions.

**Fault isolation:** every module's `register()`/`bootModule()` runs inside
`rescue()`. A broken module is marked unhealthy in the registry and skipped —
it will never 500 the rest of the panel. Dashboard shows a red badge; the
admin can Reinstall from `/admin/modules`.

**`config/modules.php`** holds **virtual modules** — legacy v2 features
(users, settings, media, menus, blog, …) that use the classic Laravel
layout but participate in the module registry. New features use physical
modules.

### 2.2 Component layering (strict)

```
Atoms       →  Molecules  →  Organisms  →  Pages
```

- `resources/js/Components/Atoms/` — primitives (AppButton, AppInput, AppIcon…). No API calls, no `useForm()`.
- `resources/js/Components/Molecules/` — one concern from Atoms (AppFormField, AppFormSection, AppFloatingSave…).
- `resources/js/Components/Organisms/` — full features (FormShell, AppMediaPicker, AppBlockEditor, GlobalSearch, NotificationBell).
- `resources/js/Layouts/` — layout shells only (AdminLayout, PublicLayout, AuthLayout).
- `app/Modules/{X}/Resources/js/Components/` — module-internal Vue. **Never imported by another module.**
- Frontend override guide: [`docs/FRONTEND.md`](docs/FRONTEND.md).

The `NoCrossModuleImportsTest` used to enforce that automatically — after
removing `tests/` this contract is enforced by review only.

### 2.3 Layer conventions

- **Routes declare their keys per-route** (`{model:slug}` public, id-binding admin). **Never** add `getRouteKeyName()` to a model.
- **DTOs are typed once** with `spatie/laravel-data` + `#[TypeScript]`; `composer ide` regenerates `resources/js/types/types.d.ts`.
- **URLs in Vue are literal root-relative paths** (`/admin/testimonials`). Module sidebar entries declare a route *name* in their manifest; `ModuleManager::navFor()` resolves it to an href server-side.
- **`HandleInertiaRequests::PUBLIC_SETTINGS`** is the only path a settings key reaches the browser. Add cautiously — every entry is public on every page.
- **`Cache::remember` stores plain arrays**; the file cache can't round-trip Eloquent collections.
- **Old-MySQL safe migrations:** `varchar(191)` for unique indexes, no `TEXT`/`JSON` defaults.

### 2.4 Directory map

```
app/
  Console/Commands/            template:doctor, template:init, make:module, make:crud, import:wordpress
  Data/                        Core DTOs (AuthData, MenuItemData, ModulesSharedData, …)
  Http/Controllers/{Admin,Auth,Public}/
  Http/Middleware/             HandleInertiaRequests, AdminMiddleware, HandleRedirects
  Models/                      Legacy v2 models (Post, Menu, User, …)
  Modules/
    Core/                      ModuleManager, AbstractModuleServiceProvider, PermissionSyncer, EnsureModuleEnabled
    Testimonials|Faqs|Events   Sample physical modules
  Providers/                   ModulesServiceProvider (orchestrates all modules)
  Services/                    AdminSearchService, MediaService, SeoService, …

data/                          JSON content: pages/{slug}.json + header.json / footer.json
config/
  modules.php                  Virtual-module registry (legacy features)
  widgets.php                  Page widget type registry (serializable)
  template.php                 Feature flag fallback
resources/
  js/
    Layouts/                   Layout shells
    Pages/                     Core admin + public pages (module pages live under app/Modules)
    Components/{Atoms,Molecules,Organisms}/
    Composables/               useImageUrl, useShortcuts, useConfirm, usePermissions
    types/                     Generated: types.d.ts; hand-written: inertia.d.ts
  css/                         app.css (design tokens), admin.css (dark admin theme), pages/*.css
  views/                       Blade root + branded email templates

routes/                        Legacy v2 route files (module routes live in app/Modules/*/Routes)
stubs/                         Generator templates (make:module, make:crud) — do not delete
public/
  debug.php                    Token-gated recovery tool (set DEBUG_TOKEN in .env to enable)
```

---

## 3. Adding a feature

New features are physical modules. Don't add to `routes/admin.php` or
`app/Http/Controllers/Admin/`; use the generators.

```bash
# 1. Scaffold the folder + provider + manifest
php artisan make:module Newsletter --description="Email list capture and drip."

# 2. Stamp a CRUD resource inside it
php artisan make:crud Subscriber --module=Newsletter --slug --public
# Flags:
#   --slug          adds a unique slug column + slug field on Create/Edit pages
#   --soft-deletes  adds deleted_at + SoftDeletes trait
#   --media         accepts the flag but doesn't yet add a media_id column (manual for now)
#   --public        stamps a public controller (you wire the public route by hand)

# 3. Edit app/Modules/Newsletter/module.php — set dependencies, icon, searchable models

# 4. Toggle on
#    Visit /admin/modules and flip the switch. Migrations + permission sync run automatically.
```

**Generator stubs live in `stubs/module/` and `stubs/crud/`.** Edit them
per-project if the default shape doesn't fit — they're plain files.

**Icons in sidebar nav** come from `resources/js/Components/Atoms/AppIcon.vue`.
The built-in set is ~20 SVG paths in `AppIcon.vue`; if you set a manifest icon that
isn't in the map it falls back to `cube`. Extend the `paths` map in
`AppIcon.vue` to add more.

### Regenerate types after any change

```bash
composer ide
# = ide-helper:generate + ide-helper:models --nowrite + ide-helper:meta
#   + typescript:transform
```

Run this after adding models, migrations, or DTOs.

---

## 4. Feature flags (legacy virtual modules)

Virtual modules in `config/modules.php` back-compat the v2 flags:

```
FEATURE_BLOG=true
FEATURE_CAREERS=true
FEATURE_CASE_STUDIES=true
FEATURE_TEAMS=true
FEATURE_CONTACT_FORM=true
```

The `modules` DB table wins over these flags. `.env` is the fallback for
fresh installs where the DB isn't reachable yet.

---

## 5. Pages & content

### 5.1 JSON pages (widget editor)

Public pages are **one JSON file each** under `data/pages/{slug}.json`
(git-diffable; never the DB). Manage them at **/admin/pages**:

```json
{
  "title": "Home",
  "status": "published",
  "featured_image": "",
  "seo": {
    "title": "",
    "description": "",
    "noindex": false,
    "json_ld": ""
  },
  "widgets": [
    { "id": "w_…", "type": "hero", "visible": true, "data": { "title": "…" } }
  ]
}
```

Canonical / OG tags are **derived** in `resolveSeo()` (not stored as
editable overrides). `featured_image` feeds `og:image`. See `agents/skills/seo`
and `docs/FRONTEND.md` for the public render path.

- Widget types live in `config/widgets.php` (must stay serializable for
  `php artisan optimize`). Public components: `resources/js/Components/Widgets/`.
  Types regenerate via `php artisan widgets:types` → `resources/js/types/widgets.d.ts`.
- Route `/` = slug `home`; other published slugs render at `/{slug}` via
  `DynamicPageController`. Drafts 404.
- Collection widgets (testimonials, FAQs, team, latest posts) pull live
  module rows through `WidgetDataResolver`.
- `data/header.json` / `data/footer.json` — Header & Footer admin screen
  (`/admin/page-content/layout`); shared as the `layout` Inertia prop.
- Menus are DB-driven (locations, nesting, drag-drop) at `/admin/menus`.
- Page widget reorder in the admin uses ↑/↓ buttons (block-editor drag
  handles are not shipped).

Agent guides: `agents/skills/page-content`, `widgets`, `seo`; frontend
override walkthrough: [`docs/FRONTEND.md`](docs/FRONTEND.md).

### 5.2 Blog

Posts use a TipTap **block editor** (`AppBlockEditor`) storing HTML.
Categories are WP-style many-to-many with a default **Uncategorized** and
public archives at `/blog/category/{slug}`. Featured/OG images use the
media library picker.

### 5.3 Dynamic admin content

Blog, testimonials, events, FAQs, careers, case studies, team, menus,
settings, media, enquiries, subscribers — managed from `/admin`.

---

## 6. SEO

On-page SEO is RankMath-parity style (see `agents/skills/seo`):

- Shared `seo` prop from `HandleInertiaRequests::resolveSeo()` →
  `PublicLayout` `<Head>` (title template, canonical, OG, robots).
- Automatic JSON-LD: Organization, optional LocalBusiness, BlogPosting,
  BreadcrumbList, FAQPage (from FAQ widgets).
- SERP preview + content checklist in page/post editors.
- Sitemap: real `lastmod`, image entries, skips `noindex` pages/posts.
- **Go-live gate:** set `SEO_INDEXABLE=true` or every crawler gets
  `noindex` (meta + `X-Robots-Tag` + `robots.txt`). Confirm with
  `php artisan template:doctor --production`.

---

## 7. Performance

- **`AppImage`** — single public/widget image component (srcset from media
  variants, width/height, lazy by default, `eager` for LCP).
- Media pipeline records dimensions; run `php artisan media:backfill-dimensions`
  after migrate on existing installs.
- Dynamic pages preload the first hero/image for LCP.
- `public/.htaccess`: Gzip (`mod_deflate`) + optional Brotli; WebP/avif/woff
  in `ExpiresByType`.
- Full rules: `agents/skills/launch-readiness`.

---

## 8. Theming

Admin and public share one brand palette. Edit **Settings → Theme**:

- **Primary color** — hex; `App\Support\BrandPalette` expands it to the 7 CSS steps (`brand-50`…`900`). Steps 300/500 are lightness-clamped so links stay readable on the dark admin shell.
- **Font** — curated [bunny.net](https://fonts.bunny.net) list; Instrument Sans is the Vite default (no extra request). Other choices load a bunny stylesheet (preconnect already in `app.blade.php`).
- **Corner radius** — `sm` / `md` / `lg` → `--radius-card` / `--radius-button`.

Overrides are emitted as `:root{…}` **after** `@vite` in `resources/views/app.blade.php`. Saving theme settings busts the response cache via the `Setting` model. Defaults still live in `resources/css/app.css` `@theme` for builds without a DB.

### Ops extras (shipped)

- **Backups** — `spatie/laravel-backup` nightly DB dump (`backup:run --only-db` at 02:00, `backup:clean` at 03:00). Set `DB_DUMP_BINARY_PATH` on hosts where `mysqldump` is not on PATH (MAMP/cPanel).
- **Sentry** — optional. Set `SENTRY_LARAVEL_DSN` (and optionally `VITE_SENTRY_DSN` + `@sentry/vue`). `bootstrap/app.php` only wires Sentry when the package class exists.
- **CSP** — `ContentSecurityPolicy` middleware; off by default (`CSP_ENABLED=false`). Prefer `CSP_REPORT_ONLY=true` first.
- **Cookie consent** — public banner gates GA/GTM via `useConsentScripts` (ids format-validated).
- **Admin notifications** — `NotificationBell` in AdminLayout (CSRF meta required in `app.blade.php`).
- **Recovery** — `public/debug.php?t={DEBUG_TOKEN}` when `DEBUG_TOKEN` is set.

---

## 9. Deployment

### 9.1 GitHub Actions (recommended)

Publish a release or trigger manually:

- **Automatic:** publish a GitHub Release (Releases → "Draft a new release", tag `v*.*.*`) → `.github/workflows/deploy.yml` fires and deploys the release's tag. A bare `git tag` push does **not** deploy; releases marked *pre-release* are skipped.
- **Manual:** Actions → "Deploy to Production" → Run workflow (pick production/staging).

Required repo secrets (names must match `.github/workflows/deploy.yml`):

```
SSH_HOST            # ssh hostname
SSH_USERNAME        # ssh user
SSH_PORT            # ssh port (usually 22)
SSH_PRIVATE_KEY     # private key contents
DEPLOY_PATH         # absolute path on server
```

Also set Actions **variable** `VITE_APP_NAME` (inlined into the JS bundle at
build time — required or the visitor-facing app throws).

The workflow: checkout → `pnpm build` (with `VITE_APP_NAME`) →
`composer install --no-dev` → rsync over SSH → remote
`composer deploy` (migrate, RBAC seeders, storage:link, typescript +
widget types, optimize, responsecache:clear, doctor).

**rsync excludes** source-only trees such as `/resources/js/`,
`/resources/css/`, `/node_modules/`, `/storage/`, and agent docs — the
built `public/build/` assets ship instead. **`data/` is included** (page
JSON + header/footer). Production admin edits to those files are
overwritten on the next deploy unless committed back to the repo first.

### 9.2 Shared hosting (cPanel / SiteGround / Hostinger)

Requirements: PHP 8.3+, MySQL 5.7+/MariaDB 10.3+, extensions `mbstring bcmath pdo_mysql gd exif intl zip openssl curl fileinfo tokenizer xml`.

**One-time server setup:**

1. Upload the project (rsync or the host's Git deploy). Exclude `node_modules`, `vendor`, `.git`, `.env*`, and never overwrite server `storage/` (sessions, logs, `storage/framework/cache/data`).
2. `cd ~/webTemplate && composer install --no-dev --optimize-autoloader`
3. `cp .env.example .env` — set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, DB creds, `MAIL_*`. Prefer `LOG_STACK=daily` (14-day retention) over `single`, and keep `RESPONSE_CACHE_DRIVER=database` (file response-cache burns inodes).
4. `php artisan key:generate && php artisan migrate --force && php artisan storage:link`
5. `php artisan optimize`
6. Point the document root at `public/` (cPanel: Domains → Manage → Document Root; SiteGround: Site Tools → Domain → Manage → Document Root; Hostinger: hPanel → Domains → Manage).

**Every subsequent deploy:**

```bash
composer deploy
# = migrate --force
#   db:seed RoleAndPermissionSeeder + AdminUserSeeder
#   storage:link, typescript:transform, widgets:types
#   optimize, responsecache:clear
#   template:doctor --production --exit-zero
```

**Inode note:** the scheduler prunes expired `cache` table rows weekly. If
`template:doctor` warns about `storage/framework/cache/data`, flip
`RESPONSE_CACHE_DRIVER=database`, clear response cache, and delete leftover
files under that directory.

**Recovery tool:** `public/debug.php?t={DEBUG_TOKEN}` — token-gated,
pure-PHP page that checks extensions/DB/storage/log tail without booting
Laravel. Enable by setting `DEBUG_TOKEN` in `.env`; disable by unsetting.

**cPanel-specific:** no `--strip-comments` on `mysqldump` (older versions
choke). Set `DB_DUMP_BINARY_PATH=/usr/bin/mysqldump`. Some hosts silently
cache config; `optimize:clear` after every deploy.

**SiteGround-specific:** their PHP selector defaults to a version older
than 8.3 — flip it in Site Tools → DevOps → PHP Manager first. Ultrafast
PHP + Dynamic Cache should both be on.

### 9.3 Queue on shared hosting

No long-running worker needed. `routes/console.php` schedules
`queue:work --stop-when-empty --tries=3` every minute. Add the standard
cron:

```
* * * * * cd /home/user/webTemplate && php artisan schedule:run >> /dev/null 2>&1
```

### 9.4 Production go-live checklist

Things that have gone wrong on live deploys (from `feedback.md`) — walk this
list before pointing DNS at a new site:

**`.env` values**

- `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning` (not `debug`).
- `APP_URL` set, **no trailing slash**, matching the canonical host you'll
  serve from. Every SEO tag, sitemap link, and OG image URL is built from
  this — a mismatch shows the wrong host in Google previews.
- `APP_NAME` and `VITE_APP_NAME="${APP_NAME}"` both set. `pnpm build` fails
  loud if `VITE_APP_NAME` is unset, so a silent-wrong tab title can never
  ship.
- `SEO_INDEXABLE=true`. Default is `false`; leaving it produces a site with
  a `noindex, nofollow` meta tag and a blocking `robots.txt`. Invisible to
  Google.
- Real `MAIL_*` + `MAIL_FROM_ADDRESS`. Without these, contact-form and
  newsletter submissions silently fail. Test with a smoke send before opening.
- `RESPONSE_CACHE_ENABLED=true` is the default and is safe (Inertia-aware
  cache profile so XHR and full-page requests never collide). Keep
  `RESPONSE_CACHE_DRIVER=database` on shared hosting — the file driver fills
  `storage/framework/cache/data` and burns inodes.
- `LOG_STACK=daily` (or `daily,console` in local) so logs rotate with
  14-day retention instead of one unbounded `laravel.log`.

**Canonical host + HTTPS**

Force one host + https via `public/.htaccess`:

```apache
# force HTTPS + non-www → https://example.com
RewriteCond %{HTTP_HOST} ^www\. [NC,OR]
RewriteCond %{HTTPS} off
RewriteRule ^ https://example.com%{REQUEST_URI} [R=301,L]
```

If a CDN (Cloudflare, etc.) terminates SSL upstream, `%{HTTPS}` is `off`
at the origin — check `%{HTTP:X-Forwarded-Proto}` instead to avoid a
redirect loop.

**First-time seed (one-time, before the first automated deploy)**

The automated deploy step (composer `deploy` / GH Actions) re-runs only
the safe idempotent seeders (`RoleAndPermissionSeeder` + `AdminUserSeeder`)
on every release. Content-shaped seeders (`ModulesSeeder`, `MenuSeeder`,
`SettingSeeder`) run **once, manually**, on the very first production deploy:

```bash
php artisan db:seed --force
```

Never wire the full `db:seed` into recurring deploys — after an admin edits
`site_name` in `/admin/settings`, the next deploy would silently overwrite
it with the seeded default.

**Pre-launch grep**

Grep the deployed tree for leftover `WebTemplate` literals — every one
should be gone (project-specific copy-paste can reintroduce):

```bash
grep -rn "WebTemplate" resources/ app/ | grep -v /node_modules/
```

**Post-deploy health**

`php artisan template:doctor --production` prints a health report
(PHP/extensions, `.env`, DB, storage symlink, Vite manifest, queue, module
health). It runs automatically at the end of the deploy pipeline with
`--exit-zero`, so a failure surfaces in the log without aborting the deploy.

---

## 10. Conventions

- **`pnpm` never `npm`.**
- **MySQL only** — no SQLite in tests, config, or production.
- **No cross-module imports** — Atoms/Molecules/Organisms are shared; anything under `app/Modules/{X}/Resources/js/Components/` is module-private.
- **Uploads through `MediaService`** — the controller enforces a MIME whitelist (JPEG/PNG/WebP/GIF/PDF); the service double-checks. SVG excluded deliberately (script tags render inline).
- **Route keys per route**, never `getRouteKeyName()` on models.
- **`HandleInertiaRequests::PUBLIC_SETTINGS`** whitelists what's exposed to every visitor.
- **Anything committed to `main` must run `php artisan optimize` + `pnpm build` clean** —
  there's no CI workflow to catch a broken build, so verify both yourself before committing.

---

## 11. Useful commands

```bash
composer ide                        # regenerate IDE helpers + TS types
composer deploy                     # production deploy steps (also run by GH Actions)

php artisan template:doctor         # health check
php artisan template:init           # first-run interactive setup

php artisan make:module {Name}
php artisan make:crud {Model} --module={Name} [--slug --soft-deletes --media --public]

php artisan media:prune [--delete]
php artisan media:backfill-dimensions
php artisan import:wordpress {file.xml}
```

---

## 12. Reference: existing modules

**Physical (`app/Modules/`):**
- Testimonials
- FAQs (page-wise via `page_slug`; FAQ widget modes: current_page / picked / global)
- Events

**Virtual (`config/modules.php`):**
- users, settings (+ Cache panel), media, menus, page_content (Pages + Header/Footer)
- redirects, custom_code, subscribers, enquiries (contact form inbox)
- blog (posts, categories, tags)
- careers, case-studies, teams

Sidebar groups: Content → Collections → Inbox → Appearance → System.
All appear on `/admin/modules` with the same toggle/health/uninstall UX.
