# AGENT.md — webTemplate v3 Handoff

This document is for the **next agent** picking up the modular usability upgrade. Read this top-to-bottom before touching code.

The full plan lives at `/Users/nomanjawad/.claude/plans/melodic-prancing-penguin.md`. Cross-reference it for phase scope; this file tracks **what's done, what's broken, what's next**.

---

## 1. Project snapshot

- **Stack**: Laravel 13 + Vue 3 + Inertia + Tailwind v4, MySQL-only.
- **Branch**: `main`. Uncommitted changes may exist — do NOT commit unless the user asks.
- **Package manager**: `pnpm` (never `npm`). Composer for PHP.
- **Working dir**: `/Users/nomanjawad/Working File/webTemplate`.
- MAMP must be running for DB-dependent verification. The user starts it themselves — do not auto-launch.
- **Gate**: `php artisan test && php artisan optimize && pnpm build` — all three must pass green (currently **23/23 tests**).

## 2. Architectural pillars (do not violate)

1. **Every feature is a self-contained module** under `app/Modules/{Name}/`.
2. **Modules toggle on/off from `/admin/modules`** (DB-backed; `config('template.features.*')` is fallback only).
3. **Fault isolation**: a broken module must never 500 the rest of the panel — all `register()`/`bootModule()` calls are `rescue()`-wrapped.
4. **No `build-deploy.sh`** — GitHub Actions deploy only.
5. **Strict layering**: Atoms → Molecules → Organisms (universal). Module-internal Vue lives under the module folder. No cross-module imports.
6. **v2 conventions preserved**: route keys declared per-route (never `getRouteKeyName()` on models), settings whitelist, smoke-test gate, `Cache::remember` stores plain arrays.

## 3. Phase status

| Phase | Status | Notes |
|------|--------|-------|
| A — Module system | DONE | Registry, manifest, providers, fault isolation, generators all in place |
| B — Atomic components | DONE | Atoms/Molecules/Organisms created; `Posts/Edit.vue` migrated as reference |
| C — TypeScript + DTOs + Ziggy | DONE | Transformer fixed; 16+ DTOs emit to `types.d.ts`; Ziggy + `vue-tsc` in build; `composer ide` + `composer deploy` wired |
| D — RBAC enforcement | DONE | `Gate::before` super-admin bypass; `AdminMiddleware` allows `super-admin`; editor excludes core perms; `module:` middleware on blog/shop routes |
| E — Toasts/confirms/shortcuts | DONE | `FlashToaster`, `ConfirmDialog`, `useConfirm`, `useShortcuts` wired; `/` opens global search |
| F — GitHub Actions deploy | DONE | `.github/workflows/deploy.yml` + `public/debug.php` + host-specific deploy docs |
| G — `template:doctor` | DONE | Renamed `fail()`→`failed()` to avoid `Command::fail()` collision; wraps `Schema::hasTable` in try/catch |
| H — Mail/notifications/search/queue | DONE | Branded mail, `NotificationBell`, queue scheduler, **global search** (`AdminSearchService` + `<GlobalSearch>`) |
| I — Papercuts | DONE | `CONTRIBUTING.md`, PR template, `composer module:enable/disable` scripts |
| J — Verification | PENDING | Run all 15 verification steps from the plan with MAMP up |

## 4. Session summary (what changed recently)

### Phase C — TypeScript + DTOs + Ziggy

- **Fixed** `TypeScriptTransformerServiceProvider` — removed invalid `ReflectionTransformedProvider`; uses `LaravelTypeScriptTransformerExtension` + `LaravelDataTypeScriptTransformerExtension`. `php artisan typescript:transform` now emits all `#[TypeScript]` DTOs.
- **Core DTOs** in `app/Data/`: `AuthData`, `UserData`, `FlashData`, `SeoData`, `SettingsData`, `MenuItemData`, `MenusData`, `ModuleNavEntry`, `ModulesSharedData`, `PostData`, `CategorySummaryData`, `TagSummaryData`, `UserSummaryData`, `SearchResultData`, `SearchGroupData`.
- **`HandleInertiaRequests`** shares DTO-shaped props (`auth`, `flash`, `modules`, `menus`, `settings`, `seo`).
- **`PostController`** returns `PostData` / summary DTOs; index uses `->through(PostData::fromModel(...))`.
- **Ziggy** at `resources/js/types/ziggy.js` + `ziggy.d.ts`; `ZiggyVue` in `app.js`; `AdminLayout` + `Posts/Edit.vue` use `route()`.
- **Tooling**: `pnpm build` = `vue-tsc --noEmit && vite build`; `composer ide` runs `typescript:transform` + `ziggy:generate`; `composer deploy` runs both before `optimize`.
- **Types**: `resources/js/types/inertia.d.ts` (shared `PageProps`), `resources/js/env.d.ts`.
- **Reference page**: `Posts/Edit.vue` is `<script setup lang="ts">` with `App.Data.*` props. CRUD stubs default to TS + `route()`.
- **Pitfall fixed**: `SettingsData::fromArray()` caused infinite recursion with Spatie Data — use `SettingsData::from()` only.

### Phase E — Shortcuts

- **`useShortcuts.js`**: `g h` (home), `g d` (dashboard), `g p` (posts), `/` (global search), `?` (help modal).
- Wired in `AdminLayout.vue` with keyboard shortcut help overlay.

### Phase D — RBAC fixes (during verification)

- **`AdminMiddleware`** now allows `super-admin` role (was 403 on `/admin` for super-admins).
- **`PermissionSyncer`** excludes core-module permissions from the `editor` role.
- **`routes/admin-blog.php`** / **`admin-shop.php`** wrapped in `module:blog` / `module:shop` middleware (disabled modules return 404, not 403).
- **`AdminRoutesSmokeTest`**: `PageMeta::firstOrCreate`, `getStatusCode()` for StreamedResponse.

### Phase H — Global search

- **`AdminSearchService`** — scans each enabled module's `searchable` manifest; top 5 per group; `$user->can()` gated; super-admin bypass.
- **`SearchController@index`** — `GET /admin/search?q=…` (route: `admin.search.index`).
- **`<GlobalSearch>`** organism — modal in `AdminLayout`; debounced fetch; arrow/enter/esc navigation; top-bar Search button.
- **`config/modules.php`**: Order searchable uses `customer_email` (not `email`).
- **Tests**: `tests/Feature/AdminSearchTest.php` (5 tests).

## 5. Key files (touch points)

**Module core**
- `app/Modules/Core/ModuleManager.php` — singleton; `enabled()`, `enable()`, `disable()`, `markUnhealthy()`, `summary()`, `navFor()`. Falls back to config when DB unreachable.
- `app/Modules/Core/AbstractModuleServiceProvider.php`
- `app/Modules/Core/VirtualModuleProvider.php` — wraps v2 features without folder migration
- `app/Modules/Core/PermissionSyncer.php` — editor gets content perms only (core excluded)
- `app/Modules/Core/Middleware/EnsureModuleEnabled.php`
- `app/Providers/ModulesServiceProvider.php` — discovery + fault-isolation wrapping
- `config/modules.php` — virtual module definitions + `searchable` entries per module
- `database/migrations/2026_06_30_120000_create_modules_table.php`
- `database/seeders/ModulesSeeder.php`
- `app/Modules/Core/Http/Controllers/ModulesController.php`
- `resources/js/Pages/Admin/Modules/Index.vue`

**Generators**
- `app/Console/Commands/MakeModule.php`
- `app/Console/Commands/MakeCrud.php` — stamps full CRUD; patches manifest nav+perms; appends to `PublicRoutesSmokeTest::PARAMS`
- Stubs: `stubs/module/`, `stubs/crud/` — Vue pages default to `lang="ts"` + Ziggy `route()`

**Inertia shared**
- `app/Http/Middleware/HandleInertiaRequests.php` — shares DTO-shaped `auth`, `flash`, `modules`, `menus`, `settings`, `seo`
- `resources/js/Layouts/AdminLayout.vue` — sidebar from `modules.nav`; `<GlobalSearch>`, `<NotificationBell>`, shortcuts

**RBAC**
- `app/Providers/AppServiceProvider.php` — `Gate::before` super-admin bypass
- `app/Http/Middleware/AdminMiddleware.php` — `super-admin`, `admin`, `editor`
- `database/seeders/RoleAndPermissionSeeder.php` — ensures roles, delegates to `PermissionSyncer`
- All admin route files use per-action `can:resource.action` middleware; blog/shop also use `module:{key}`

**TypeScript + Ziggy**
- `app/Providers/TypeScriptTransformerServiceProvider.php` — working; scans `app/Data` + `app/Modules`
- `app/Data/*.php` — all `#[TypeScript]` DTOs
- `resources/js/types/types.d.ts` — generated; do not hand-edit
- `resources/js/types/ziggy.js` + `ziggy.d.ts` — generated route map
- `resources/js/types/inertia.d.ts` — shared `PageProps` typing
- `tsconfig.json` — strict; `@/*` → `resources/js/*` (no `@modules/*` yet — TS 6 single-wildcard limit)
- `resources/js/Pages/Admin/Posts/Edit.vue` — TS reference page

**Global search**
- `app/Services/AdminSearchService.php` — manifest-driven search engine
- `app/Http/Controllers/Admin/SearchController.php`
- `resources/js/Components/Organisms/GlobalSearch.vue`
- `routes/admin.php` — `admin.search.index`

**Tests** (23 total)
- `tests/Feature/AdminRoutesSmokeTest.php` — 6 tests (RBAC + fault isolation)
- `tests/Feature/AdminSearchTest.php` — 5 tests (global search)
- `tests/Feature/PublicRoutesSmokeTest.php` — 10 tests
- `tests/Unit/` — 2 tests

**Doctor + deploy**
- `app/Console/Commands/TemplateDoctor.php`
- `.github/workflows/deploy.yml` — `workflow_dispatch` + tag triggers; runs `template:doctor --production` before migrate
- `public/debug.php` — token-gated recovery (parses `.env` directly, app-independent)
- `DEPLOY-cpanel.md`, `DEPLOY-siteground.md`
- `composer deploy` — doctor + migrate + `typescript:transform` + `ziggy:generate` + optimize

**Mail + notifications + queue**
- `resources/views/emails/layouts/branded.blade.php` + 4 templates
- `app/Http/Controllers/Admin/NotificationsController.php`
- `resources/js/Components/Organisms/NotificationBell.vue`
- `database/migrations/2026_06_30_130000_create_jobs_table.php`
- `database/migrations/2026_06_30_140000_create_notifications_table.php`
- `routes/console.php` — `Schedule::command('queue:work --stop-when-empty ...')`

## 6. Remaining work

1. **Phase J — Verification** — run all 15 steps from the plan with MAMP up (see plan § Phase J). Highlights:
   - `composer ide && composer deploy` clean; CI green
   - Module discovery + generator self-test (`make:module` / `make:crud`)
   - Fault isolation + RBAC + TS drift test (`pnpm build` after renaming a DTO field)
   - Global search: `/` opens modal, searches enabled modules only
   - Queue drain: dispatch job, wait one scheduler tick, `jobs` empty
2. **Optional follow-ups** (not blocking):
   - Re-add `@modules/*` tsconfig path when physical modules ship JS (TS 6 allows only one `*` per path substitution)
   - Migrate remaining admin Vue pages from hardcoded URLs to `route()` (legacy pages still use literal paths)
   - Wire notification listeners for contact form, backup events, module unhealthy (H2 listeners may be partial)
   - Per-module DTOs under `app/Modules/{X}/Data/` as features migrate out of v2 layout

## 7. Useful commands

```bash
composer ide            # IDE helpers + typescript:transform + ziggy:generate
composer deploy         # production deploy (doctor + migrate + TS + Ziggy + optimize)
php artisan typescript:transform
php artisan ziggy:generate resources/js/types/ziggy.js --types=resources/js/types/ziggy.d.ts
php artisan test && php artisan optimize && pnpm build   # phase gate
```

## 8. Memories already saved (do not duplicate)

In `~/.claude/projects/-Users-nomanjawad-Working-File-webTemplate/memory/`:
- `project_template_architecture.md`
- `feedback_use_pnpm.md`
- `project_v2_conventions.md`
- `feedback_module_architecture.md`

## 9. Hard rules — DO

- Use `pnpm`, never `npm`.
- Keep all module register/boot in `rescue()`.
- Always `Cache::remember` plain arrays (file driver compat).
- Declare route keys per-route (no `getRouteKeyName()` on models).
- Run `php artisan test && php artisan optimize && pnpm build` at the end of each phase — red = not done.
- Regenerate types after DTO changes: `php artisan typescript:transform`.

## 10. Hard rules — DON'T

- Don't commit unless the user explicitly asks.
- Don't skip git hooks (`--no-verify`).
- Don't auto-launch MAMP or probe MySQL with hardcoded creds (the classifier denies this).
- Don't restore `build-deploy.sh`.
- Don't add cross-module Vue imports.
- Don't add `getRouteKeyName()` to any model.
- Don't add custom `fromArray()` methods on Data DTOs that call `self::from()` — Spatie Data treats `from*` as creation methods and will recurse.

## 11. Pointer to full plan

`/Users/nomanjawad/.claude/plans/melodic-prancing-penguin.md` — read **Phase J** in full before the verification pass.
