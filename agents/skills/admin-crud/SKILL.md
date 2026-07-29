---
name: admin-crud
description: Building or changing admin panel screens — the AdminLayout dark theme, the Atoms/Molecules/Organisms component library (FormShell, AppFormField, DataTable, AppMediaPicker…), composables, form patterns, permission gating, and DTO→TypeScript flow. Use when writing any Vue page or component under /admin.
---

# Building admin panel UI

Admin pages are Inertia Vue 3 pages (`<script setup lang="ts">`) rendered
inside `AdminLayout.vue` (`defineOptions({ layout: AdminLayout })`). Core pages
live in `resources/js/Pages/Admin/…`; module pages in
`app/Modules/{Name}/Resources/js/Pages/Admin/…`.

## Non-negotiable conventions

- **Navigation is literal root-relative paths** (`/admin/posts`,
  `` `/admin/posts/${id}/edit` ``) — there is NO route() helper on the
  frontend (Ziggy was removed). Server-side PHP still uses named routes.
- **Component layering**: Atoms → Molecules → Organisms → Pages. No
  cross-module imports; shared UI belongs in `resources/js/Components/`.
- **Dark theme**: `AdminLayout` adds `.admin-dark`; `resources/css/admin.css`
  remaps light Tailwind classes (`bg-white`, `text-gray-900`, `border-gray-200`…)
  to dark tokens. Write pages with normal light-mode classes and they render
  correctly — or use semantic tokens directly (`var(--admin-surface)`,
  `bg-(--admin-sidebar)` etc.). Never assume a raw gray renders as-is.
- **Data crossing to the browser** must exist on a `#[TypeScript]` DTO in
  `app/Data/` (or a module's `Data/`): spatie/laravel-data silently drops
  unknown keys. After changing a DTO run `php artisan typescript:transform`
  (types land in `resources/js/types/types.d.ts` as `App.Data.*`).
- Brand color utilities come from `@theme` in `resources/css/app.css` —
  available steps are brand-{50,100,300,500,600,700,900} only (no 400!).

## The form pattern (design-system exemplar: `Pages/Admin/Settings/Index.vue`)

```vue
const form = useForm({ settings: { site_name: '', … } })

<FormShell :form="form" action="/admin/settings" method="put" cancel-href="/admin">
  <AppFormSection title="General" description="…">
    <AppFormField name="settings.site_name" label="Site name" help="…" v-slot="{ id, invalid }">
      <AppInput :id="id" :invalid="invalid" v-model="form.settings.site_name" />
    </AppFormField>
  </AppFormSection>
</FormShell>
```

- `FormShell` provides the form to descendants, submits with
  `form[method](action, {preserveScroll})`, disables the button while
  processing, and guards `beforeunload` when dirty.
- `AppFormField` reads `form.errors[name]` and passes `{id, invalid}` to its
  slot — error display is automatic.
- Gotcha: Inertia's `useForm()` reserves the key `data` — never name a form
  field `data` (use `content` or similar). Deeply recursive generic types blow
  up `vue-tsc`; keep `useForm`'s generic simple and cast at point of use.
- Note: Posts Create/Edit use an older raw-`<form>` style — prefer the
  FormShell pattern for new screens.

## Component quick reference

Atoms: `AppButton` (href→Link, variants primary/outline), `AppInput`,
`AppTextarea`, `AppSelect` (options: primitives or {value,label}),
`AppCheckbox`, `AppSwitch` (boolean toggle, no id prop), `AppFileInput`
(emits `select(File[])`), `AppLink` (Link with prefetch=hover),
`AppSpinner`, `Badge` (color: gray|green|amber|red|brand), `SectionHeading`,
`AppIcon` (name from built-in maps in AppIcon.vue; unknown → cube fallback).

Molecules: `AppCard` (title/padded, header/footer slots), `AppEmptyState`,
`AppFormField`, `AppFormSection`, `AppPagination` (Laravel paginator),
`AppSearchInput` (debounced, 250ms).

Organisms: `DataTable` (columns/rows/sort — sortable headers do
`router.get` with preserveState; slots `cell:{key}`, `actions`, `empty`;
paginates automatically), `FormShell`, `AppMediaPicker` (see settings-and-media
skill), `JsonContentEditor` (recursive JSON editor), `GlobalSearch`,
`NotificationBell`, `BlogTabs` (Posts/Categories/Tags tab bar).

Shared: `Can` (`<Can permission="posts.create">` or `:any="[…]"`, fallback
slot), `ConfirmDialog` + `useConfirm()` (`await confirm({title, confirmTone:
'danger'})` returns Promise<boolean>), `FlashToaster` (flash.success/error
auto-toast), `BrandLogo`.

Composables: `usePermissions()` (`can()`, `canAny()`, `isSuperAdmin`),
`useImageUrl()` (`toImageUrl` — mirrors backend `Controller::imageUrl`),
`useConfirm()`, `useShortcuts()` (g h / g d / g p, `/` search),
`useTableFilters(initial, {only})` (two-way URL query sync for index filters),
`useConsentScripts()` (cookie-gated GA/GTM).

## Backend side of an admin screen

- Routes live in the module's `Routes/*.php` (or `routes/admin.php` for
  virtual modules) inside
  `Route::middleware(['web','auth','admin','module:{key}'])->prefix('admin')`,
  with per-action `can:{resource}.{action}` middleware.
- `AdminMiddleware` requires role super-admin/admin/editor; permissions are
  `{resource}.{action}` via spatie; `Gate::before` gives super-admin a bypass.
- Controllers call `$this->authorize('viewAny', Model::class)` (policies
  auto-discover by namespace convention).
- Flash messages: `back()->with('success', …)` / `'error'` → `FlashData` →
  `FlashToaster` renders them automatically.
- Sidebar entries come from module manifests (see create-module skill) — never
  hardcode nav in `AdminLayout.vue`.

## Verify before finishing

`pnpm build` must pass (`vue-tsc --noEmit` gates it — CI-enforced) and
`php artisan optimize` must stay clean. Dev server: `composer dev` (or
`php artisan serve` + `pnpm dev`; port 8001 if 8000 is taken).
