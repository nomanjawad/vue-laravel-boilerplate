---
name: widgets
description: Creating and extending page widget types — config/widgets.php registry, field schema, WidgetDataResolver for collection widgets, public Components/Widgets/* renderers, AppBlockEditor for richtext, AppImage discipline. Use when adding a widget type, changing widget fields, wiring dynamic collections, or customizing per-client frontend widget components.
---

# Page widgets

Pages store an ordered `widgets[]` array in `data/pages/{slug}.json`. The
admin editor (`Admin/Pages/Edit.vue`) renders fields from
`config/widgets.php`; the public site maps `type` →
`resources/js/Components/Widgets/{PascalCase}.vue` via
`Public/DynamicPage.vue`. See the **`page-content`** skill for file shape,
routing, and SEO.

## Registry (`config/widgets.php`)

**Must stay serializable** (no closures) — `config:cache` / `optimize` fail
otherwise.

Each entry:

```php
'hero' => [
    'key'   => 'hero',
    'label' => 'Hero',
    'icon'  => 'sparkles',          // AppIcon map name
    'fields' => [
        ['key' => 'title', 'label' => 'Title', 'type' => 'text',
         'default' => '', 'placeholder' => '…'],
        // …
    ],
],
```

### Field types

| Type | Editor control | Notes |
|---|---|---|
| `text` / `link` / `number` | `AppInput` | link = URL string |
| `textarea` | `AppTextarea` | |
| `richtext` | `AppBlockEditor` | TipTap HTML string |
| `boolean` | `AppSwitch` | |
| `image` | `AppMediaPicker` | Persists MediaData-shaped object (`url`, `variants`, `width`, `height`, `alt_text`) for `AppImage` |
| `select` | `AppSelect` | needs `options` on the field |
| `repeater` | add/remove cards | needs `item_fields` (same field schema) |
| `collection` | mode / limit / page_slug | needs `source` (`testimonials`/`faqs`/`teams`/`blog`); resolved server-side |

Every field should have a `placeholder`. Defaults seed new widget instances.

### Shipped types

**Static:** `hero`, `rich_text`, `feature_grid`, `stats`, `cta`, `image`,
`gallery`, `contact_form`, `custom_html`.

**Collection (dynamic):** `testimonials`, `faqs`, `team`, `latest_posts`.

FAQs collection modes: `current_page` (default; falls back to global),
`picked` (page slug or ids), `global`.

## Adding a widget type (checklist)

1. **Registry** — add the entry in `config/widgets.php` with fields +
   placeholders.
2. **Public component** — `resources/js/Components/Widgets/MyType.vue`
   (`<script setup lang="ts">`). Name = PascalCase of the key
   (`rich_text` → `RichText.vue`). Register it in the map inside
   `Public/DynamicPage.vue` if that file uses an explicit import map
   (it does — unknown types are skipped, not auto-imported).
3. **Images** — use `AppImage` (eager only for LCP/hero). See
   `launch-readiness`.
4. **Collection source** — if the widget pulls live DB rows, extend
   `App\Services\WidgetDataResolver` with a guarded resolver
   (`Schema::hasTable` + `ModuleManager::enabled()`). Return plain arrays.
5. **Headings** — only `hero` may emit `<h1>`; other widgets start at H2
   (`SectionHeading` / local markup).
6. **Verify** — `pnpm build` + `php artisan optimize` (config must cache).

Per-client frontends override `Components/Widgets/*` — that is the
universal-backend contract. Keep registry field keys stable; rename = content
migration.

## WidgetDataResolver

`DynamicPageController` calls `WidgetDataResolver::resolve($widgets, $slug)`.
Returns `array<widgetId, list<item>>` merged into the page props as
`widgetData`. Disabled/missing modules yield `[]`, never a 500.

When adding a collection source: match on `$type`, read
`$data['collection']`, apply mode/limit/ids, map models to arrays.

## AppBlockEditor

Organism on TipTap (`@tiptap/vue-3`): slash-command block menu (empty-block
gated), tables, doc import, rich paste, media-library image blocks. Stores
**HTML** (posts `body` and widget `richtext` fields) so `v-html` + the WP
importer keep working. **Per-block drag handles are not shipped** — page
widget cards reorder with ↑/↓ in `Admin/Pages/Edit.vue`. Used on
`Admin/Pages/Edit.vue` (richtext fields) and `Admin/Posts/{Create,Edit}.vue`.

Typed public widget props: `resources/js/types/widgets.d.ts` (from
`php artisan widgets:types`). Frontend override guide: `docs/FRONTEND.md`.

## AppFloatingSave

Molecule: fixed bottom-right Save — dirty indicator, saving spinner,
disabled when clean. Used on the page editor and post Create/Edit
(replaces inline submit bars).

## Related skills

- `page-content` — JSON shape, DynamicPage routing, header/footer
- `seo` — FAQPage schema from visible `faqs` widgets; BreadcrumbList
- `launch-readiness` — `AppImage`, LCP preload, lazy iframes
- `admin-crud` — form atoms the field renderer maps to
