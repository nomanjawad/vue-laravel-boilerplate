# webTemplate → Universal Service-Site CMS: Full Roadmap

> Status: **in progress** · Plan of 2026-09-04 · Based on a four-part codebase audit (main @ cade35c) · Phase 9 landed
>
> Phase checklist: mark each phase done here as it lands.
> - [x] Phase 0 — Bug fixes
> - [x] Phase 1 — Remove ecommerce
> - [x] Phase 2 — Media Library v2
> - [x] Phase 3 — Sidebar + badges
> - [x] Phase 4 — Pages + widget editor
> - [x] Phase 4.5 — WordPress-style blog categories
> - [x] Phase 5 — Menus + header/footer
> - [x] Phase 6 — Enquiries, phone validation, page-wise FAQs
> - [x] Phase 7 — Cache panel + settings consolidation
> - [x] Phase 7.5 — SEO pack (RankMath parity)
> - [x] Phase 8 — Theme / color schemes
> - [x] Phase 9 — Blade audit + inode optimization
> - [x] Phase 10 — Performance pack (Lighthouse)
> - [ ] Phase 11 — Docs & skills upkeep (per-phase, see table)

## Context

webTemplate is being repositioned as the organization's **universal backend for service-based client websites** — a scaled-down, WordPress-like CMS. Custom frontends are built per client; the backend stays generic. A four-part audit (2026-09-04) found solid foundations (modules, RBAC, redirects, media pipeline) but three real bugs, a large ecommerce footprint to remove, a media library no content form actually uses, scattered/dead settings surfaces, and a single nuclear cache button.

**User decisions locked in:**
- Page content **stays in JSON files** (git-diffable, deploys with code) — reorganized into a WP-like Pages table + widget-based editor. One file per page under `data/pages/`.
- Execute the **entire roadmap, phased, without check-ins** between phases.
- Remove ALL ecommerce. Media library = single source of media. Enquiries stored with read/unread + nav badges. WP-like menus (drag-drop, nesting). Admin theme/colors synced with frontend.
- **Settings stay in tabs** (organized, driven from one registry).
- **FAQs are page-wise** — each FAQ belongs to a page (nullable = global), and the FAQ widget shows the current page's FAQs.
- **Phone fields validated by a real package** (`propaganistas/laravel-phone`), and **every form field gets a placeholder**.
- **Blog gets a Gutenberg-like block editor** (built on the already-installed TipTap — slash-command block menu, drag handles, media-library image blocks — storing HTML so existing `v-html` rendering and the WP importer keep working).
- **No legacy Blade** (audited: only the Inertia root, email templates, and error fallbacks exist — all required; anything unreferenced gets deleted), **inode-frugal on shared hosting** (response cache moves to the database store — the one unbounded file consumer), **WP-Rocket-style performance features** targeting green PageSpeed/Lighthouse (gzip, srcset + width/height via one `AppImage` component, LCP preload).

**Verification gate for every phase** (no test suite exists): `pnpm build` (runs vue-tsc) + `php artisan optimize` + exercise the feature in the browser (`composer dev`, MySQL via XAMPP on :3306).

**Repo conventions that constrain everything:** MySQL-only (varchar(191) for unique/indexed), pnpm only, no route() in JS (literal paths), DTOs in `app/Data` marked `#[TypeScript]` → regenerate with `php artisan typescript:transform`, `config/modules.php` must stay serializable (no closures — `optimize` must pass), Inertia `useForm()` reserves the key `data`.

---

## Phase 0 — Bug fixes

**0.1 `SettingService::update()` bypasses Eloquent** (`app/Services/SettingService.php:31-33` uses query-builder `->update()`): secrets stored plaintext (Crypt mutator skipped), `ClearsResponseCache` + `LogsContentActivity` never fire → public pages stale up to 7 days after settings edits, no audit trail.
Fix: fetch the existing rows, set `$setting->value = $v; $setting->save();` per key (keeps whitelist-by-existence). Existing plaintext secret rows re-encrypt on next save.

**0.2 Page-content saves don't bust the response cache** (`app/Http/Controllers/Admin/PageContentController.php` → `JsonDataService::put()`): cached HTML serves stale for up to 7 days after a green "saved" toast.
Fix: after `put()`, call `ResponseCache::clear()` + `Cache::forget('sitemap.xml')` (mirror `ClearsResponseCache::clearPublicCaches()`). Delete the dead legacy-key methods `JsonDataService::clearCache()`/`clearAllCache()` (they forget keys that no longer exist — confirmed no-ops).

**0.3 Media orphaning on user delete** (`database/migrations/2026_05_18_100005_create_media_table.php:13`: `user_id` non-nullable + `cascadeOnDelete` → deleting a user DB-cascades media rows, bypassing `MediaService::delete()`, orphaning files forever).
Fix: new migration → `media.user_id` nullable + `nullOnDelete`.

**0.4 Clear-cache button** (`resources/js/Pages/Admin/Dashboard.vue`): gate behind `settings.update` in the UI (route already requires it; editors currently get a 403 page). Full cache panel comes in Phase 7.

---

## Phase 1 — Remove ecommerce (inventory fully mapped)

**Delete entirely:**
- Models `app/Models/{Product,Order,OrderItem}.php`
- Controllers `Admin/{ProductController,OrderController}.php`, `Public/{ShopController,CartController,CheckoutController}.php`
- Services `app/Services/{CartService,OrderService,DummyPaymentService}.php`
- Route files `routes/admin-shop.php`, `routes/public-shop.php` + their `rescue(require ...)` lines in `bootstrap/app.php:41,49`
- Vue: **entire folder** `resources/js/Pages/Public/Shop/` (must delete the folder or `FileSystemPageRouter` auto-registers `/shop`), `Pages/Admin/Products/`, `Pages/Admin/Orders/`, `Pages/Public/Home/components/FeaturedProducts.vue`
- Keep the 3 create-migrations for history; add ONE new drop migration: `order_items` → `orders` → `products` (FK order).

**Surgical edits** (each verified to exist):
- `config/modules.php` — remove the `shop` block; `config/template.php` — remove `'shop'` feature; `.env.example` — remove `FEATURE_SHOP`
- `HandleInertiaRequests.php` — remove `shop_location/shop_currency/shop_currency_symbol` from PUBLIC_SETTINGS + the `cartCount` shared prop (line 144); `resources/js/types/inertia.d.ts` — remove `cartCount`
- `app/Data/SettingsData.php` — remove 3 shop props → `php artisan typescript:transform`
- `User::orders()`, `Category::products()` relations
- `SitemapService` shop block, `AdminSearchService` Product/Order configs + subtitle maps, `Public/HomeController` featuredProducts, `Admin/DashboardController` counts
- Vue: `Home/Index.vue` (import/interface/prop/usage), `Admin/Dashboard.vue` (2 tiles), `Admin/Settings/Index.vue` (Shop tab), `AdminLayout.vue` commerce section (superseded by Phase 3 anyway), `GlobalSearch.vue:154` placeholder
- Seeders: DemoSeeder ($products + import), SettingSeeder (3 rows), MenuSeeder (Shop link), PageMetaSeeder (shop.index)
- `TemplateInit.php` FEATURE_SHOP entry + prompt text
- **Cleanup migration for existing installs:** delete `site_settings` shop rows, `menus` `/shop` row, `page_metas` shop row, `modules` table `shop` row, orphaned `products.*`/`orders.*` permission rows (PermissionSyncer doesn't prune)
- Docs: README, CLAUDE.md/AGENTS.md, `agents/skills/*` shop refs. Then `composer ide`.

---

## Phase 2 — Media Library v2 (single source of media)

Prereq for widget editor image fields; do before Pages.

1. **Browse mode in `AppMediaPicker.vue`** (currently upload-only, 123 lines): add a "Choose from library" overlay — fetch `GET /admin/media` as JSON (add a `?format=json`/`wantsJson` branch or a new `admin.media.list` endpoint in `MediaController`), paginated grid with search + type filter, click to select → emits the same `MediaData` contract the upload path uses. Include `variants` in the payload; render `thumb` variants in grids (currently loads 2000px originals).
2. **Swap the raw URL text inputs for `AppMediaPicker`:** `Admin/Posts/{Create,Edit}.vue` (featured_image; also expose the existing `og_image` column), `Admin/Teams/{Create,Edit}.vue` (photo), `Admin/CaseStudies/{Create,Edit}.vue` (featured_image). Keep storing the URL string (no schema change now) — `media_id` FKs are a later enhancement, noted in Deferred.
3. **Media admin UI:** search (email-style `?search=` like SubscriberController), MIME-type filter, bulk select + bulk delete in `Admin/Media/Index.vue` + `MediaController::index` (its `Request $request` is currently unused).
4. **Logo/favicon actually render:** `<link rel="icon" href="{{ favicon }}">` in `resources/views/app.blade.php` from `site_favicon` setting; `PublicLayout.vue` header uses `site_logo` (shared settings prop already exposes both). Delete dead `resources/js/Components/Shared/BrandLogo.vue` (imported nowhere, points at nonexistent `/images/logo.svg`). Replace 0-byte `public/favicon.ico`.
5. **DemoSeeder** routes `placeholderImage()` through `MediaService::importFromContents()` so demo content creates Media rows (kills the `storage/demo/` parallel namespace).
6. Add `ClearsResponseCache` to the `Media` model (alt-text edits currently go stale).
7. `media:prune` artisan command: report/delete files on disk with no Media row and rows whose file is missing.

---

## Phase 3 — Sidebar re-organization + badge infrastructure

**New groups** (WP-style). Update `nav_group` in `config/modules.php` and the 3 physical `app/Modules/*/module.php` manifests:

| Group key | Label | Members |
|---|---|---|
| `content` | Content | Pages (page_content), Blog, Media |
| `collections` | Collections | Testimonials, FAQs, Events, Team, Case Studies, Careers |
| `inbox` | Inbox | Enquiries (Phase 6), Subscribers |
| `appearance` | Appearance | Menus, Header & Footer, Theme (Phase 8) |
| `system` | System | Settings, Users, Modules (hardcoded), Redirects, Custom Code, Audit Log |

`resources/js/Layouts/AdminLayout.vue`: update `SECTION_ORDER` (line 28) and the `buckets` literal (lines 64-82) — both must list every group (unknown groups silently fall back to `content`).

**Badge infrastructure:**
- Manifests must stay serializable → badge is a **class-string of an invokable**: `'badge' => \App\Support\NavBadges\UnreadEnquiries::class`. `ModuleManager::navFor()` (line 432, inside the `array_merge` defaults block) resolves it via the container when present; each resolver must be cheap (single indexed COUNT, `Cache::remember` 60s).
- Add `public ?int $badge = null` to `app/Data/ModuleNavEntry.php` → `php artisan typescript:transform`.
- `AdminLayout.vue`: extend the local `MenuItem` interface + render a small right-aligned pill after the label span (lines 166-198 render loop; `navItemClass` gets the label `flex-1`).
- **Subscribers badge now:** migration adds `seen_at` timestamp nullable to `subscribers`; badge = `whereNull('seen_at')->count()`; `SubscriberController::index()` stamps `seen_at = now()` on unseen rows after render data is built.

---

## Phase 4 — Pages table + widget editor (JSON-backed)

**Storage:** one file per page: `data/pages/{slug}.json` with shape:
```json
{ "title": "...", "status": "published|draft",
  "seo": { "title": "", "description": "", "og_image": "", "noindex": false, "json_ld": "" },
  "widgets": [ { "id": "w_abc123", "type": "hero", "visible": true, "data": { ... } } ] }
```
Migrate `data/home.json`, `about.json`, `contact.json` into this shape under `data/pages/` (map their current sections to widgets; keep the JSON keys' content). `header.json`/`footer.json` stay flat files (Phase 5).

**`JsonDataService`** (`app/Services/JsonDataService.php`): relax `put()`'s filename regex to allow one subdirectory (`^[a-z0-9_-]+(\/[a-z0-9_-]+)?$` — still rejects `..`), add `File::ensureDirectoryExists()` on write, add `delete()` and `list(string $dir)` helpers. `get()` already handles subpaths.

**Routing** (explored): a new `routes/public-pages.php` required in `bootstrap/app.php` **after** `FileSystemPageRouter->register()` — `Route::get('/{slug}', [Public\DynamicPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->middleware('responsecache')`, plus `/` mapped to slug `home`. Registered last → every explicit route and auto-router page wins; controller `abort(404)` when the JSON is missing/draft (keeps NotFoundLog working). Route itself is static so `route:cache` stays valid when pages are created. Retire the old explicit `HomeController/AboutController/ContactController` page-render paths in favor of the dynamic controller (contact keeps its POST route + feature flag; the contact page becomes widgets incl. a `contact_form` widget).

**Retire the half-dead DB pages system:** remove `/page/{page:slug}` route, `Public/PageController`, `Public/Page/Show.vue`, `Page` model usage in `SitemapService`; rewrite `ImportWordPress` to emit `data/pages/{slug}.json` (one Rich Text widget from the body) instead of `Page` rows. Also fully delete the dead `page_metas` surface: `PageMeta` model, `PageMetaSeeder` (+ its `DatabaseSeeder` line), drop-table migration.

**Widget registry:** `config/widgets.php` — each type: `key, label, icon, fields[]`. Field schema: `{ key, label, type, default, placeholder, options?, item_fields? }` with types `text, textarea, richtext, image, link, boolean, number, select, repeater, collection`. Every field declares a `placeholder` (rendered by the field renderer). `collection` = dynamic content (`source` module key, `mode: latest|picked`, `limit`) — this is how testimonials/FAQs/team/posts appear on pages and can be freely added/removed. Initial widgets: `hero, rich_text, feature_grid, stats, cta, image, gallery, testimonials, faqs, team, latest_posts, contact_form, custom_html`. The `faqs` widget defaults to the **current page's FAQs** (page-wise FAQ scoping, see Phase 6.5). Expose the registry to the editor via an Inertia prop (transform to a typed shape; add a DTO if practical).

**Server:** `Admin\PageController` (new, replaces `PageContentController`'s Pages half): `index` (list from `JsonDataService::list('pages')` + title/status/mtime), `create`/`store` (slug validation vs existing files + claimed routes), `edit`/`update` (validate widgets against the registry: known type, known field keys, scalar types; validate `seo.json_ld` as JSON), `destroy` (confirm; optionally create a 301 via existing `Redirect` model when the page had traffic — keep simple: just delete). Slug rename → file rename + `SlugService::redirectOldSlug`-style 301 (reuse `app/Services/SlugService.php` pattern). Cache: every write clears response cache + sitemap (per Phase 0.2). Keep permissions `page_content.view/update` + add `page_content.create/delete` in the manifest.
`HandleInertiaRequests::resolveSeo()`: replace the hardcoded `SEO_PAGE_MAP` with: if the matched route is the dynamic page route (or `/`), read the slug's JSON `seo` block. `SitemapService`: enumerate published `data/pages/*.json`.

**Editor UI:** `Admin/Pages/Index.vue` (DataTable — title, slug, status, updated, actions) + `Admin/Pages/Edit.vue`: **floating Save button** — build it as a reusable `AppFloatingSave.vue` (Molecule: fixed bottom-right, always visible while scrolling, dirty-state indicator + saving spinner, disabled when clean) used here **and on the blog post editor** (`Admin/Posts/{Create,Edit}.vue`, replacing their inline submit buttons); left column = widget cards (collapse/expand, visibility toggle, remove, drag-handle reorder), "Add widget" palette grouped static/dynamic; right column = page settings (title, slug, status) + SEO section (reuse the existing SEO section markup from `PageContent/Index.vue`, incl. `AppMediaPicker` for og_image). Field renderer component maps registry field types → existing atoms (`AppInput`, `AppTextarea`, `AppSwitch`, `AppMediaPicker`, repeater cards modeled on `JsonContentEditor`'s array-of-objects UI). **Reorder:** add `vuedraggable@next` (+ `sortablejs`, `@types/sortablejs`) via pnpm; if vue-tsc fights the types, fall back to up/down buttons (JsonContentEditor precedent). Keep `useForm` generic as `Record<string, any>` (recursive JSON types blow up vue-tsc — known gotcha) and never name a form field `data` (reserved) — use `content`/`widgets`.

**Block editor (Gutenberg-like):** build `AppBlockEditor.vue` (Organism) on the already-installed TipTap (`@tiptap/vue-3`, starter-kit, link, image):
- Slash-command menu (`/` opens a block palette: paragraph, headings H2/H3, bulleted/numbered list, quote, image, divider, HTML block) via TipTap's Suggestion utility.
- Per-block drag handles for reordering (TipTap drag-handle pattern; NodeSelection + draggable).
- Image block opens the Phase-2 media-library browser (inserts library URL + alt text from the Media row).
- Floating toolbar for inline marks (bold/italic/link) via BubbleMenu.
- Output stays **HTML** (TipTap `getHTML()`), so `Public/Blog/Show.vue`'s `v-html` rendering and `ImportWordPress` continue to work unchanged.
Use it for the blog post body in `Admin/Posts/{Create,Edit}.vue` AND as the `richtext` widget field type (a slimmer toolbar config there).

**Public rendering:** `resources/js/Pages/Public/DynamicPage.vue` — iterates widgets, maps type → component in `resources/js/Components/Widgets/{Type}.vue`, skips unknown types (per-client frontends override these components — that's the universal-backend contract). `DynamicPageController` resolves `collection` widget data server-side (a `WidgetDataResolver` service: testimonials/faqs/team/posts queries, guarded by `Schema::hasTable`/module-enabled checks). Seed the shipped widget components with markup ported from the current Home/About/Contact sections.

---

## Phase 4.5 — WordPress-style blog categories

Current state (verified): `posts.category_id` single nullable FK; post editor has a flat `<select>`; categories admin = separate Index/Create/Edit pages; no public category archives. Categories table already has `name/slug/description/parent_id/sort_order` — schema is close.

1. **Many-to-many**: migration creates `category_post` pivot (both FKs `cascadeOnDelete`, unique pair), copies every existing `posts.category_id` into it, then drops `posts.category_id`. `Post` model: replace `category(): BelongsTo` with `categories(): BelongsToMany`; update fillable. `PostController` store/update: `categories => ['array']`, `categories.* => exists:categories,id`, then `sync()`.
2. **Default category** (WP behavior): seed an "Uncategorized" category (slug `uncategorized`); on post save with zero categories, auto-attach it. Category delete: children re-parent one level up (WP behavior), posts left with no category fall back to Uncategorized; Uncategorized itself is undeletable.
3. **Post editor** (`Admin/Posts/{Create,Edit}.vue`): replace the select with a **hierarchical checkbox tree** (indent by depth) in a sidebar card + an inline "+ Add New Category" mini-form (name + parent) that posts to the existing `admin.categories.store` and refreshes via Inertia partial reload (`only: ['categories']`).
4. **Categories admin → one WP-style screen**: rewrite `Admin/Categories/Index.vue` as add-form (left: name, auto-slug, parent select, description) + hierarchy-indented table (right: name, slug, post count via `withCount('posts')`, edit inline/slide-over, delete). Retire the separate Create/Edit pages + their GET routes (`routes/admin-blog.php:30,34` — keep POST/PUT/DELETE).
5. **Public category archives**: add `GET /blog/category/{category:slug}` → `BlogController::category()` (posts in the category **including descendants**, paginated, reuses the blog index page with a category header from `name`/`description`); category chips on post cards/show link to archives; `SitemapService` emits archive URLs; archive `<title>`/meta from the category.
6. Tags stay as-is (already WP-like inline CRUD).

## Phase 5 — Menus like WordPress + Header/Footer wiring

**Menus** (`nesting exists in schema, not in UI`):
- `MenuController`: accept `parent_id` on store/update; new `PUT /admin/menus/reorder` accepting `[{id, parent_id, sort_order}, ...]` in one transaction.
- `Admin/Menus/Index.vue` rewrite: location tabs (header/footer from `config('template.menu_locations')`, default `['header'=>'Header','footer'=>'Footer']` — new config key), nested drag-drop tree (vuedraggable nested lists; depth cap 2), inline edit, **"add from content" panel**: pick a Page (`data/pages/*` slugs), a Post, or enter a custom link — inserts title+url.
- `HandleInertiaRequests` menus share: return roots with `children` (already has relations; currently shares a flat/roots-only list — build the tree, respecting `is_active` + sort).

**Header & Footer** (currently dead write-only files — wire them up):
- Share `data/header.json` + `data/footer.json` content via a `layout` shared prop in `HandleInertiaRequests` (public requests only).
- `PublicLayout.vue`: consume it — logo (via `site_logo` fallback → header.json logo), CTA button, footer columns, copyright (with `{year}`/`{site_name}` tokens), social toggle.
- Admin screen stays `PageContent/Layout.vue` (renamed nav label "Header & Footer", group `appearance`), upgraded: logo field uses `AppMediaPicker`, footer columns as repeater cards.

---

## Phase 6 — Enquiries module (stored leads, read/unread, badge)

- Migration: `enquiries` table — `name` varchar(191), `email` varchar(191) index, `phone` varchar(50) nullable, `subject` varchar(255), `message` text, `ip` varchar(45) nullable, `read_at` timestamp nullable index, timestamps. Model `app/Models/Enquiry.php` (no response-cache trait needed).
- **Phone validation package:** `composer require propaganistas/laravel-phone`. Contact form `phone` rule becomes `['nullable', 'phone:INTERNATIONAL,BD']`-style (lenient international + a default-country setting `contact_default_country` seeded in the Contact settings tab). Apply the same rule to the Teams admin phone-ish fields if any and any future phone inputs. Frontend: `type="tel"` + placeholder showing the expected format.
- **Placeholders everywhere:** sweep every form — public contact/newsletter forms and all admin Create/Edit screens (`Posts`, `Careers`, `CaseStudies`, `Teams`, `Users`, `Menus`, `Settings`, module CRUD forms, the widget field renderer) — so every `AppInput`/`AppTextarea` gets a meaningful `placeholder` (e.g. slug: `my-page-url`, email: `name@example.com`, phone: `+880 1XXX-XXXXXX`). `AppFormField`/atoms already pass attrs through; this is a content pass, not a component change.
- `Public/ContactController::store`: persist the enquiry **after** the honeypot early-return and **before** `Mail::to(...)->queue(...)` (mail failure must not lose the lead; keep the existing `ContactMessage` mailable untouched).
- Virtual module block in `config/modules.php`: `name` Enquiries, `nav_group => 'inbox'`, `feature_flag_fallback => 'contact_form'`, permissions `enquiries => [view, update, delete]`, nav icon `inbox`, badge resolver `UnreadEnquiries::class` (Phase 3 infra). Routes in `routes/admin.php` (core-module pattern).
- `Admin\EnquiryController` + `Admin/Enquiries/Index.vue`: table (unread rows bold/dot), search, filter read/unread, row click → detail (marks read), mark unread, bulk mark-read, delete, reply-via-`mailto:` link.

**6.5 Page-wise FAQs** (user requirement: "the faq will be page wise"):
- Migration in the Faqs physical module (`app/Modules/Faqs/Database/Migrations/`): add `page_slug` varchar(191) nullable + index to `faqs` (null = global/unassigned).
- `app/Modules/Faqs/Models/Faq.php`: add to fillable; scope `forPage(string $slug)`.
- FAQ admin (`app/Modules/Faqs/Resources/js/Pages/Admin/Faqs/{Index,Create,Edit}.vue` + `FaqController`): a "Page" select populated from `data/pages/*.json` slugs (plus "Global"); Index gains a page filter column.
- The `faqs` widget (Phase 4 registry): `mode: current_page (default) | picked page | global`; `WidgetDataResolver` filters `Faq::forPage($slug)` accordingly, falling back to global FAQs when the page has none.

---

## Phase 7 — Cache panel + settings consolidation

**Cache panel** (replaces the sledgehammer — `CacheController` currently does `Cache::flush()`, which also wipes permission cache and rate-limiter counters):
- New `/admin/system/cache` screen (System group) + rewritten `CacheController` with per-layer actions: Page cache (`ResponseCache::clear()`), Sitemap (`Cache::forget('sitemap.xml')`), Settings (`Cache::forget('site_settings')`), Modules registry (`ModuleManager::forgetCache()`), Redirects (`Cache::forget('redirects.map')`), Views (`view:clear`), Everything (all of the above — **not** `Cache::flush()`). Show last-cleared timestamps (store in cache keys). Dashboard button becomes "Clear page cache" quick action.
- `ModuleManager::enable/disable` also forget `sitemap.xml` (audit: sitemap doesn't rebuild on module toggle).

**Settings organization (stays tabbed — user requirement):**
- Keep the tabbed UI, but drive the tabs from the DB `group` column instead of the hardcoded `TABS` const in `Admin/Settings/Index.vue` (a `SettingGroupData` DTO: group key, label, fields with input type + **placeholder** — via a server-side field-meta map in `SettingController`; no schema change, single source in PHP). A setting seeded with a new group then appears automatically as a new tab instead of being silently invisible.
- New Theme group placeholder (Phase 8 fills it). Remove the now-dead Shop tab remnants.
- Surface `SEO_INDEXABLE` state read-only in the SEO tab with a loud banner when false ("site is invisible to search engines — set SEO_INDEXABLE=true in .env to go live"), mirroring `TemplateDoctor::checkIndexability()`.

---

## Phase 7.5 — SEO pack (RankMath-parity, Ahrefs-audit clean)

What exists: per-page/post title, description, noindex; raw JSON-LD textarea; Organization + JobPosting schema; sitemap; dynamic robots.txt; redirects UI + 404 log + auto-301s; RSS; GA4/GTM. Gaps vs RankMath features and Ahrefs site-audit standards, in order:

1. **Canonical tags (missing entirely — core Ahrefs check):** self-referencing `<link rel="canonical">` on every public page via `resolveSeo()`/`PublicLayout` `<Head>`, absolute via `url()`; optional per-post/per-page canonical override field in the SEO sections.
2. **Complete social meta:** og:title/og:description override fields (fall back to meta title/description), `twitter:card=summary_large_image`, `article:published_time`/`modified_time` on posts. Audit `PublicLayout`'s existing og/twitter block for completeness.
3. **Title templates:** SEO settings gain `seo_title_template` (default `%title% — %site_name%`) applied in `resolveSeo()` when a page/post has no explicit meta title (replaces the current bare-suffix behavior).
4. **SERP snippet preview + length counters** in both SEO sections (page editor + post editor): Google-style preview card, live character counters (title ≤60, description ≤160, green/amber/red).
5. **Lightweight content analysis** (scaled-down RankMath score, client-side only) on the post editor: optional focus keyword; checks = keyword in title/slug/description/first paragraph, title+description length, word count, every image has alt, has at least one H2 and one internal link. Rendered as a checklist, not a gimmick score.
6. **Automatic schema (biggest RankMath gap):**
   - `BlogPosting` JSON-LD on posts (headline, author, dates, featured image) — generated, no textarea needed;
   - `BreadcrumbList` on posts + dynamic pages (+ a public breadcrumbs component);
   - `FAQPage` schema auto-emitted by the FAQ widget (synergy with Phase 6.5);
   - **`LocalBusiness` schema builder** in SEO settings (business type, address, geo, phone, opening hours) — the highest-value schema for service businesses; emitted site-wide alongside Organization.
7. **Sitemap upgrades:** real `lastmod` (post `updated_at`, page-file mtime), `<image:image>` entries for featured images, exclude noindex content.
8. **Ahrefs hygiene:** single-H1 discipline (hero widget renders the page H1, all other widgets start at H2 — enforced in the shipped widget components); media library nudges when alt text is empty at upload.

Deferred within SEO: keyword rank tracking, internal-link suggestions, hreflang (single-language sites).

## Phase 8 — Theme / color schemes (admin + frontend synced)

- Settings group `theme` (seeded rows — whitelist-by-existence): `theme_primary_color` (hex), `theme_font` (select from a curated bunny-fonts list), `theme_radius` (sm/md/lg). 
- A small PHP color utility generates the 7 brand steps the CSS expects (`50,100,300,500,600,700,900` — the `@theme` block in `resources/css/app.css` is sparse) from the primary hex; clamp the 300/500 steps' lightness so brand text stays legible on the dark admin shell (warning documented in `resources/css/admin.css` header).
- Emit in `resources/views/app.blade.php` `<head>` **after** `@vite`: `<style>:root{ --color-brand-500: ...; --font-sans: ...; }</style>` from a cached settings read; when `theme_font` ≠ default, emit a bunny.net `<link rel="stylesheet">` (preconnect already present). Theme changes bust the response cache automatically once Phase 0.1 lands (Setting model events fire).
- Settings UI: color picker input (native `<input type="color">` in an `AppFormField`), font select with preview, radius select. Admin panel picks the change up for free (AdminLayout already uses `--color-brand-*` tokens; grays are remapped in `admin.css` and stay as-is).

---

## Phase 9 — Blade cleanup + inode optimization (shared-hosting hygiene)

**Blade audit (user asked to remove legacy Blade; verified 2026-09-04 there are no legacy page views):** `resources/views/` holds exactly 11 files — `app.blade.php` (the Inertia root shell, required), `emails/*` + `emails/layouts/branded` + `components/mail/message-branded` (mail must render server-side HTML for email clients — cannot be Vue), and `errors/{500,503,database}.blade.php` (shown when the app/DB is down, before Vue can boot — cannot be Vue). Action: keep all of these, delete nothing blindly; verify each email template is actually sent from somewhere (`Mail::` / Mailable `content()` references) and delete any unreferenced ones; ensure all emails use the shared `branded` layout.

**Inode optimization** (shared-hosting inode limits):
- **Response cache → database store**: `CACHE_STORE`/`SESSION_DRIVER` are already `database`, but `config/responsecache.php:20` defaults to `'file'` — the one unbounded inode consumer (7-day TTL, spatie file entries only reclaimed on read/clear). Set `RESPONSE_CACHE_DRIVER=database` in `.env` + `.env.example` and change the config default to `env('RESPONSE_CACHE_DRIVER', 'database')`. Cached pages become DB rows (0 inodes); MySQL handles it fine at this scale.
- One-time cleanup: schedule (in `routes/console.php`) a weekly `cache:prune-stale-tags`-equivalent — for the DB store, a simple scheduled `DB::table('cache')->where('expiration', '<', now()->timestamp)->delete()` command (Laravel's DB cache doesn't self-prune expired rows).
- **Logs**: switch `.env` `LOG_STACK` to `daily,console` (daily channel already configured with 14-day retention in `config/logging.php:93-97`) so the log can't grow into one giant file.
- **Deploy**: verify `.github/workflows/deploy.yml` rsync excludes `node_modules/`, `.git/`, `storage/framework/cache/data/` (it already excludes agent/dev paths); `media:prune` (Phase 2) handles orphaned media files.
- `template:doctor`: add an inode-awareness check — warn when `storage/framework/cache/data` holds > N files (catches misconfigured file response-cache on a live server).

## Phase 10 — Performance pack (WP-Rocket parity → green PageSpeed/Lighthouse)

Governed by the existing `launch-readiness` skill (derived from a real production audit). What Vite/the stack already gives free: JS/CSS minification + hashing, ESM defer, Tailwind purge, WebP conversion, bunny-fonts preconnect, 1-year Expires headers (incl. `image/webp`). What's missing, in Lighthouse-impact order:

1. **Gzip/Deflate**: `public/.htaccess` has Expires but **no compression block** — add `<IfModule mod_deflate.c>` with `AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml` (+ brotli IfModule for hosts that have it).
2. **`AppImage.vue` public component** (single image discipline point, replaces ad-hoc `<img>` tags on public pages/widgets): emits `srcset`/`sizes` from the media `variants` (thumb 400 / md 1200 / original 2000), `width`/`height` attributes (CLS), `loading="lazy" decoding="async"` by default with an `eager` prop (adds `fetchpriority="high"`) for above-the-fold use. All Phase-4 widget components render images through it.
3. **Media dimensions**: migration adds `width`/`height` (+ per-variant dims in the `variants` JSON) to `media`; `MediaService` records them at upload (Intervention already has the image open); a backfill command reads existing files' dimensions.
4. **LCP preload**: `DynamicPageController`/`DynamicPage.vue` — when the first visible widget has an image (hero), emit `<link rel="preload" as="image">` for it via Inertia `<Head>`.
5. **Lazy iframes/embeds**: the `custom_html`/map embeds get `loading="lazy"` guidance; contact map embed field documented to include it.
6. Verify with a real Lighthouse run per the launch-readiness skill (ignore browser-extension noise; the SEO gate = `SEO_INDEXABLE=true` in production, surfaced by the Phase-7 settings banner and `template:doctor`).

**Not chased** (per launch-readiness): third-party chat-widget Best-Practices penalties (cookies + bfcache) — a trade-off, not a bug; "100 everywhere with embeds present" is not a realistic target and the skill documents why.

## Phase 11 — Documentation, skills & agent-guide updates

The repo ships agent guides (`CLAUDE.md`/`AGENTS.md`, symlinked skills in `agents/skills/` → `.claude/skills`) that MUST track these changes, plus README. Update **at the end of each phase** (not as one big pass) so the guides never lie about the codebase:

| Phase | `agents/skills/` edits | Docs edits |
|---|---|---|
| 1 (ecommerce) | `modules-reference` (delete shop row + "**Shop.**" behavior section, fix frontmatter description), `dev-workflow` (cart/responsecache note line 53, `cartCount` in shared-props line 66), `settings-and-media` (Shop tab, line 36), `create-module` (shop in module list, line 13), `launch-readiness` (Shop/Show.vue + cart image lines 91,94), `blog` ("same for products/careers", line 35) | README (FEATURE_SHOP, module table, shop sections — lines 5,76,112,117,191,209,392), CLAUDE.md/AGENTS.md shop + `shop_location` mentions, `data/home.json` feature-card copy |
| 2 (media v2) | `settings-and-media` — rewrite the AppMediaPicker contract section (browse mode + select-from-library), document media search/filter/bulk, `media:prune`, logo/favicon rendering | CLAUDE.md: remove the "Wire logo/favicon" TODO (completed); README media section |
| 3 (sidebar) | `create-module` + `modules-reference` — new `nav_group` values (content/collections/inbox/appearance/system) + the `badge` manifest key (class-string invokable, must stay serializable); `admin-crud` — badge pill pattern in AdminLayout | CLAUDE.md architecture: sidebar groups list |
| 4 (pages/widgets) | **`page-content` — major rewrite**: `data/pages/{slug}.json` shape, widget registry (`config/widgets.php`), how to add a widget type + its public component, dynamic-page routing, retired systems (old PageContent pages half, DB `pages` route, `page_metas`); **new skill `agents/skills/widgets/SKILL.md`** — creating widget types, field schema, `WidgetDataResolver`, per-client frontend widget components, `AppBlockEditor` usage; `admin-crud` — `AppFloatingSave`, `AppBlockEditor`, field-renderer patterns | CLAUDE.md: rewrite the "JSON content pages" architecture bullet; README pages/editor docs |
| 4.5 (categories) | `blog` — categories many-to-many + default Uncategorized + checkbox tree + archives; block editor replaces the textarea note | README blog section |
| 5 (menus/layout) | `modules-reference` — menus capabilities (nesting, drag-drop, locations, add-from-content); `page-content` — header/footer now consumed via the `layout` shared prop | CLAUDE.md shared-props list (`layout`, menus tree shape) |
| 6 (enquiries/FAQs) | `modules-reference` — new Enquiries module entry + FAQ page-wise behavior; `settings-and-media` — `contact_default_country` setting | README modules table |
| 7 (cache/settings) | **`dev-workflow` — rewrite the caching-layers table** (per-layer panel, what busts what, response cache on DB store); `settings-and-media` — "adding a setting end-to-end" updated for the group-driven tabs + field-meta map | CLAUDE.md caching + settings conventions |
| 7.5 (SEO) | **new skill `agents/skills/seo/SKILL.md`** — canonical/OG/title-template behavior, schema generators (BlogPosting/Breadcrumb/FAQPage/LocalBusiness), the content checklist, sitemap rules; `launch-readiness` — cross-reference the SEO pack; `blog` + `page-content` — new SEO section fields | README SEO section; CLAUDE.md resolveSeo() conventions |
| 8 (theme) | `settings-and-media` — theme settings group; `admin-crud` — brand-token/lightness guidance (mirrors `admin.css` header warning) | README theming section |
| 9 (blade/inodes) | `dev-workflow` — `RESPONSE_CACHE_DRIVER=database`, daily logs, scheduled cache prune, new doctor check | README deploy/shared-hosting section; `.env.example` comments |
| 10 (performance) | **`launch-readiness`** — `AppImage` component as the image-discipline mechanism (replaces per-page img guidance), gzip block, LCP preload, media width/height | README performance section |

Also at the end: CLAUDE.md "Recently completed" gains a summary entry; the stale "TODO" section is emptied; `php artisan typescript:transform` + `composer ide` regenerate generated files, never hand-edit them.

## Deferred (explicitly out of scope for this roadmap)

`media_id` FKs / usage tracking (kept URL-string storage for now), trash/soft-deletes, revisions, post scheduling UI, role/permission editor UI, public routes for FAQs/Testimonials/Events (covered on pages via collection widgets), comments, per-client widget marketplace.

## Verification (each phase, and final)

1. `pnpm build` — vue-tsc must pass (no CI runs it).
2. `php artisan optimize` — must stay clean (config must remain serializable; duplicate route names fail it).
3. `php artisan template:doctor` — all green.
4. Browser pass with `composer dev`: exercise the changed screens (upload+browse media, create a page with static+dynamic widgets and view it publicly, write a blog post in the block editor — slash menu, drag a block, insert a library image — assign it two categories from the checkbox tree (one created inline) and check the public render + `/blog/category/{slug}` archive, confirm the floating Save button on the page editor, drag a menu item under a parent and check the public nav, submit the contact form with a bad then a valid phone number and see the enquiry + badge, assign an FAQ to a page and confirm the page's FAQ widget shows only it, change theme color and confirm both admin and public shift, clear each cache layer).
5. Fresh-install check at the end: `php artisan migrate:fresh --seed` + `template:init` flow still works.
