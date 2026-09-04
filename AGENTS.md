# webTemplate — Agent guide

Guide for any AI agent working in this repo (Claude Code reads it via the
`CLAUDE.md` symlink).

Laravel 13 + Vue 3 + Inertia + Tailwind v4 boilerplate repositioned as a
**universal backend for service-based client websites** — a scaled-down,
WordPress-like CMS. Ships an admin panel, a public site, and a **toggleable
module system** — every feature (blog, testimonials, events, …) lives in its
own folder and can be turned on/off from the dashboard. Custom frontends are
built per client; the backend stays generic.

## Stack
- **Backend:** Laravel 13 (PHP 8.3+), MySQL only (**no SQLite**)
- **Frontend:** Vue 3 (`<script setup lang="ts">`) + Tailwind CSS v4, Vite 8
- **Bridge:** Inertia.js v3 (no Ziggy — frontend uses literal root-relative paths; module nav route names resolve to hrefs server-side in `ModuleManager::navFor()`)
- **Package managers:** Composer + **pnpm** (never npm)
- **Key libs:** spatie/laravel-data (DTOs), spatie/laravel-permission (roles), Intervention Image (media), propaganistas/laravel-phone

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
`widgets`, `blog`, `modules-reference`, `settings-and-media`, `dev-workflow`,
`launch-readiness`, `seo`. Claude Code auto-loads them via the `.claude/skills`
symlink; other agents should read the relevant skill before working in that
area. Prefer consulting them over re-deriving conventions from the code.
**Read `launch-readiness` before adding any public-facing image or
third-party script, and before telling anyone a site is ready to launch.**
**Read `seo` for canonical/OG/title-template/schema/sitemap behavior.**
**Read `widgets` when adding or changing page widget types.**

## Architecture & conventions (the non-obvious rules)
- **Module system.** Physical modules live in `app/Modules/{Name}/` (with `rescue()`
  fault isolation); "virtual modules" are declared in `config/modules.php`. The
  **admin sidebar is generated from each module's `nav` array** (permission-filtered
  server-side) — it is NOT hardcoded in `AdminLayout.vue`. Groups:
  `content` / `collections` / `inbox` / `appearance` / `system`. Optional
  `badge` = class-string invokable (must stay serializable — no closures).
  Permissions are synced from the `permissions` map by `PermissionSyncer`.
- **DTOs → TypeScript.** `app/Data/*.php` classes marked `#[TypeScript]` auto-generate
  `resources/js/types/types.d.ts` (`App.Data.*`). Change a DTO → run
  `php artisan typescript:transform`. spatie/laravel-data **silently drops unknown
  keys**, so a field must exist on the DTO to cross to the browser.
- **Inertia shared props** are built in `app/Http/Middleware/HandleInertiaRequests.php`
  (`auth`, `modules`, `flash`, `menus` [nested tree], `layout` [header/footer JSON],
  `settings`, `seo`, `organizationJsonLd`, `localBusinessJsonLd`, …).
- **`PUBLIC_SETTINGS` whitelist** (in `HandleInertiaRequests`) is the ONLY path a
  `site_settings` key reaches the browser — the table may hold secrets. Never widen it
  to a key that isn't safe on a public page. Theme keys are **not** on the whitelist;
  they reach the browser via Blade CSS emit in `app.blade.php`.
- **Component layering:** Atoms → Molecules → Organisms → Pages; no cross-module imports.
  Reuse `AppFormField`, `AppInput`, `AppTextarea`, `AppMediaPicker`, `AppBlockEditor`,
  `AppFloatingSave`, `AppImage`, `FormShell`, etc.
- **Media:** `MediaController`/`MediaService` enforce a MIME whitelist (JPEG/PNG/WebP/GIF/PDF);
  **SVG is deliberately excluded** (inline scripts). Images are WebP-converted + EXIF-stripped
  with `md`/`thumb` variants + width/height. `Media::url` returns a **root-relative** path
  for app-origin assets. Public/widget images go through **`AppImage`** (srcset, dims,
  lazy/eager) — see `launch-readiness`.
- **JSON content pages.** One file per page under `data/pages/{slug}.json` with
  `title` / `status` / `seo` / `widgets[]`. Edited at `/admin/pages` (widget editor).
  Widget types are registered in `config/widgets.php` (serializable); public render
  is `DynamicPageController` → `Public/DynamicPage.vue` → `Components/Widgets/*`.
  Collection widgets resolve via `WidgetDataResolver`. `data/header.json` /
  `data/footer.json` are layout-only (Header & Footer admin screen) → `layout`
  shared prop. SEO is resolved globally in `HandleInertiaRequests::resolveSeo()`
  (title template, canonical, OG overrides, page/post meta) → shared `seo` prop →
  `PublicLayout` `<Head>`. See `page-content`, `widgets`, and `seo` skills.
- **Settings** live in the `site_settings` table (grouped), edited via the tabbed
  `Admin/Settings/Index.vue`. Tabs are driven from the DB `group` column + a
  server-side field-meta map in `SettingController` (`SettingGroupData` DTO) —
  a new group appears as a new tab automatically. Groups: General / Contact /
  Social / SEO & Analytics / Theme. SEO tab shows a read-only `SEO_INDEXABLE`
  banner when false. `SettingService::update()` only UPDATEs existing rows
  (whitelist-by-existence) — new keys need a seeded/migrated row or the save no-ops.
- **Cache panel** at `/admin/system/cache` clears per layer (pages / sitemap /
  settings / modules / redirects / views / all) — never `Cache::flush()`.
  Response cache uses **`RESPONSE_CACHE_DRIVER=database`** (inode-frugal on
  shared hosting). Dashboard button is "Clear page cache" only.
- **Theme.** Settings → Theme (`theme_primary_color` / `theme_font` /
  `theme_radius`) → `BrandPalette` + `Theme` emit `:root` CSS after `@vite`
  in `app.blade.php` (admin + public synced).

## TODO

_(none — Phase 0–11 of the 2026-09-04 CMS roadmap are complete.)_

---

## Recently completed (context — don't redo)

- **Universal service-site CMS repositioning (2026-09-04 roadmap, Phases 0–11).**
  Ecommerce removed; Media Library v2 (browse + bulk + `media:prune` + logo/favicon);
  WP-style sidebar groups + nav badges; JSON pages under `data/pages/{slug}.json`
  with widget editor (`config/widgets.php`, `WidgetDataResolver`, `AppBlockEditor`,
  `AppFloatingSave`); WP-style blog categories + TipTap block editor; nested
  drag-drop menus + header/footer layout; Enquiries inbox + page-wise FAQs +
  phone validation; per-layer cache panel + group-driven settings tabs; RankMath-
  parity SEO pack (`seo` skill); Theme settings synced admin↔public; Blade/inode
  hygiene (`RESPONSE_CACHE_DRIVER=database`); Performance pack (`AppImage`, gzip/
  brotli, LCP preload, media dimensions). Docs/skills updated to match (this guide,
  README, `agents/skills/*` including new `widgets` + `seo`).
- Older fold-backs (Lighthouse/`launch-readiness`, feedback.md batch 2, early
  Page Content panel → later superseded by the widget Pages system, tabbed
  Settings, media-picker flash contract, root-relative `Media::url`) remain in
  git history; do not reintroduce shop, flat `data/home.json` page bodies, or
  `page_metas` as the SEO source.
