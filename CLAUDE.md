# webTemplate — Claude Code project guide

Laravel 13 + Vue 3 + Inertia + Tailwind v4 boilerplate for small-to-medium
websites. Ships an admin panel, a public site, and a **toggleable module
system** — every feature (blog, shop, testimonials, events, …) lives in its own
folder and can be turned on/off from the dashboard.

## Stack
- **Backend:** Laravel 13 (PHP 8.3+), MySQL only (**no SQLite**)
- **Frontend:** Vue 3 (`<script setup lang="ts">`) + Tailwind CSS v4, Vite 8
- **Bridge:** Inertia.js v3 (no Ziggy — frontend uses literal root-relative paths; module nav route names resolve to hrefs server-side in `ModuleManager::navFor()`)
- **Package managers:** Composer + **pnpm** (never npm)
- **Key libs:** spatie/laravel-data (DTOs), spatie/laravel-permission (roles), Intervention Image (media)

## Commands
- `composer dev` — serve + queue + logs (pail) + Vite, all at once
- `php artisan serve` + `pnpm run dev` — app + HMR (dev often runs on :8001 if :8000 is taken)
- `pnpm build` — **runs `vue-tsc --noEmit`** then Vite; the frontend typecheck is CI-enforced
- `php artisan typescript:transform` — regenerate TS types from `#[TypeScript]` DTOs
- `composer ide` — regenerate IDE helpers + TS types
- `php artisan optimize` must stay clean (CI gate)
- `php artisan template:init` — first-run setup (site name, admin user, migrate+seed)

## Architecture & conventions (the non-obvious rules)
- **Module system.** Physical modules live in `app/Modules/{Name}/` (with `rescue()`
  fault isolation); "virtual modules" are declared in `config/modules.php`. The
  **admin sidebar is generated from each module's `nav` array** (permission-filtered
  server-side) — it is NOT hardcoded in `AdminLayout.vue`. Permissions are synced
  from the `permissions` map by `PermissionSyncer`.
- **DTOs → TypeScript.** `app/Data/*.php` classes marked `#[TypeScript]` auto-generate
  `resources/js/types/types.d.ts` (`App.Data.*`). Change a DTO → run
  `php artisan typescript:transform`. spatie/laravel-data **silently drops unknown
  keys**, so a field must exist on the DTO to cross to the browser.
- **Inertia shared props** are built in `app/Http/Middleware/HandleInertiaRequests.php`
  (`auth`, `modules`, `flash`, `menus`, `settings`, `seo`, …).
- **`PUBLIC_SETTINGS` whitelist** (in `HandleInertiaRequests`) is the ONLY path a
  `site_settings` key reaches the browser — the table may hold secrets. Never widen it
  to a key that isn't safe on a public page.
- **Component layering:** Atoms → Molecules → Organisms → Pages; no cross-module imports.
  Reuse `AppFormField`, `AppInput`, `AppTextarea`, `AppMediaPicker`, `FormShell`, etc.
- **Media:** `MediaController`/`MediaService` enforce a MIME whitelist (JPEG/PNG/WebP/GIF/PDF);
  **SVG is deliberately excluded** (inline scripts). Images are WebP-converted + EXIF-stripped.
  `Media::url` returns a **root-relative** path for app-origin assets (works on any
  host/port + CSP `img-src 'self'`); external CDN/S3 URLs stay absolute. Consumers that
  need an absolute URL (og:image, sitemap) promote via `url()`.
- **JSON content pages.** Static pages read `data/*.json` through `App\Services\JsonDataService`
  (no cache in debug; mtime-keyed cache in prod). Currently: `home`, `about`, `contact`,
  `header`, `footer`. Public controllers pass `data` to the Inertia page; SEO is resolved
  globally in `HandleInertiaRequests::resolveSeo()` → shared `seo` prop → `PublicLayout` `<Head>`.
- **Settings** live in the `site_settings` table (grouped), edited via the tabbed
  `Admin/Settings/Index.vue`. `SettingService::update()` only UPDATEs existing rows
  (whitelist-by-existence) — new keys need a seeded/migrated row or the save no-ops.

---

## TODO

### 1. Wire logo/favicon into the public site  ← next up
`site_logo` / `site_favicon` are now stored + exposed via `PUBLIC_SETTINGS`, but nothing
renders them. Add `<link rel="icon">` (favicon) and use `site_logo` in `PublicLayout.vue`
header. (Flagged during the Settings work; not yet done.)

---

## Recently completed (context — don't redo)
- **Page Content Management panel.** Admin UI to edit `data/*.json` (home/about/contact/
  header/footer) — content stays in JSON, never pushed to the DB.
  - `JsonDataService::put()` — validating, atomic (`tmp` + `rename`) write with
    `JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`; key order and
    value types round-trip because PHP assoc arrays preserve insertion order.
  - `Admin\PageContentController` (index/update) + `Admin/PageContent/Index.vue`: a
    **Pages** tab (home/about/contact, each with a dedicated SEO section — title,
    description, `AppMediaPicker` for `og_image`) and a **Header/Footer** tab (layout
    blocks, no SEO). One `useForm` per file, all created up front, so switching tabs never
    drops unsaved edits.
  - `JsonContentEditor.vue` (Organism) — generic recursive editor for the non-SEO part of
    each file's JSON: strings→text/textarea (textarea if >80 chars or multiline),
    booleans→`AppSwitch`, numbers→number input, arrays of objects→add/remove/reorder
    cards, arrays of strings→repeatable lines, nested objects→groups.
  - New core virtual module `page_content` (`page_content.view`/`.update` permissions,
    **Pages** nav entry) replaces the retired `page_metas` module: its config block, routes,
    `PageMetaController`, and `Admin/PageMetas/Index.vue` are removed. `SeoService::
    getMetaForRoute()` (now dead) removed too. The `page_metas` DB table/model/seeder are
    left in place, untouched and unused, per the non-destructive-removal decision.
  - `HandleInertiaRequests::resolveSeo()` now reads the `seo` block from the matching
    page's JSON (route name → home/about/contact) instead of the `page_metas` table,
    falling back to site settings on empty fields (`?:`, not `??` — the JSON editor writes
    `""` for a cleared field, not `null`).
  - `data/home.json`, `about.json`, `contact.json` each gained a `seo: { title,
    description, og_image }` block, seeded from the old `PageMetaSeeder` values so public
    SEO output didn't regress on cutover.
  - Gotcha hit during build: Inertia's `useForm()` reserves the key `data` (it's a method
    on the form instance) — the form field is named `content` instead, and feeding the
    recursive `JsonValue` type into `useForm`'s generic blew up `vue-tsc`
    ("Type instantiation is excessively deep"); the form's generic is kept as
    `Record<string, any>` and helper functions cast to the recursive `JsonObject` type at
    the point of use.
- **Tabbed Site Settings** (`Admin/Settings/Index.vue`): General / Contact / Social / Shop /
  SEO & Analytics; added `site_logo`, `site_favicon`, `shop_location`; tagline reuses
  `site_description`; WhatsApp moved to Social; migration + seeder backfill.
- **Media-picker contract fix:** `MediaData` DTO flashed from `MediaController::store`,
  forwarded through `HandleInertiaRequests` + `FlashData` so `AppMediaPicker` updates its
  v-model on upload.
- **Root-relative `Media::url`** so image previews load regardless of serving host/port.
- Committed on `main` as `ed6e428`.
