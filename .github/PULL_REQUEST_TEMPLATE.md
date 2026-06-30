## Summary
<!-- 1-3 bullets describing the change and why -->

## Scope
<!-- Which module(s) does this touch? Mark the relevant boxes. -->
- [ ] New module added
- [ ] Existing module modified: __
- [ ] Core (auth, layouts, modules system) — please justify

## Checklist
- [ ] `php artisan test` green
- [ ] `php artisan optimize` clean (catches duplicate route names + view-compile failures)
- [ ] `pnpm build` succeeds; `vue-tsc --noEmit` clean if TS was touched
- [ ] New admin routes have `can:resource.action` middleware
- [ ] New public routes appended to `PublicRoutesSmokeTest::PARAMS` if parameterized
- [ ] Module manifest updated (permissions, nav, dependencies, searchable)
- [ ] No cross-module imports introduced (PHP or Vue)
- [ ] Forms use Atoms / `<FormShell>` — no raw `<input>` markup added
- [ ] Images render through `imageUrl()` / `useImageUrl()` / `<AppMediaPicker>`
- [ ] Migrations are old-MySQL safe: `varchar(191)` for unique indexes, no TEXT/JSON defaults

## Test plan
<!-- How a reviewer can verify the change locally -->
- [ ]
- [ ]
