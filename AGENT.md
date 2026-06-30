# AGENT.md — webTemplate v3 Handoff

This document is for the **next agent** picking up the modular usability upgrade. Read this top-to-bottom before touching code.

The full plan lives at `/Users/nomanjawad/.claude/plans/melodic-prancing-penguin.md`. Cross-reference it for phase scope; this file tracks **what's done, what's broken, what's next**.

---

## 1. Project snapshot

- **Stack**: Laravel 13 + Vue 3 + Inertia + Tailwind v4, MySQL-only.
- **Branch**: `main`. Many uncommitted changes — do NOT commit unless the user asks.
- **Package manager**: `pnpm` (never `npm`). Composer for PHP.
- **Working dir**: `/Users/nomanjawad/Working File/webTemplate`.
- MAMP must be running for DB-dependent verification. The user starts it themselves — do not auto-launch.

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
| C — TypeScript + DTOs + Ziggy | **BLOCKED** | Transformer only emits LaravelData paginator types, not the `#[TypeScript]` DTOs. See §5. |
| D — RBAC enforcement | DONE | `Gate::before` super-admin bypass, per-action `can:` on all admin routes, `PermissionSyncer` reads manifests |
| E — Toasts/confirms/shortcuts | PARTIAL | `FlashToaster`, `ConfirmDialog`, `useConfirm` created. Shortcuts composable not yet wired |
| F — GitHub Actions deploy | DONE | `.github/workflows/deploy.yml` + `public/debug.php` + host-specific deploy docs |
| G — `template:doctor` | DONE | Renamed `fail()`→`failed()` to avoid `Command::fail()` collision; wraps `Schema::hasTable` in try/catch |
| H — Mail/notifications/search/queue | PARTIAL | Branded mail templates, `NotificationsController`, `NotificationBell` done. Global search NOT done. Queue scheduler wired in `routes/console.php` |
| I — Papercuts | DONE | `CONTRIBUTING.md`, PR template, `composer module:enable/disable` scripts |
| J — Verification | PENDING | Gated on MAMP + finishing Phase C |

## 4. Key files (touch points)

**Module core**
- `app/Modules/Core/ModuleManager.php` — singleton; `enabled()`, `enable()`, `disable()`, `markUnhealthy()`, `summary()`, `navFor()`. Falls back to config when DB unreachable.
- `app/Modules/Core/AbstractModuleServiceProvider.php`
- `app/Modules/Core/VirtualModuleProvider.php` — wraps v2 features without folder migration
- `app/Modules/Core/PermissionSyncer.php`
- `app/Modules/Core/Middleware/EnsureModuleEnabled.php`
- `app/Providers/ModulesServiceProvider.php` — discovery + fault-isolation wrapping
- `config/modules.php` — virtual module definitions
- `database/migrations/2026_06_30_120000_create_modules_table.php`
- `database/seeders/ModulesSeeder.php`
- `app/Modules/Core/Http/Controllers/ModulesController.php`
- `resources/js/Pages/Admin/Modules/Index.vue`

**Generators**
- `app/Console/Commands/MakeModule.php`
- `app/Console/Commands/MakeCrud.php` — stamps full CRUD; patches manifest nav+perms; appends to `PublicRoutesSmokeTest::PARAMS`
- Stubs: `stubs/module/`, `stubs/crud/`

**Inertia shared**
- `app/Http/Middleware/HandleInertiaRequests.php` — shares `auth.user.{permissions,is_super_admin}` and `modules.{nav,enabled}`
- `resources/js/Layouts/AdminLayout.vue` — sidebar fed by `page.props.modules.nav`

**RBAC**
- `app/Providers/AppServiceProvider.php` — `Gate::before` super-admin bypass
- `database/seeders/RoleAndPermissionSeeder.php` — ensures roles, delegates to `PermissionSyncer`
- All 4 admin route files use per-action `can:resource.action` middleware

**Tests**
- `tests/Feature/AdminRoutesSmokeTest.php` — 6 tests including fault-isolation assertions

**Doctor + deploy**
- `app/Console/Commands/TemplateDoctor.php`
- `.github/workflows/deploy.yml` — `workflow_dispatch` + tag triggers; runs `template:doctor --production` before migrate
- `public/debug.php` — token-gated recovery (parses `.env` directly, app-independent)
- `DEPLOY-cpanel.md`, `DEPLOY-siteground.md`

**Mail + notifications**
- `resources/views/emails/layouts/branded.blade.php` + 4 templates
- `app/Http/Controllers/Admin/NotificationsController.php`
- `resources/js/Components/Organisms/NotificationBell.vue`
- `database/migrations/2026_06_30_130000_create_jobs_table.php`
- `database/migrations/2026_06_30_140000_create_notifications_table.php`
- `routes/console.php` — `Schedule::command('queue:work --stop-when-empty ...')`

**DTOs + TS (broken)**
- `app/Data/UserData.php`, `app/Data/ModuleNavEntry.php`, `app/Data/FlashData.php` — all `#[TypeScript]`
- `app/Providers/TypeScriptTransformerServiceProvider.php` — **currently broken**, see §5
- `tsconfig.json` — strict, `@/*` + `@modules/*` paths
- `resources/js/types/types.d.ts` — only emits LaravelData paginator types

## 5. CURRENT BLOCKER — TypeScript transformer

**Symptom**: `php artisan typescript:transform` emits only LaravelData's internal `PaginatedDataCollection` / `CursorPaginatedDataCollection` typedefs, **not** the three `#[TypeScript]`-marked DTOs in `app/Data/`.

**What was tried**:
- `LaravelDataTransformedProvider` alone — paginator only
- Adding `LaravelAttributedClassTransformer` — paginator only
- Adding `ReflectionTransformedProvider::class` — class-not-found (it doesn't exist in this package version)

**Current broken config** in `app/Providers/TypeScriptTransformerServiceProvider.php`:
```php
$config
    ->transformDirectories(app_path('Data'), app_path('Modules'))
    ->transformer(LaravelAttributedClassTransformer::class)
    ->provider(ReflectionTransformedProvider::class) // ← class not found
    ->provider(LaravelDataTransformedProvider::class)
    ->outputDirectory(resource_path('js/types'));
```

**Next steps for the agent**:
1. Remove the failing `ReflectionTransformedProvider::class` line.
2. Inspect `vendor/spatie/typescript-transformer/src/` for the actual concrete `TransformedProvider` that scans directories for `#[TypeScript]` attributes. Concrete providers found so far: `PhpNodesAwareTransformedProvider`, `ConfigAwareTransformedProvider`, `LoggingTransformedProvider`, `WatchingTransformedProvider`, `ActionAwareTransformedProvider`, `LaravelDataTransformedProvider`, `LaravelControllerTransformedProvider`.
3. Look at `vendor/spatie/typescript-transformer/src/TypeScriptTransformerConfigFactory.php::get()` — the default factory probably wires the right discovery provider. May need to drop the custom provider config entirely and rely on the package's published `config/typescript-transformer.php` (run `php artisan vendor:publish --tag=typescript-transformer-config`).
4. Confirm `php artisan typescript:transform` then emits `UserData`, `ModuleNavEntry`, `FlashData` into `resources/js/types/`.

## 6. Remaining work after C unblocks

1. Wire `composer ide` to run `typescript:transform` + `ziggy:generate`.
2. Add `vue-tsc --noEmit` to `pnpm build` (and CI).
3. Phase E — finish `useShortcuts()` composable (`g h`, `g d`, `g p`, `/`).
4. Phase H — global search controller + `<GlobalSearch>` modal iterating enabled-modules' `searchable` manifest entries.
5. Phase J — run all 15 verification steps from the plan with MAMP up.

## 7. Memories already saved (do not duplicate)

In `~/.claude/projects/-Users-nomanjawad-Working-File-webTemplate/memory/`:
- `project_template_architecture.md`
- `feedback_use_pnpm.md`
- `project_v2_conventions.md`
- `feedback_module_architecture.md`

## 8. Hard rules — DO

- Use `pnpm`, never `npm`.
- Keep all module register/boot in `rescue()`.
- Always `Cache::remember` plain arrays (file driver compat).
- Declare route keys per-route (no `getRouteKeyName()` on models).
- Run `php artisan test && php artisan optimize && pnpm build` at the end of each phase — red = not done.

## 9. Hard rules — DON'T

- Don't commit unless the user explicitly asks.
- Don't skip git hooks (`--no-verify`).
- Don't auto-launch MAMP or probe MySQL with hardcoded creds (the classifier denies this).
- Don't restore `build-deploy.sh`.
- Don't add cross-module Vue imports.
- Don't add `getRouteKeyName()` to any model.

## 10. Pointer to full plan

`/Users/nomanjawad/.claude/plans/melodic-prancing-penguin.md` — read Phase C and Phase J in full before resuming.
