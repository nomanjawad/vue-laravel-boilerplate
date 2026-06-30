# Contributing to webTemplate

This boilerplate has a handful of hard rules — they exist because they paid for themselves in production. **Follow them; the smoke tests will catch you if you don't.**

## TL;DR

1. New feature = new module under `app/Modules/{Name}/`. Use `php artisan make:module`.
2. New CRUD inside a module = `php artisan make:crud Thing --module=ModuleName`.
3. Components are atomic: Atoms → Molecules → Organisms. Module-specific lives under `Components/Modules/`. **Never import another module's components.**
4. Forms use the Atoms (`AppInput`, `AppFormField`, `<FormShell>`), never raw `<input>` markup.
5. Admin routes are gated per-action with `can:resource.action`.
6. All uploads go through `MediaService` / `<AppMediaPicker>`. Image URLs go through `Controller::imageUrl()` or `useImageUrl()`.
7. Anything user-visible in production gets a smoke test entry. CI runs `php artisan optimize` + smoke tests + Vite build.

## Module rules

webTemplate is built around toggleable, fault-isolated modules. **Adding a feature means dropping a module in; removing one means deleting a folder.** Everything a module owns lives next to it:

```
app/Modules/Newsletter/
  NewsletterModuleServiceProvider.php   # auto-discovered
  module.php                            # manifest: permissions, nav, deps
  Http/Controllers/{Admin,Public}/      # routes loaded only when enabled
  Models/
  Database/{Migrations,Factories,Seeders}/
  Routes/{admin,public}.php
  Resources/js/{Pages,Components}/
  Tests/Feature/
```

Hard rules:

- **One direction of imports.** Atoms ← Molecules ← Organisms ← Pages. Module-internal components never import from another module.
- **Manifest is the source of truth.** Permissions, sidebar entries, dependencies, searchable models — all declared in `module.php`. The dashboard `/admin/modules` reads them; `PermissionSyncer` syncs them; the global search reads them; the smoke tests use them.
- **Modules toggle from the admin dashboard**, not by editing `.env`. The DB is the source of truth; `config('template.features.*')` is the fallback.
- **Fault isolation is enforced.** Every `register()` / `bootModule()` runs inside `rescue()`. If your module throws, it gets marked unhealthy and skipped; the rest of the panel keeps booting. Don't disable this — if you need init that *must* succeed, make a normal Laravel `ServiceProvider`, not a module provider.
- **Disabling preserves data; uninstalling drops it.** Reflect that in your migrations: roll back cleanly, never delete data on `down()`.

## v2 conventions (still apply)

- Models **never** declare `getRouteKeyName()`. Routes declare their keys: `{model:slug}` public, default id-binding admin.
- The `HandleInertiaRequests::PUBLIC_SETTINGS` whitelist is the *only* path settings keys reach the browser. Adding a key here exposes it to every visitor. Default to NOT adding.
- `Cache::remember` stores **plain arrays** — file driver can't round-trip Eloquent collections.
- **MySQL only.** No SQLite. `varchar(191)` is the safe ceiling for unique indexes (old-MySQL utf8mb4). No `TEXT` or `JSON` defaults.
- The admin dark theme is scoped under `.admin-dark` in `resources/css/admin.css`. New pages keep using light utility classes — the scoped overrides re-theme them.

## Adding a new module — recipe

```bash
# 1. Scaffold
php artisan make:module Newsletter
php artisan make:crud Subscriber --module=Newsletter --slug --public

# 2. Edit app/Modules/Newsletter/module.php — set dependencies, search, etc.

# 3. Toggle on
# Visit /admin/modules and flip the switch. Migrations + permission sync happen automatically.

# 4. Run the gate
php artisan test
php artisan optimize
pnpm build
```

## Pull-request checklist

Copied into `.github/PULL_REQUEST_TEMPLATE.md` — but the highlights:

- [ ] `php artisan test` green
- [ ] `php artisan optimize` clean (catches duplicate route names + view-compile failures before deploy)
- [ ] `pnpm build` succeeds, `vue-tsc --noEmit` clean if TS is touched
- [ ] If a new public route was added, smoke test PARAMS map was updated (or `make:crud --public` did it for you)
- [ ] If a new admin route was added, the route is gated with `can:resource.action`
- [ ] Module manifest updated (permissions, nav, dependencies)
- [ ] No cross-module imports (Vue or PHP)
- [ ] Forms use Atoms; no new hand-rolled `<input>` markup
- [ ] Images go through `MediaService` and render via `imageUrl()` / `useImageUrl()` / `<AppMediaPicker>`

## Useful commands

```bash
composer ide            # regenerate IDE helpers + TS + Ziggy route types
composer deploy         # production deploy steps (also run by GH Actions)
php artisan template:doctor   # health check (preflight)
php artisan make:module {Name}
php artisan make:crud {Model} --module={Name} [--slug --soft-deletes --media --public]
php artisan import:wordpress {file.xml}
```

## Reporting issues

For bugs, ship a failing test in `tests/Feature/`. For new ideas, open a discussion before opening a PR — the surface area of this boilerplate is intentionally small and additive changes have to clear a high bar.
