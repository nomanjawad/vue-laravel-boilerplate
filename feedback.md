# Boilerplate Feedback & Improvement Guide

> Honest, field-tested feedback based on building a Laravel 13 + Vue 3 + Inertia.js
> admin panel (~145 pages) on this boilerplate, deployed to StackCP/Hostinger shared
> hosting. Goal of this document: make future development **and** frontend design
> dramatically faster.

---

## 1. Executive Summary

The **backend foundation is solid** — Laravel + Inertia + Vue is the right stack for a
CRUD-heavy admin panel and earns its keep. The **frontend side is where the boilerplate
cost us time**: a half-migrated Blade↔Vue state, two competing design systems
(Bootstrap + Tailwind), and host-hostile defaults (`shell_exec`, `symlink`) that only
crash *after* deploy.

The fastest version of this project is: **one design system, one rendering paradigm, a
small library of reusable admin components, typed props, and zero shell calls.**

Roughly **80% of admin screens are list + form + detail** — once those three are real,
reusable components, new pages become *configuration instead of construction*.

---

## 2. What the Boilerplate Got Right

- **Laravel + Inertia + Vue is an excellent choice.** No separate API layer, no CORS,
  no duplicate auth. Write a controller, return `Inertia::render`, done. For a
  CRUD-heavy admin this is the fastest stack available. **Keep it.**
- **Server-driven routing.** Routes live in Laravel and props flow to Vue. No parallel
  frontend router, no TypeScript contracts to manually sync against API responses.

---

## 3. Where It Actively Slowed Development Down

### 3.1 The half-migrated Blade ↔ Vue state (biggest tax)
~145 Blade pages partially migrated to Vue meant **two rendering paradigms living
side-by-side**, sharing partials like `sidebar.blade.php`. The live `shell_exec()` crash
on the POS page is the textbook symptom: a not-yet-migrated Blade page pulled in a
shared partial with host-incompatible code, and it only blew up in production.

> **Recommendation:** Go all-Inertia from day one, or don't start a migration at all.
> Avoid long-lived hybrid states.

### 3.2 Two design systems (Bootstrap in admin + Tailwind elsewhere)
Paying the cognitive cost of two utility vocabularies (`class="btn btn-primary"` vs
Tailwind utilities) makes frontend design **slower**, not faster, due to constant
context-switching.

> **Recommendation:** Pick one. For an Inertia/Vue app, standardize on Tailwind + a
> component library (shadcn-vue, PrimeVue, or Reka UI) and delete Bootstrap.

### 3.3 Host-hostile defaults
`shell_exec`, `exec`, and `symlink` were baked in (version display, storage linking) —
**all blocked on StackCP/Hostinger**. These are landmines that only detonate after
deploy. We replaced them with `version.txt` reads.

> **Recommendation:** A boilerplate targeting shared hosting must never shell out.

---

## 4. What to Include in the Boilerplate to Go Faster

### 4.1 Backend scaffolding (kills CRUD repetition)
- **Base resource controller** — `index/store/update/destroy` with pagination, search,
  sort, and filter pre-wired. New entity = subclass + model, not 200 lines.
- **Custom generator command** — `php artisan make:crud Product` stamps out controller,
  model, migration, request validators, Inertia page, and route from stubs.
  *(Single biggest speedup for an admin panel.)*
- **Form Request classes by default** — validation out of controllers, reused across
  store/update.
- **Query/filter helper** (e.g. Spatie QueryBuilder) — `?filter[]`, `?sort=`, `?search=`
  for free on list endpoints.
- **Dual-response trait** — one helper to branch between JSON and Inertia responses.
- **Seeders + factories for every model** — local dev always has realistic data.

### 4.2 Frontend scaffolding (kills page repetition)
Ship these as real, reusable components (80% of admin screens are built from them):
- `<DataTable>` — columns config, pagination, sort, search, row actions, empty/loading.
- `<FormModal>` / `<FormDrawer>` — schema-driven, handles Inertia `useForm`, validation
  errors, submit/cancel.
- `<PageHeader>` — title, breadcrumbs, primary action button.
- `<ConfirmDialog>`, `<Toast>` (Laravel session flash → toast automatically).
- `<StatCard>`, `<FilterBar>`, `<Pagination>`.
- A **layout shell** with sidebar/topbar already wired to permissions.

> Result: a new page becomes *configuration* (define columns + form fields), not
> construction.

### 4.3 One design system, pre-themed
- **Tailwind + one Vue component library** (shadcn-vue / PrimeVue / Reka UI) — drop
  Bootstrap.
- **Theme tokens file** — palette (e.g. Forest `#1c330d`, Cream `#FFF5E8`, Tan `#c69e7e`,
  Navy `#1e2a39`), spacing, radius, shadows as CSS variables / Tailwind config. Rebrand
  in one place.
- **Dark mode + RTL** toggles baked in if ever needed.

### 4.4 Types & contracts (kills drift bugs)
- **TypeScript** on the Vue side.
- **Spatie typescript-transformer** (or laravel-data) — backend DTOs/enums auto-generate
  `.d.ts`, props typed end-to-end with no manual sync.
- **Ziggy** — route names in Vue (`route('admin.products.index')`) instead of hardcoded
  URLs.

### 4.5 Auth, permissions, settings (every project needs these)
- **Roles & permissions** (spatie/laravel-permission) wired into backend middleware
  *and* a shared `can()` helper in Vue.
- **Settings/config table + cache** for app-level toggles (logo, version, feature flags)
  — no shell calls, no hardcoding. (This is what replaced the `shell_exec` version line.)
- **Activity log** (spatie/activitylog) for free audit trails.
- **Media/file upload** component + Laravel media library, with a URL-vs-path guard
  built in.

### 4.6 Host-safety (so prod doesn't surprise you)
- **Zero shell calls** anywhere — version from `version.txt`, no `symlink()`, no
  `exec()`. Disable these in local `php.ini` so they fail in dev, not after deploy.
- **A `.deploy/` folder** with the GitHub Actions workflow, `.htaccess`, and a deploy
  checklist as a reusable template.
- **`.env.example` with shared-hosting defaults** (file cache/session driver, no Redis
  assumption).
- **Healthcheck route** confirming DB, storage write, and asset manifest after deploy.

### 4.7 Developer experience
- **Storybook / Histoire** — build/preview components in isolation without booting the
  full app. Big speedup for design iteration.
- **Laravel Pint + ESLint + Prettier + pre-commit hook** — formatting never a discussion.
- **Pest tests + a CI step** running them on PR.
- **Seeded demo login** + a README that gets a new dev running in one command.

---

## 5. The 20% That Delivers 80% of the Speed

If only a few things get built, build these:

1. **`make:crud` generator**
2. **`<DataTable>` + `<FormModal>` components**
3. **One design system with theme tokens**
4. **Typed props (Spatie transformer + Ziggy)**

These four turn "build a new admin module" from a day into an hour.

---

## 6. Quick-Reference: Problem → Fix

| Problem | Fix |
|---|---|
| Two CSS systems | Standardize on Tailwind + one Vue component lib (shadcn-vue / PrimeVue) |
| Repetitive CRUD controllers | Base controller / Laravel resource pattern + `make:crud` generator stub |
| Repetitive Vue pages | Shared `<DataTable>`, `<FormModal>`, `<PageHeader>` components |
| Props typing drift | TypeScript + Spatie typescript-transformer + Ziggy |
| Prod-only crashes | Match local PHP to host (8.4); disable `shell_exec`/`exec`/`symlink` locally |
| Slow design iteration | Storybook / Histoire for isolated component development |
| Hybrid Blade/Vue pain | Go all-Inertia from day one; avoid long-lived migrations |
| Host-blocked functions | Zero shell calls; read `version.txt`; settings table for config |

---

## 7. Second Field Report — SkyTech build on SiteGround

> Independent second build: a full Vue 3 + Laravel 13 + Inertia marketing site +
> admin panel, migrated from a headless Next.js/WordPress site and deployed to
> **SiteGround** shared hosting. Where this report **confirms** section 4–6 below,
> treat that as a strong signal — two unrelated builds hit the same walls.

### 7.1 The biggest untold cost: deployment (the doc underweights this)

Section 4.6 covers "host-safety," but in practice **deployment ate more time than all
feature work combined.** The boilerplate ships with *no real deploy story*, so every
issue was discovered live, as a blank 500. What actually happened, in order:

1. **Zip-and-upload dragged junk that broke prod.** A 177 MB stray `Archive.zip`, a
   stale local `bootstrap/cache/config.php` (froze MAMP DB creds → prod 500), the local
   `.env`, a `public/storage` symlink pointing at `/Users/...`, and the Vite **`hot`
   file** (makes prod try to load assets from a dev server that isn't there).
2. **PHP version mismatch.** `vendor/` built on PHP 8.5 baked a `>= 8.4` platform check;
   the server ran 8.2 → hard fatal before Laravel booted.
3. **The `/public` in every URL** + an **`ERR_TOO_MANY_REDIRECTS` loop** (see 7.2).
4. **Storage symlink** had to be recreated server-side; `artisan storage:link` points at
   the wrong path under non-standard layouts.
5. **Config-cache gotcha** — the #1 "I changed `.env` and nothing happened": cached
   config silently wins until `php artisan optimize:clear`.

> **Ship with the boilerplate (all proven on this build):**
> - **`build-deploy.sh`** — builds assets, then strips `node_modules`, `.git`, `.env`,
>   `bootstrap/cache/*.php`, stale logs, the `public/storage` symlink, **the `hot`
>   file**, and stray zips. Produces a clean, extract-in-place package.
> - **A standalone `debug.php`** (token-gated, pure PHP — runs even when the app fatals):
>   checks PHP version + extensions, parses `.env`, runs a **live DB test**, verifies the
>   storage symlink, does a **homepage self-test**, tails `laravel.log`, and offers
>   **one-click cache-clear / storage-link / boot-test**. This turned blind 500s into a
>   single page. Easily the highest-ROI artifact of the whole project.
> - **A `DEPLOY-<host>.md`** documenting the SiteGround/cPanel realities: PHP selector,
>   `.env` location, `storage:link`, "config cache is why your change did nothing,"
>   and the `/public` document-root options.
> - **GitHub Actions deploy** (tag bump → build → rsync → `optimize:clear`) so manual
>   zip uploads — where dotfiles get dropped and stale caches sneak in — go away.

### 7.2 Bugs that ship *inside* the boilerplate (not ours)

These are template defects every downstream project inherits:

- **Trailing-slash redirect loop.** The boilerplate ships **both** a
  `TrailingSlashRedirect` middleware (*adds* `/`) **and** Laravel's default
  `public/.htaccess` rule (*removes* `/`). On any host using the `public/` rewrite they
  fight → `ERR_TOO_MANY_REDIRECTS`, and the removal redirect even leaks `/public` into
  the URL. A sibling site that simply never added the middleware worked fine. **Pick one
  slash policy.**
- **Guest file uploads are broken.** `media.user_id` is `NOT NULL`, but the public
  CV/career form uploads as a guest → **every applicant got a 500.** Make it nullable.
- **Double `/storage/` prefix.** `MediaService::store()` returns `/storage/...` but
  several admin views prepended `/storage/` again → broken resume/image links. One
  shared URL helper, used everywhere, prevents this whole class of bug.
- **`getRouteKeyName()` on content models** resolves by slug, which 404s admin edit
  links (they use IDs). Use route-scoped bindings (`{post}` admin / `{post:slug}`
  public) instead.

### 7.3 RBAC is defined but enforced nowhere

The boilerplate *ships* roles + permissions (Spatie) but **gates nothing** — any
admin-role user could reach `/admin/settings`, `/admin/users`, everything. Had to add
`can:` middleware on every route group, a `Gate::before` super-admin bypass, **and**
sidebar filtering by the user's permissions. A permission system that isn't enforced is
worse than none — it implies a safety that doesn't exist. (This *extends* §4.5: the
package being installed ≠ access actually controlled.)

### 7.4 The safety net that paid for itself immediately

A single **route smoke test** — hit every public + admin GET route, assert no 5xx, with
DB-transaction rollback — caught **three real bugs at once** on first run:
`CategoryController::create` 500 (route registered, method didn't exist), a missing-column
crash, and a **duplicate route name that broke `php artisan optimize`** (passes in dev,
only fails at deploy). Plus: **run `php artisan optimize` in CI** — duplicate route names
and view-compile errors surface there, not in production. (Strengthens §4.7's "Pest +
CI": make the *first* generated test a renders-without-5xx smoke test per resource.)

### 7.5 Content-migration + SEO realities

- Migrating from WordPress, the **`uploads/` vs `storage/` image-path split** was a
  recurring tax. Ship a WP importer that normalizes paths to one convention on the way in.
- The `page_metas` + `SeoService` + `SEO_INDEXABLE` system is good but **half-finished**:
  no auto `sitemap.xml`, no JSON-LD (`Organization`/`Article`/**`JobPosting`** = real
  client value), no **redirect manager** (every rebuild changes URLs and clients panic
  about rankings). Finish these once in the boilerplate; every project needs them.

### 7.6 What I'd add to "the 20% that delivers 80%" (§5)

§5's four picks are right. Based on this build, add two that are pure time-back:

5. **The deploy kit** — `build-deploy.sh` + `debug.php` + host deploy doc + Actions.
   (Saved days here; would have saved an afternoon if it had existed up front.)
6. **The route smoke test + `optimize`-in-CI** — near-zero cost, caught the bugs that
   actually reached production.

---

## 8. Bottom Line

The backend (Laravel + Inertia) is a strong, fast foundation — keep it. The frontend is
where the boilerplate cost time: mixed Blade/Vue, mixed Bootstrap/Tailwind, and
host-hostile defaults. Consolidate to **one design system, one rendering paradigm, a
library of reusable admin components, typed props, and zero shell calls**, and the next
project will move several times faster.

**Two independent builds now agree** on the core fixes (CRUD generator, DataTable/FormModal,
one design system, typed props, zero shell calls). The SkyTech build adds one hard-won
lesson the first didn't surface: **the boilerplate needs a real deployment story and a
smoke-test safety net baked in** — most of the pain wasn't writing features, it was
getting them live without blank 500s. Ship the deploy kit + the smoke test alongside the
scaffolding, and "build *and* ship a new site" drops from a multi-day ordeal to about a day.

---

## 9. Third Pass — Fresh-Eyes Read of v2

> Written as a developer onboarding to v2 with no prior context. v2 closed the
> production-grade gaps (smoke tests, error pages, responsecache, redirects, sitemap,
> JSON-LD, MediaService, WP importer, `template:init`, dark admin, atomic frontend). The
> foundation is now genuinely good. What follows is what *still* slows the first day,
> the first new module, and the first deploy — ordered by time saved.

### 9.1 The CRUD-generator gap is still the #1 unfilled item

Sections 4.1, 5, and 7.4 all flag this and v2 still doesn't ship it. A new admin module
in v2 means: controller + FormRequest + model + migration + factory + 3 Inertia pages
(Index/Create/Edit) + sidebar entry + route registration + policy + `ClearsResponseCache`
+ `LogsContentActivity` traits + smoke-test PARAMS entry. That's ~12 touch points for
something the existing 17 admin controllers do almost identically.

> **Ship:** `php artisan make:crud Product --soft-deletes --media --slug` that stamps
> all of the above from stubs, *including the smoke-test PARAMS entry and the sidebar
> link*. Wire it to read the v2 conventions (route key per-route, traits, dark-admin
> classes). This single command is worth more than any other item in this section.

### 9.2 Atoms folder is 3 components — the admin pages are still hand-rolling forms

`Components/Atoms/` ships **AppButton, Badge, SectionHeading**. Every admin Create/Edit
page still hand-writes `<input class="…">`, `<select>`, `<textarea>`, error display,
label, and help text. That's 17 controllers × 2 pages × N fields of duplication.

> **Ship as Atoms (dark-admin-aware):** `AppInput`, `AppTextarea`, `AppSelect`,
> `AppCheckbox`, `AppSwitch`, `AppFileInput`, `AppDatePicker`, `AppRichText` (Tiptap or
> similar), `AppFormField` (label + control + error + help wrapper), `AppFormSection`.
> Each accepts a `useForm()` instance and a field name — error wiring is automatic.
> A new form drops from 200 lines to ~40.

### 9.3 No DataTable means every Index page reinvents pagination + search + sort

Same story as §4.2 and §5. v2 has 17 admin index pages — pagination markup, search box,
sort links, empty state, and row actions are copy-pasted into each. A `<DataTable>`
with column config + slot-based row actions collapses this.

> **Pair with** a `useTableFilters()` composable that syncs `?search=&sort=&page=` to
> Inertia `router.reload({ only: [...] })` so list pages are reactive without full
> reloads.

### 9.4 Typed props drift is already starting

v2 is pure JS Vue, and `HandleInertiaRequests` shares ~10 props (auth, settings, seo,
flash, organizationJsonLd, etc.). Without types, a renamed setting or moved seo field
silently breaks pages — exactly the bug class smoke tests can't catch.

> **Add:** TypeScript + `spatie/laravel-data` + `spatie/typescript-transformer`. DTOs
> for `SharedProps`, `Post`, `Product`, etc. auto-generate `.d.ts`. Now the IDE catches
> drift, and the smoke test catches what types miss.

### 9.5 RBAC is installed but the new admin routes still aren't gated

§7.3 called this out and v2 still has the gap: `routes/admin*.php` mostly relies on
"logged-in admin" rather than `can:posts.update` on each route. Spatie permission is
loaded, roles are seeded, but the matrix isn't enforced or surfaced in the sidebar.

> **Ship:** `can:` middleware on every admin resource group keyed to a permission
> convention (`{resource}.{view|create|update|delete}`), a `Gate::before` super-admin
> bypass, a `usePermissions()` composable for the Vue side, and a sidebar that filters
> itself by what the user can see. Seed the permission matrix from one config array
> so adding a module = adding one row.

### 9.6 No deploy kit yet — §7.1's highest-ROI artifact is missing

`composer deploy` exists but the §7.1 trio (`build-deploy.sh` packaging script,
token-gated `debug.php`, `DEPLOY-<host>.md`) isn't shipped. The single biggest cause of
post-deploy pain on shared hosting is still uncovered.

> **Ship the trio verbatim** — it has been proven twice now. Include a GitHub Actions
> workflow for the "tag → build → rsync → optimize:clear" path so manual zip uploads go
> away.

### 9.7 First-run still requires too much external knowledge

`template:init` is great but a new dev still needs to know: MAMP port 8889, `root/root`,
where MAMP's mysqldump lives, which PHP extensions to enable, how to point a vhost at
`/public`, and where to put the cron line. Each is a small papercut; together they're
a half-day.

> **Ship:** a `php artisan template:doctor` that checks PHP version + ext + MAMP
> binaries + `.env` + DB connection + storage writable + Vite manifest + queue + cron,
> and prints copy-paste fixes for each failure. Run it as the last step of
> `template:init` and as the first step of `composer deploy`.

### 9.8 Inertia flash → toast is unwired

Controllers `redirect()->with('success', '…')` in several places, but there's no global
toast that consumes `$page.props.flash`. Every action then needs a manual confirmation
UI or the user gets silent success.

> **Ship:** a `<FlashToaster>` mounted once in both `AdminLayout` and `PublicLayout`,
> watching `$page.props.flash.{success,error,info,warning}`. One line of controller
> code = a real notification.

### 9.9 The admin dark theme works but isn't documented for new pages

`admin.css` cleverly re-themes light utility classes under `.admin-dark` so existing
pages survive. But a new dev writing a new page won't *know* that — they'll see the
existing pages using `bg-white text-gray-900` and assume that's the convention. Then
someone redesigns the shell and the assumption breaks.

> **Ship:** `resources/js/Pages/Admin/_TEMPLATE.vue` (and `_TEMPLATE_FORM.vue`) — copy-
> paste starting points with comments explaining the dark-admin scoping, the form Atom
> usage, the page header pattern, and the standard flash/toast wiring.
> Make `make:crud` (§9.1) emit pages from these templates.

### 9.10 Smoke test is great but doesn't cover admin

`PublicRoutesSmokeTest` hits public GETs. The 17 admin controllers — exactly where the
"renders without 5xx" class of bug bit us in §7.4 — aren't covered.

> **Add:** `AdminRoutesSmokeTest` that logs in as the seeded super-admin and hits every
> admin GET (`index`, `create`, `edit` for one seeded record) asserting <500. Same
> 90 LOC, doubles the safety net's coverage.

### 9.11 One reusable image-upload component is still missing

`MediaService` does the right thing on the backend (WebP + variants + EXIF strip), but
on the frontend each admin form still implements its own `<input type="file">` +
preview + alt-text + delete + variant selection. The §7.2 "double `/storage/` prefix"
bug class lives here.

> **Ship:** `<AppMediaPicker>` — drag/drop, preview, calls one upload endpoint, returns
> a media id + the variant URLs, uses `useImageUrl()` everywhere internally so the
> double-prefix bug can't recur.

### 9.12 Smaller papercuts a fresh dev hits in the first week

- **Sidebar is hand-edited.** Adding a module = editing `AdminLayout.vue`. Make it a
  config array (label, icon, route, permission) so `make:crud` can append to it.
- **No `route()` helper in Vue.** Ziggy was excluded; dashboard already worked around
  this with a literal URL. Either ship Ziggy or ship a `routes.js` const map auto-
  generated at build time.
- **No queue/worker story for shared hosting.** Mail, backups, and the WP importer all
  benefit from queues; cPanel hosts don't run `queue:work`. Document `queue:work --stop-
  when-empty` triggered by the existing scheduler tick, or default to `sync` with a
  loud comment.
- **No transactional email templates.** The newsletter exists; password reset / welcome
  / contact-form-receipt still use Laravel defaults. Ship 3 branded Markdown mail
  templates.
- **No in-app notification center.** Spatie activitylog tracks history; nothing
  surfaces "3 new subscribers / 2 new 404s" to the admin dashboard beyond the static
  widget. A `notifications` table + bell icon is one evening of work, reused everywhere.
- **No global search.** Admins routinely want "find this order/post/customer." A
  Scout-free `?q=` endpoint scanning the 4–5 main tables would punch well above its
  weight.
- **No factory/seeder for every model.** `DemoSeeder` is curated; individual model
  factories are inconsistent. Smoke tests and local dev both want them.
- **No `.editorconfig`.** Mixed indentation will creep in across contributors.
- **No PR template / CONTRIBUTING.md.** The v2 conventions live in memory and CLAUDE.md
  — they should also live in `CONTRIBUTING.md` so a human contributor sees them.

### 9.13 The 20%-of-20% if only three things ship next

If the next pass picks three items:

1. **`make:crud` generator** (§9.1) — pays for itself on module #2.
2. **Atoms form kit + `<DataTable>` + `<AppMediaPicker>`** (§9.2, §9.3, §9.11) — every
   admin page collapses.
3. **TypeScript + laravel-data DTOs** (§9.4) — locks in everything above so it doesn't
   drift back.

Everything else in §9 is real but optional; these three move the "build a new module"
clock from an afternoon to under an hour.
