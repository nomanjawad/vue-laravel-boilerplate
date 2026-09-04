---
name: seo
description: RankMath-parity SEO pack — canonical/OG/title-template behavior in resolveSeo(), automatic schema (BlogPosting, BreadcrumbList, FAQPage, LocalBusiness), SERP preview + content checklist in editors, sitemap rules (lastmod, images, noindex). Use when touching PublicLayout Head tags, SeoService, HandleInertiaRequests::resolveSeo, page/post SEO sections, or /sitemap.xml.
---

# SEO pack (Phase 7.5)

Public meta is owned by `HandleInertiaRequests::resolveSeo()` → shared `seo`
prop → `PublicLayout.vue` `<Head>`. Do not re-emit title/canonical/robots on
individual public pages (Blog/Show and DynamicPage rely on the shared prop).

## resolveSeo() contract

Sources by route:

| Route | Content title / meta source |
|---|---|
| `home` / `page.show` | `data/pages/{slug}.json` → `seo` + page `title` |
| `blog.show` | Post columns (`meta_*`, `og_*`, `canonical_url`, `noindex`) |
| `blog.category` | Category name + description |
| `blog.index` / careers / case-studies | Hardcoded / model title fallbacks |

Rules:

- **Title template** (`seo_title_template`, default `%title% — %site_name%`)
  applies only when the page/post has **no** explicit meta title. Explicit
  titles are used as-is. Applied via `SeoService::applyTitleTemplate()`.
- **Canonical** is always absolute (`url()` / `SeoService::absoluteUrl()`).
  Optional override: page `seo.canonical` or post `canonical_url`.
- **noindex** ORs: `!config('template.indexable')` ∪ `site_noindex` ∪
  page/post `noindex`.
- **og:image** promoted to absolute (Media URLs are root-relative).
- Posts set `og_type=article` + `article:published_time` /
  `article:modified_time`.

`SeoData` fields: `site_name`, `title`, `description`, `og_image`,
`og_title`, `og_description`, `og_type`, `twitter_card`
(`summary_large_image`), `article_*_time`, `canonical`, `noindex`, `json_ld`.

Inertia `app.ts` title callback avoids double-suffix when the resolved title
already ends with ` — ${APP_NAME}`.

## Social meta (PublicLayout)

Always emits: description, canonical, robots (when noindex), og:type/site_name/
title/description/url/image, twitter:card/title/description/image, article
times when present. `og_title` / `og_description` fall back to meta
title/description.

## Automatic schema (`SeoService`)

Shared on every public page (Inertia props):

- `organizationJsonLd` — Organization from site settings (+ logo)
- `localBusinessJsonLd` — when `local_business_enabled=1` (SEO settings
  builder: type, street/city/region/postal/country, lat/lng, opening hours;
  phone/email fall back to Contact settings)

Per-page via `jsonLd` prop:

- **BlogPosting** — `BlogController::show` (`blogPosting()`; `article()` is an alias)
- **BreadcrumbList** — posts + dynamic pages (+ `PublicBreadcrumbs` UI)
- **FAQPage** — `DynamicPageController` emits for every visible `faqs` widget
  with resolved items
- **JobPosting** — careers (unchanged)
- Raw page `seo.json_ld` textarea still rendered verbatim after `<body>`

## Editors

- **SERP preview** — `SeoSerpPreview.vue` (title ≤60 / description ≤160,
  green/amber/red counters) on Pages + Posts SEO sections.
- **Content checklist** — `SeoContentChecklist.vue` on post Create/Edit
  (focus keyword client-side: title/slug/description/first paragraph, lengths,
  word count ≥300, image alts, H2, internal link). Not a gimmick score.
- Page/post SEO fields: meta title/description, canonical, og title/
  description/image, noindex; posts also have `focus_keyword`.

New post columns (migration + fillable + `PostData`): `canonical_url`,
`og_title`, `og_description`, `focus_keyword`.

New page JSON `seo` keys: `canonical`, `og_title`, `og_description` (plus
existing title/description/og_image/noindex/json_ld).

## Sitemap (`SitemapService`)

- Real `lastmod` (post `updated_at`, page-file mtime)
- `<image:image>` for featured/OG images (image namespace only when needed)
- Skips `seo.noindex` pages and `posts.noindex` posts
- Enumerates published `data/pages/*.json` + blog/careers/case-studies

## Ahrefs hygiene

- **Single H1:** Hero widget = H1; all other shipped widgets start at H2
  (`SectionHeading` uses H2). Post show has one H1 (post title).
- **Alt nudge:** `AppMediaPicker` + Media Index warn when uploading an image
  with empty alt; picker sends `alt_text` when filled.

## Settings (seed + FIELD_META)

`seo_title_template`, `local_business_enabled`, `local_business_type`,
`local_business_*` address/geo/hours — migration
`2026_09_04_000021_seed_seo_pack_settings` + `SettingSeeder` +
`SettingController::FIELD_META`. Whitelist-by-existence: missing rows no-op
on save.

## Related skills

- `launch-readiness` — SEO_INDEXABLE gate; this pack covers on-page meta/schema
- `blog` / `page-content` — editor SEO fields + resolveSeo sources
- `settings-and-media` — adding settings end-to-end
