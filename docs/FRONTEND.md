# Frontend guide — building client sites on this backend

This backend is generic. Custom **frontends** are built per client. Public pages
are JSON widget lists; your Vue components decide how they look.

For the admin/editor side see `agents/skills/widgets` and `page-content`.
For launch checks (images, LCP, third-party scripts) see `launch-readiness`.

## 1. Mental model

| Layer | Owner | Where |
|---|---|---|
| Page content | Editors / you | `data/pages/{slug}.json` |
| Widget registry | Backend | `config/widgets.php` (drives admin fields + types) |
| Public look | Frontend | `resources/js/Components/Widgets/*.vue` |
| Shared chrome | Both | `PublicLayout.vue`, `layout` / `menus` / `settings` / `seo` props |

A published page is an ordered list of widgets. The admin edits `data`; your
components render `type` → component. Unknown types are skipped on purpose so
client overrides can ship without breaking older pages.

## 2. What a widget is

Envelope in page JSON:

```ts
{ id: string; type: WidgetType; visible?: boolean; data?: … }
```

- **Static widgets** — all content in `data` (hero, rich_text, cta, …).
- **Collection widgets** — `data.collection` tells `WidgetDataResolver` which
  module rows to load (`testimonials`, `faqs`, `team`, `latest_posts`).
  Resolved items arrive as `collectionData[widget.id]` on the page props.

Field types in the registry: `text`, `textarea`, `richtext`, `image`, `link`,
`boolean`, `number`, `select`, `repeater`, `collection`.

**Types of truth:** `php artisan widgets:types` writes
`resources/js/types/widgets.d.ts` from `config/widgets.php`. Never hand-edit
that file (same rule as `types.d.ts`). It also runs from `composer ide` /
`composer deploy`.

## 3. Render path

```
GET /{slug}
  → DynamicPageController
  → JsonDataService (data/pages/{slug}.json)
  → WidgetDataResolver (collection widgets → arrays)
  → Inertia Public/DynamicPage.vue
  → Components/Widgets/{PascalType}.vue
```

Optional LCP preload (`lcpPreload` prop) mirrors `AppImage` srcset for the
first image-bearing widget.

## 4. Building / overriding a widget component

1. File: `resources/js/Components/Widgets/Hero.vue` for type `hero`
   (snake_case type → PascalCase filename).
2. Register the component in `Public/DynamicPage.vue` `widgetMap`.
3. Type the prop:

```ts
import type { WidgetHeroData } from '@/types/widgets'

defineProps<{ data?: WidgetHeroData; items?: unknown[] }>()
```

Rules:

- Public images go through **`AppImage`** (srcset, dims, lazy/`eager`).
- **Hero owns the page H1**; every other shipped widget starts at H2.
- No cross-module imports; reuse Atoms/Molecules/Organisms only.

## 5. Adding a new widget type end-to-end

1. Add an entry to `config/widgets.php` (serializable — no closures).
2. `php artisan widgets:types`
3. Admin page editor picks up fields automatically.
4. Write `Components/Widgets/YourType.vue` + map it in `DynamicPage.vue`.
5. If it needs live rows, add a case in `WidgetDataResolver`.
6. `pnpm build` (vue-tsc) + `php artisan optimize`.

## 6. Shared Inertia props (public)

| Prop | Use |
|---|---|
| `layout` | `header.json` / `footer.json` |
| `menus` | Nested header/footer trees |
| `settings` | Whitelisted public keys only (`PUBLIC_SETTINGS`) |
| `siteLogo` | Media payload for the header logo (`AppImage`) |
| `seo` | Already applied in `PublicLayout` `<Head>` — do not re-emit titles |
| `organizationJsonLd` / `localBusinessJsonLd` | Schema blocks |

Theme tokens (`--color-brand-*`, font, radius) are CSS from Blade after
`@vite` — driven by Settings → Theme.

## 7. Styling & theming

- Tailwind v4 `@theme` in `resources/css/app.css`.
- Brand steps come from admin primary color (`BrandPalette`).
- Prefer semantic tokens over hard-coded indigo/purple for client brands.

## 8. Per-client workflow

**Change freely:** widget Vue components, PublicLayout regions, page CSS,
`data/pages/*.json` content.

**Do not break:** controller contracts, `WidgetDataResolver` return shapes,
admin permission/module system, `PUBLIC_SETTINGS` whitelist.

Before launch: follow `agents/skills/launch-readiness` (indexability,
AppImage, no bare analytics scripts without consent).

## 9. Shipped widget types

| Type | Kind | Notes |
|---|---|---|
| `hero` | static | Page H1 + optional background image |
| `rich_text` | static | TipTap HTML body |
| `feature_grid` | static | Repeater of title/description/icon |
| `stats` | static | Repeater of value/label |
| `cta` | static | Title/description/button (empty = hidden) |
| `image` | static | Single `AppImage` |
| `gallery` | static | Image repeater |
| `testimonials` | collection | Module rows → quote cards |
| `faqs` | collection | Page-wise / global / picked |
| `team` | collection | Team members + photos |
| `latest_posts` | collection | Blog cards |
| `contact_form` | static | Posts to contact → Enquiries |
| `custom_html` | static | Trusted HTML only |

See generated interfaces in `resources/js/types/widgets.d.ts` for exact fields.
