---
name: page-content
description: JSON-backed public pages (data/pages/{slug}.json), widget editor, header/footer layout files, and page SEO. Use when creating or editing public pages, page SEO fields, DynamicPage routing, or header/footer layout.
---

# Public pages & JSON content

Pages live as one file each under `data/pages/{slug}.json` (never the DB),
edited at **/admin/pages**. Shape:

```json
{ "title": "...", "status": "published|draft",
  "seo": { "title": "", "description": "", "og_image": "", "og_title": "",
           "og_description": "", "canonical": "", "noindex": false, "json_ld": "" },
  "widgets": [ { "id": "w_…", "type": "hero", "visible": true, "data": { … } } ] }
```

`data/header.json` / `data/footer.json` stay flat files (Header & Footer
admin screen at `/admin/page-content/layout`) and are shared via the
`layout` Inertia prop (`HandleInertiaRequests`). Menus (DB) are separate —
see `menus` in the modules-reference skill.

## How it fits together

- `JsonDataService::get('pages/home')` → decoded array. Debug bypasses cache;
  production caches keyed on file mtime. `put()` / `list('pages')` / `delete()`.
- Admin: `Admin\PageController` (index/create/edit/update/destroy) →
  `Admin/Pages/{Index,Edit}.vue`. Editor uses `AppBlockEditor` for richtext
  fields, `AppMediaPicker` for images, `AppFloatingSave` for persist.
  Widget field schema comes from `config/widgets.php` (passed as a prop).
- Public render: `DynamicPageController` → `Public/DynamicPage.vue` (widget
  map in `Components/Widgets/*`). Route `/` = slug `home`; `/{slug}` last in
  the stack. Draft/missing → 404. Collection widgets get rows from
  `WidgetDataResolver` (see **`widgets`** skill).
- SEO resolution is global: `HandleInertiaRequests::resolveSeo()` reads the
  page's `seo` block (plus title template / canonical / OG overrides). See the
  **`seo`** skill for the full RankMath-parity contract. Fallbacks use `?:`
  not `??` — the editor writes `""` for cleared fields. Site-wide
  `site_noindex` ORs with the page's own `noindex`.
- Page editor SEO section: SERP preview, meta title/description, canonical,
  OG title/description/image, noindex, optional raw JSON-LD. Automatic
  BreadcrumbList + FAQPage schemas are generated server-side (not the textarea).

## Widget registry (summary)

Full create/extend guide: **`widgets`** skill. Registry at
`config/widgets.php` — serializable only. Field types: text, textarea,
richtext, image, link, boolean, number, select, repeater, collection.
Shipped static + collection widgets; public components under
`resources/js/Components/Widgets/`.

## Adding a new public page

Prefer **Admin → Pages → New** (creates `data/pages/{slug}.json`). The dynamic
route already covers every slug; no route/controller edit needed. Add a menu
item under Admin → Menus for navigation. Sitemap picks up published pages
automatically (skips `seo.noindex`).

Reserved slugs (blog, careers, admin, …) are blocked in `PageController`.

## Retired systems (do not revive)

- Old flat `data/home.json` / `about.json` / `contact.json` at repo root of
  `data/` — migrated into `data/pages/{slug}.json` with widgets.
- Old `Admin/PageContent/Index.vue` pages half + recursive
  `JsonContentEditor` for page bodies — replaced by the widget editor.
  Header/Footer layout screen remains (still JSON, not widgets).
- DB `pages` table / Eloquent `Page` model — removed; content is JSON files.
- `page_metas` module / `PageMetaController` / `SeoService::getMetaForRoute()`
  — fully removed. SEO lives in each page JSON's `seo` block + settings +
  derived canonical/OG in `resolveSeo()`.

## Gotchas

- Inertia `useForm()` reserves the key `data` — page forms use `widgets` /
  `content`, never `data`.
- `seo.json_ld` must be valid JSON when non-empty (validated on save).
- Public GET routes use `responsecache` — writes clear it + `sitemap.xml`.
- Hero widget is the page H1; other widgets start at H2 (Ahrefs single-H1).
- Contact form widget posts to the existing contact route; enquiries land in
  the Enquiries inbox.
- Robots/indexing: everything is `noindex` unless `SEO_INDEXABLE=true`
  (`config('template.indexable')`); `/sitemap.xml` 404s when not indexable.
