---
name: page-content
description: Static/public pages and the JSON content system — how home/about/contact content is stored in data/*.json, edited via the admin Pages panel, and how to add a brand-new public page end-to-end (auto-router or full admin-editable parity). Use when creating or editing public pages, page SEO, or the header/footer layout files.
---

# Public pages & JSON content

Static page content lives in `data/*.json` (never the DB) and is read through
`App\Services\JsonDataService`. Current files: `home.json`, `about.json`,
`contact.json` (each has a `seo` block) plus `header.json` / `footer.json`
(layout-only, no `seo`).

## How it fits together

- `JsonDataService::get('home')` → decoded array. Debug mode bypasses cache;
  production caches keyed on file mtime (auto-busts on edit). `put()` validates
  the filename, pretty-prints, writes atomically, preserves key order.
- Public controllers (`Public\HomeController` etc.) pass `data => $jsonData->get('home')`
  to the Inertia page. Pages live in `resources/js/Pages/Public/{Name}/Index.vue`
  with `defineOptions({ layout: PublicLayout })`.
- Admins edit these files at **/admin/page-content** (Pages) and
  **/admin/page-content/layout** (Header/Footer). `Admin\PageContentController`
  whitelists files in two constants: `PAGE_FILES` (home/about/contact) and
  `LAYOUT_FILES` (header/footer).
- The editor UI splits each page file into a dedicated SEO section (title,
  description, og:image via `AppMediaPicker`, noindex switch, JSON-LD textarea)
  and a generic recursive `JsonContentEditor` for everything else. The editor is
  schema-agnostic: strings→input/textarea, booleans→switch, numbers→number
  input, arrays of objects→add/remove/reorder cards, arrays of strings→lines.
- SEO resolution is global: `HandleInertiaRequests::resolveSeo()` maps the
  current route name to a JSON file via `SEO_PAGE_MAP`, reads its `seo` block,
  and falls back to site settings **using `?:` not `??`** — the editor writes
  `""` for cleared fields, not `null`. Site-wide `site_noindex` setting ORs with
  the page's own `noindex` (kill switch composes, never overridden).

## Adding a new public page

**Path A — file-based auto-routing (simplest).** `App\Support\FileSystemPageRouter`
(registered last in `bootstrap/app.php`) works like Next.js: create
`resources/js/Pages/Public/Pricing/Index.vue` and `GET /pricing` (route name
`pricing`) exists automatically, with `data => data/pricing.json` if that file
exists. An optional `page.php` sidecar in the folder overrides
path/name/middleware/data/layout. Explicit routes always win over the
auto-router. Production needs a fresh `route:cache`.

**Path B — full parity with home/about/contact (admin-editable + SEO).**
The auto-router does NOT touch the admin panel or SEO. Four independent
hardcoded lists must each be extended — missing one fails silently:

1. `data/pricing.json` — include a `seo: {title, description, og_image, noindex, json_ld}` block.
2. `Admin\PageContentController::PAGE_FILES` — add `'pricing' => 'Pricing'` (admin panel entry).
3. `routes/admin.php` — the `->where('file', 'home|about|contact|header|footer')`
   constraint on `page-content.update` must include `pricing`, or saving 404s.
4. `HandleInertiaRequests::SEO_PAGE_MAP` — add `'pricing' => 'pricing'`
   (route name → JSON file; the route name must match what the auto-router or
   your explicit route registers).

Also: `SitemapService::build()` hardcodes each static path — add
`$add('/pricing')` if the page should be in the sitemap. Navigation is
DB-driven (Admin > Menus), so add a menu row rather than editing any nav code.

Write an explicit controller + route in `routes/public.php` only when the page
needs DB data; otherwise rely on the auto-router.

## Gotchas

- **`header.json` / `footer.json` are currently write-only** — the admin can
  edit them but `PublicLayout.vue` renders its header/footer from DB menus
  (`menus` shared prop) + `settings`, not from these files. Don't assume edits
  there appear on the site; wire them up first if a task needs it.
- Inertia's `useForm()` reserves the key `data` — the page-content forms name
  the field `content` for this reason. Keep that convention.
- `PageContentController::update()` validates only `content.seo.json_ld` (must
  be valid JSON); everything else is accepted as-is — the JSON shape IS the
  schema, so don't reorder/rename keys casually; the Vue pages read them.
- Public GET routes use the `responsecache` middleware — after changing
  content-affecting code run `php artisan responsecache:clear` (model saves
  with the `ClearsResponseCache` trait handle it automatically).
- Contact form: honeypot field `website` (silent success), throttled 5/min,
  queues `App\Mail\ContactMessage` to the `contact_email` setting. The form
  fields are hardcoded in `Public/Contact/Index.vue` — `contact.json`'s
  `form_fields` array is not consumed dynamically.
- Robots/indexing: everything is `noindex` unless `SEO_INDEXABLE=true`
  (`config('template.indexable')`); `/sitemap.xml` 404s when not indexable.
