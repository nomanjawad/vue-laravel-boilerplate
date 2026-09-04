# webTemplate — Agent guide

Guide for any AI agent working in this repo (Claude Code reads it via the
`CLAUDE.md` symlink).

Laravel 13 + Vue 3 + Inertia + Tailwind v4 boilerplate for small-to-medium
websites. Ships an admin panel, a public site, and a **toggleable module
system** — every feature (blog, testimonials, events, …) lives in its own
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
- `pnpm build` — **runs `vue-tsc --noEmit`** then Vite; no CI runs this automatically
  (no `.github/workflows/ci.yml` in this repo) — always run it yourself before
  declaring frontend work done.
- `php artisan typescript:transform` — regenerate TS types from `#[TypeScript]` DTOs
- `composer ide` — regenerate IDE helpers + TS types
- `php artisan optimize` must stay clean — run it yourself; nothing gates this automatically
- `php artisan template:init` — first-run setup (site name, admin user, migrate+seed)

## Agent skills

Task-focused guides live in `agents/skills/` (each is a `SKILL.md` with
name/description frontmatter): `create-module`, `admin-crud`, `page-content`,
`blog`, `modules-reference`, `settings-and-media`, `dev-workflow`,
`launch-readiness`, `seo`. Claude Code auto-loads them via the `.claude/skills`
symlink; other agents should read the relevant skill before working in that
area. Prefer consulting them over re-deriving conventions from the code.
**Read `launch-readiness` before adding any public-facing image or
third-party script, and before telling anyone a site is ready to launch** —
it's derived from a real production Lighthouse audit and covers the SEO
indexability gate, image sizing/caching/loading rules, and third-party embed
trade-offs. **Read `seo` for canonical/OG/title-template/schema/sitemap
behavior.**

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
- **JSON content pages.** Static pages live in `data/pages/{slug}.json` (widget
  editor); `header.json`/`footer.json` are layout-only. SEO is resolved globally
  in `HandleInertiaRequests::resolveSeo()` (title template, canonical, OG
  overrides, post/page meta) → shared `seo` prop → `PublicLayout` `<Head>`.
  See `agents/skills/seo/SKILL.md` for the RankMath-parity contract.
- **Settings** live in the `site_settings` table (grouped), edited via the tabbed
  `Admin/Settings/Index.vue`. Tabs are driven from the DB `group` column + a
  server-side field-meta map in `SettingController` (`SettingGroupData` DTO) —
  a new group appears as a new tab automatically. Theme tab is a Phase-8
  placeholder. SEO tab shows a read-only `SEO_INDEXABLE` banner when false.
  `SettingService::update()` only UPDATEs existing rows (whitelist-by-existence)
  — new keys need a seeded/migrated row or the save no-ops.
- **Cache panel** at `/admin/system/cache` clears per layer (pages / sitemap /
  settings / modules / redirects / views / all) — never `Cache::flush()`.
  Dashboard button is "Clear page cache" only.

---

## Recently completed (context — don't redo)
- **`launch-readiness` skill + Lighthouse fold-back** (2026-08-11), from a real production
  audit (skyhealthpro.com: Performance 82-88, Best Practices 58, SEO 69, Accessibility 91):
  - New skill `agents/skills/launch-readiness/SKILL.md` — the SEO indexability go-live gate,
    image sizing/caching/loading rules, third-party embed trade-offs (chat widgets cost real
    Best Practices points via cookies + bfcache and that's not a bug to chase), and how to
    run Lighthouse without mistaking browser-extension noise for real findings.
  - `public/.htaccess` — `image/webp` (+ `avif`, `font/woff`) added to `ExpiresByType`. Every
    `MediaService`-processed image is WebP; without this entry every content image on every
    site built from this template was served with **zero** cache lifetime (measured on the
    live site: `cacheLifetimeMs: 0` on every uploaded photo while JS/CSS cached fine).
  - `TemplateDoctor::checkIndexability()` — `--production` doctor runs now print a loud
    warning when `SEO_INDEXABLE` isn't `true`. Root cause of the SEO 69 score: the audited
    site had been live with the default `SEO_INDEXABLE=false` never flipped, so every crawler
    got `noindex, nofollow` from three independent signals (meta tag, `X-Robots-Tag`,
    `robots.txt`) — nothing in the deploy pipeline had ever surfaced this.
  - `resources/views/app.blade.php` — `<link rel="preconnect" href="https://fonts.bunny.net">`
    (the font origin `laravel-vite-plugin`'s `bunny()` helper always loads from).
  - Stock public pages (`Blog`, `CaseStudies`, `About`, `Home` featured-* components)
    — added `loading="lazy" decoding="async"` to grid/list images, and
    `loading="eager" fetchpriority="high"` to the one above-the-fold featured image on each
    `*/Show.vue` detail page (previously none of these had any loading discipline at all).
- **feedback.md fold-back batch 2** (§15, §19, §20, §24, §25, §31, §33, §34, §40, §41, §43 —
  the earlier §1–§11 batch was already folded back in prior work):
  - `resources/css/app.css` — added `@source` for `app/Modules/*/Resources/js/**/*.vue`;
    physical modules live outside `resources/` so the default glob never reached them.
  - `.github/workflows/deploy.yml` — build step now requires `vars.VITE_APP_NAME` (fails
    loudly instead of shipping a bundle that throws in every visitor's browser); rsync
    excludes agent/dev-only paths (`agents/`, `.claude/`, `AGENTS.md`, `CLAUDE.md`,
    `feedback.md`, `_referance/`, `.cursor/`, `.codex/`); post-deploy wipes stale
    `bootstrap/cache/*.php` before any artisan command runs (a removed package's cached
    provider list used to survive indefinitely since that dir is rsync-excluded).
  - `TemplateDoctor::checkEnv()` — checks `config('app.key')` instead of raw `env()` reads;
    DB reachability (already checked elsewhere) is the real gate, so non-standard `.env`
    layouts (sockets, etc.) no longer false-flag a working setup.
  - `PreventSearchIndexing` — `/admin*` is always `noindex, nofollow` now, not only while
    `SEO_INDEXABLE=false`; going live previously left the admin panel with no noindex
    header at all. `HandleInertiaRequests::resolveSeo()`'s `noindex` calc now also ORs in
    `! config('template.indexable')` so the `<meta name="robots">` tag agrees with the
    header instead of a staging build still rendering `index,follow` client-side.
  - `Menu::normalizeUrl()` (+ backfill migration) — internal URLs saved without a leading
    slash now get one; external `http(s)://` and `#anchor` links are untouched.
    `MenuController` applies it on store/update and loosened `sort_order` to `nullable`
    (Vue's `.number` modifier emits `''` on a cleared field, which 422'd with zero visible
    error before — `Admin/Menus/Index.vue` now renders `newForm`/`editForm` error boxes).
  - `AppServiceProvider::boot()` — `URL::forceScheme('https')` in production / when
    `APP_URL` is https; `bootstrap/app.php` adds `trustProxies(at: '*')` so this and
    `request()->secure()` reflect reality behind a TLS-terminating proxy (cPanel,
    Cloudflare) instead of emitting mixed-content `http://` asset URLs.
  - Media alt-text is now editable post-upload: `PUT /admin/media/{media}` +
    `MediaController::update()` + a click-to-edit modal on `Admin/Media/Index.vue`
    (permission-gated on `media.update`, which existed in `config/modules.php` but had no
    route/UI behind it before).
  - Not folded back (client-specific, no equivalent in this base template): §14
    (`useSiteSettings`/shortcode service), §17 (settings-tab module gating), §28–30/§45–49
    (SkyREVA/SkyHealth-only components — `SiteImage`, `v-reveal`, subnav, a custom
    `Services` module, menu consolidation). The remaining low-severity doc-only items
    (§4, §16, §21, §23, §26, §42, §44) are guidance for future work, not code gaps here.
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
- **Tabbed Site Settings** (`Admin/Settings/Index.vue`): General / Contact / Social /
  SEO & Analytics; added `site_logo`, `site_favicon`; tagline reuses
  `site_description`; WhatsApp moved to Social; migration + seeder backfill.
- **Media-picker contract fix:** `MediaData` DTO flashed from `MediaController::store`,
  forwarded through `HandleInertiaRequests` + `FlashData` so `AppMediaPicker` updates its
  v-model on upload.
- **Root-relative `Media::url`** so image previews load regardless of serving host/port.
- Committed on `main` as `ed6e428`.
