---
name: blog
description: Working with the blog — posts, categories, tags, publishing workflow, draft preview links, slugs and automatic 301s, per-post SEO/noindex, and public blog rendering. Use for any task touching /admin/posts, /admin/categories, /admin/tags, or the public /blog pages.
---

# Blog module

Virtual module `blog` (config/modules.php), depends on `media`, feature flag
`FEATURE_BLOG`. Admin routes in `routes/admin-blog.php`, public in
`routes/public-blog.php` — both behind `module:blog` middleware.

## Data model

- `Post` (`app/Models/Post.php`): `user_id, title, slug (unique), excerpt,
  body (longText), featured_image, status (draft|published|archived),
  published_at, meta_title, meta_description (500), og_image, canonical_url,
  og_title, og_description, focus_keyword, noindex (bool)`.
  Relations: user, **categories** (belongsToMany, pivot `category_post`),
  tags (belongsToMany, pivot `post_tag`).
  `scopePublished()` = status published AND `published_at <= now()`.
- `Category`: name, slug, description, `parent_id` (self-referencing),
  sort_order. Posts are many-to-many. Reserved slug `uncategorized` is the
  WP-style default (auto-attached when a post has zero categories; undeletable;
  children re-parent one level up on delete). `Tag`: name, slug only.
- Route binding is per-route: `{post:slug}` public, `{post:id}` admin — never
  add `getRouteKeyName()`.

## Admin workflow (`/admin/posts`, `/admin/categories`, `/admin/tags`)

- The three screens share one sidebar entry ("Blog") and are linked by the
  `BlogTabs` organism (real Inertia links, not client tabs).
- Permissions: `posts.view/create/update/delete/publish`, `categories.*`, `tags.*`.
- Publishing: setting status to published stamps `published_at` (only if not
  already set — republishing keeps the original date).
- **Draft preview**: the post editor exposes a 7-day
  `URL::temporarySignedRoute('blog.show', …)` link; `Public\BlogController::show`
  404s non-published posts unless the signature is valid.
- **Slug changes auto-create 301s**: updating a published post's slug calls
  `SlugService::redirectOldSlug('blog', $old, $new)` (same for careers).
  Empty slug on update keeps the old one; on create it's generated from title.
- **Categories**: single WP-style Index (add form left + hierarchy table right,
  edit via slide-over). No Create/Edit GET pages. Post editor uses a hierarchical
  checkbox tree (`PostCategoriesField`) + inline "+ Add New Category" that posts
  to `admin.categories.store` and partial-reloads `categories`. `update` blocks
  choosing itself OR any descendant as parent (cycle guard).
- Tags are inline CRUD on the index (no create/edit pages, name-only, slug
  always regenerated).
- Boolean switches: controllers re-read with `$request->boolean('noindex')`
  because an off switch may omit the key entirely.

## Public rendering

- `GET /blog` (`blog.index`) — published posts, `?search=` (title LIKE),
  12/page; passes categories with published counts.
- `GET /blog/category/{category:slug}` (`blog.category`) — archive of posts in
  that category **including descendants**; reuses `Public/Blog/Index` with an
  `archive` header; SEO title/description from the category.
- `GET /blog/{post:slug}` (`blog.show`) — draft-gated (see above), loads
  user/categories/tags, builds `jsonLd` via `SeoService::blogPosting()` +
  breadcrumbs (+ `PublicBreadcrumbs` UI), and up to 3 related posts (sharing
  any category, excluding self). Category chips link to `/blog/category/{slug}`.
  Document title / canonical / OG / noindex come from shared `seo`
  (`resolveSeo()`), not a page-local `<Head>`.
- Routes use `responsecache`; saving a Post/Category busts it via
  `ClearsResponseCache`. Sitemap emits category archive URLs + featured
  images; noindex posts are excluded.
- Per-post SEO fields: `meta_title`, `meta_description`, `og_image`,
  `canonical_url`, `og_title`, `og_description`, `focus_keyword`, `noindex`.
  Editors show SERP preview + content checklist (see `seo` skill).
- Per-post `noindex` composes with site-wide `site_noindex` / `SEO_INDEXABLE`
  (OR, never overridden).

## Known quirks (don't "fix" silently — flag to the user)

- Post body uses `AppBlockEditor` (TipTap). Featured/OG images use `AppMediaPicker`.
- Saving a post with zero categories auto-attaches Uncategorized (server-side).
- Title template (`seo_title_template`) applies only when `meta_title` is empty.

## Block editor: import + rich paste (F4)

`AppBlockEditor` (posts + Pages `richtext` widget) supports:

- **Tables** — TipTap Table (+ row/cell/header), `/table` slash command, +Col/−Col/+Row/−Row/Del table when the caret is in a table. Public body uses `.cms-prose` table CSS (`resources/css/pages/blog.css`).
- **Underline / text-align** — survive Word/Google Docs paste.
- **Placeholder** — `placeholder` prop drives TipTap Placeholder (empty editor hint).
- **Slash menu** — opens only at the start of an **empty** block (F11 #31; no hijack of `24/7`).
- **Rich paste** — HTML clipboard → `cleanPastedHtml` (strip mso/`<o:p>`/scripts) → `ingestEditorHtml` (base64 + remote images → `POST /admin/media/import`) → insert. Plain text paste uses TipTap default.
- **Import** toolbar (non-compact): `.docx` via `mammoth`, `.md` via `marked` (GFM tables). Confirm replace vs append when the editor has content. Google Docs: File → Download → Microsoft Word (.docx) (native `.gdoc` is not parseable).

**Media import endpoint:** `POST /admin/media/import` (`media.create`) accepts JSON `{ data_url }` or `{ url }` (+ optional `filename`/`alt_text`), returns `MediaData` JSON. Server: MIME whitelist, 10 MB cap, SSRF host/IP guard on URLs, WebP pipeline via `MediaService::importFromDataUrl` / `importFromUrl`.

Utils: `resources/js/Utils/cleanPastedHtml.ts`, `ingestEditorHtml.ts`, `importDocumentFile.ts`, `csrfHeaders.ts`.
