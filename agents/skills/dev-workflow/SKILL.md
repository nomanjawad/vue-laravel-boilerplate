---
name: dev-workflow
description: Commands, build gates, caching layers, and repo-wide guardrails for this boilerplate — composer/pnpm scripts, template:init/doctor, typescript:transform, response cache, activity log, middleware stack, MySQL constraints. Use at the start of any task to know how to run, verify, and not break the project.
---

# Dev workflow & guardrails

## Commands

- `composer dev` — serve + queue + logs (pail) + Vite together (dev often on
  :8001 if :8000 is taken).
- `pnpm build` — runs `vue-tsc --noEmit` THEN `vite build`; the typecheck is
  the CI gate. Always run before declaring frontend work done.
- `php artisan optimize` must stay clean (duplicate route names / view
  compile errors fail it).
- `composer ide` — ide-helpers + `php artisan typescript:transform` (DTO →
  `resources/js/types/types.d.ts`). Run after changing any `#[TypeScript]`
  DTO in `app/Data` or a module's `Data/`.
- `composer module:enable {key}` / `module:disable {key}` — module lifecycle
  from CLI (same as /admin/modules).
- `php artisan template:init` — interactive first-run (site name, feature
  flags, admin user, migrate+seed). `php artisan template:doctor` — health
  check with exact fixes (extensions, DB, storage link, Vite manifest, queue).
- `composer deploy` — migrate, RBAC + admin seeders, storage:link,
  typescript:transform, optimize, responsecache:clear, doctor.
- Package managers: Composer + **pnpm only** (never npm). If pnpm errors with
  "Unexpected store location", the node_modules was installed by a different
  pnpm major — use the matching binary rather than reinstalling blindly.

## Hard constraints

- **MySQL only, no SQLite.** Old-MySQL-safe migrations: `varchar(191)` for
  unique/indexed strings (utf8mb4 ceiling), no TEXT/JSON column defaults.
- **File-cache-safe caching**: `Cache::remember` must store plain arrays —
  Eloquent models/Collections don't round-trip the file driver (see
  `Redirect::map()`, `ModuleManager::registry()` for the pattern).
- **No tests exist**: no `tests/`, no phpunit.xml, no Pest. Don't run
  `php artisan test` and don't claim tests pass. Verification =
  `pnpm build` + `php artisan optimize` + exercising the feature.
- **Frontend URLs are literal root-relative paths** — no route() helper in
  JS (Ziggy removed). PHP keeps named routes; module nav route names resolve
  server-side in `ModuleManager::navFor()`.
- Inertia `useForm()` reserves the key `data` — name form fields `content`
  or similar.

## Caching layers (know which one is stale)

1. **Response cache** (spatie/responsecache, `responsecache` middleware on
   public GET routes, 7-day TTL): busted automatically by the
   `ClearsResponseCache` model trait, manually by
   `php artisan responsecache:clear` or POST /admin/cache/clear. NEVER cache
   routes rendering session data (cart, auth, form errors) — use
   `doNotCacheResponse`.
2. **Laravel optimize caches** (config/route/view) — `optimize:clear` in dev
   after route/config edits.
3. **App caches**: `site_settings` (1h), `modules.registry` (forever),
   `redirects.map` (1h), `json_data_*` (mtime-keyed), `sitemap.xml` (24h) —
   all bust on their own writes.

## Request pipeline (bootstrap/app.php)

`HandleRedirects` prepended globally (301/302 map before routing, logs 404s
to `not_found_logs`); `PreventSearchIndexing` appends `X-Robots-Tag: noindex`
unless `SEO_INDEXABLE=true`; web group gets `HandleInertiaRequests` (shared
props: auth, modules, flash, menus, settings, enabledFeatures, cartCount,
seo, organizationJsonLd) + `ContentSecurityPolicy` (opt-in via CSP_ENABLED).
Admin = `['web','auth','admin']` + prefix `/admin`; `admin` middleware
requires role super-admin/admin/editor; fine-grained access via
`can:{resource}.{action}` route middleware + policies.

## Audit & auth notes

- Content models use `LogsContentActivity` (spatie activitylog, dirty-only)
  → visible at /admin/audit-log. Logins/logouts/failures logged too
  (passwords stripped).
- Users admin has escalation guards: only super-admins can grant/edit
  super-admin; you can't delete your own account. One role per user via the UI.
- No public registration — /register intentionally removed (login +
  password reset only).
