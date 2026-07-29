---
name: create-module
description: Create or modify a feature module (physical app/Modules/* or virtual config/modules.php) — scaffolding with make:module + make:crud, the module.php manifest, permissions, sidebar nav, dependencies, enable/disable lifecycle, and fault isolation. Use when adding a new feature to the boilerplate or changing how an existing module registers itself.
---

# Feature modules

Every feature is a module. Two kinds:

- **Physical** — self-contained folder in `app/Modules/{Name}/` (Testimonials,
  Faqs, Events). This is the pattern for ALL new features.
- **Virtual** — declared in `config/modules.php`, using legacy `routes/*.php`
  files and core `resources/js/Pages` (blog, shop, users, settings, media, …).
  Don't create new virtual modules; they exist for v2 compatibility.

The kernel is `App\Modules\Core\ModuleManager` (singleton). Providers are
discovered by `App\Providers\ModulesServiceProvider`, which globs
`app/Modules/*/*ModuleServiceProvider.php`.

## Scaffolding a new feature (the happy path)

```bash
php artisan make:module Newsletter --description="Email campaigns"
php artisan make:crud Campaign --module=Newsletter --slug --public
# edit the generated manifest/migration/pages as needed, then:
composer module:enable newsletter     # or toggle at /admin/modules
composer ide                          # regen TS types if you added DTOs
```

- `make:module {Name}` creates the full skeleton (Http/Controllers/{Admin,Public},
  Requests, Models, Policies, Services, Data, Database/{Migrations,Factories,Seeders},
  Routes, Views, Resources/js/Pages/{Admin,Public}, Resources/css, Tests/Feature)
  plus `module.php` and the ServiceProvider from `stubs/module/`.
- `make:crud {Model} --module={Name}` stamps model, migration, factory,
  store/update requests, policy, admin controller, Index/Create/Edit Vue pages,
  and the route file from `stubs/crud/`, then **patches `module.php`** with a
  permissions block + nav entry automatically. Flags: `--slug` (unique slug +
  `{model:slug}` public binding), `--soft-deletes`, `--public` (public
  controller + Show page). `--media` is currently a no-op placeholder.
- Enabling (dashboard or `composer module:enable {key}`) runs the module's
  migrations, its manifest `seeders`, and `PermissionSyncer::sync()` — no
  manual permission step.

## The manifest (`module.php`)

```php
return [
    'key'          => 'testimonials',   // stable id: DB key + permission prefix
    'name'         => 'Testimonials',
    'description'  => '…',              // shown on /admin/modules
    'version'      => '1.0.0',
    'dependencies' => ['media'],        // must be enabled first; blocks disable of deps
    'nav_group'    => 'content',        // sidebar section: content | commerce | system
    'permissions'  => ['testimonials' => ['view','create','update','delete']],
    'nav'          => [[
        'label' => 'Testimonials',
        'route' => 'admin.testimonials.index',  // route NAME — resolved to an
        // href server-side in ModuleManager::navFor(); frontend never calls route()
        'icon' => 'chat-bubble-left-right',     // must exist in AppIcon.vue's maps
        'permission' => 'testimonials.view',
    ]],
    'searchable'   => [Models\Testimonial::class => ['title','body']], // global admin search
];
```

Other honored keys: `core` (can't be disabled; always enabled), `seeders`
(classes run on enable/reinstall), `feature_flag_fallback` (config
`template.features.{flag}` used when no DB row exists — fresh installs).

## Conventions inside a module

- **Routes** (`Routes/{resource}.php`): declare their own full middleware —
  `Route::middleware(['web','auth','admin','module:{key}'])->prefix('admin')->name('admin.')`
  with per-action `can:{resource}.{action}`. The `module:{key}` middleware 404s
  when the module is disabled (guards stale cached routes).
- **Vue pages** resolve by naming convention:
  `Inertia::render('Testimonials/Admin/Testimonials/Index')` →
  `app/Modules/Testimonials/Resources/js/Pages/Admin/Testimonials/Index.vue`
  (first path segment = module folder; see the resolver in `resources/js/app.ts`).
- **Policies** auto-discover by namespace convention (`…\Models\X` →
  `…\Policies\XPolicy`); methods delegate to `$user->can('{resource}.{action}')`.
  `super-admin` bypasses everything via `Gate::before`. Controllers call
  `$this->authorize(...)` (trait lives on the base `App\Http\Controllers\Controller`).
- **Models**: use `ClearsResponseCache` + `LogsContentActivity` traits, module
  factory via `newFactory()`. Route keys are declared per-route
  (`{model:slug}` public, id-binding admin) — **never** add `getRouteKeyName()`.
- **No cross-module imports** (enforced by review). Shared code belongs in
  core (`app/Services`, `resources/js/Components`).
- DTOs in the module's `Data/` folder with `#[TypeScript]` are picked up by
  `php artisan typescript:transform`.

## Lifecycle & health

- `/admin/modules` (permission `modules.manage`): enable, disable, reinstall,
  clear-health, toggle sidebar visibility (`nav_visible` — works for core
  modules too), and uninstall (type `UNINSTALL`; rolls back migrations, drops
  permissions).
- Disable never rolls back migrations — data is preserved.
- **Fault isolation**: provider register/boot are wrapped in `rescue()`. A
  throwing module is marked `unhealthy` (red badge + last_error on
  /admin/modules) and treated as disabled — it never 500s the panel. Recover
  with Reinstall or Clear health.
- `enabled()` precedence: manifest `core` → always on; else `modules` DB row
  (`enabled && !unhealthy`); else fallback to `config('template.features.{flag}')`.
- The registry is cached forever under `modules.registry` as a **plain array**
  (file-cache safe — never store Eloquent collections in cache here).

## Permission model

`PermissionSyncer::sync()` (runs on every enable): creates
`{resource}.{action}` permissions for all enabled modules; role `admin` gets
all of them; role `editor` gets only `.view/.create/.update` and never
core-module resources. Uninstall drops the module's permission rows.

## Gotchas

- MySQL only, and unique-indexed strings must be `varchar(191)`.
- Sidebar section order is fixed (Content → Commerce → System) in
  `AdminLayout.vue`; unknown `nav_group` falls back to content.
- Nav `icon` names must exist in `resources/js/Components/Atoms/AppIcon.vue`
  (Heroicons-style names like `cog-6-tooth`, `photo`; unknown names render the
  fallback cube). Add the SVG path there for a new icon.
- There is currently no root `tests/` directory or phpunit.xml — module
  `Tests/Feature/` folders are the intended home for tests but no runner
  config is checked in. Don't assume `php artisan test` works.
- After changing a manifest's permissions, re-run enable (or
  `RoleAndPermissionSeeder`) to sync.
