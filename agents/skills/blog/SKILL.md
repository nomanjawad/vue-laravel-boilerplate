---
name: blog
description: Working with the blog — posts, categories, tags, publishing workflow, draft preview links, slugs and automatic 301s, per-post SEO/noindex, and public blog rendering. Use for any task touching /admin/posts, /admin/categories, /admin/tags, or the public /blog pages.
---

# Blog module

Virtual module `blog` (config/modules.php), depends on `media`, feature flag
`FEATURE_BLOG`. Admin routes in `routes/admin-blog.php`, public in
`routes/public-blog.php` — both behind `module:blog` middleware.

## Data model

- `Post` (`app/Models/Post.php`): `user_id, category_id, title, slug (unique),
  excerpt, body (longText), featured_image, status (draft|published|archived),
  published_at, meta_title, meta_description (500), og_image, noindex (bool)`.
  Relations: user, category, tags (belongsToMany, pivot `post_tag`).
  `scopePublished()` = status published AND `published_at <= now()`.
- `Category`: name, slug, description, `parent_id` (self-referencing),
  sort_order. `Tag`: name, slug only.
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
  `SlugService::redirectOldSlug('blog', $old, $new)` (same for products/careers).
  Empty slug on update keeps the old one; on create it's generated from title.
- Categories: `update` blocks choosing itself OR any descendant as parent
  (cycle guard — walks the tree). Tags are inline CRUD on the index (no
  create/edit pages, name-only, slug always regenerated).
- Boolean switches: controllers re-read with `$request->boolean('noindex')`
  because an off switch may omit the key entirely.

## Public rendering

- `GET /blog` (`blog.index`) — published posts, `?search=` (title LIKE),
  `?category={slug}`, 12/page; passes categories with published counts.
- `GET /blog/{post:slug}` (`blog.show`) — draft-gated (see above), loads
  user/category/tags, builds `jsonLd` via `SeoService::article()` +
  breadcrumbs, and up to 3 related posts (same category, excluding self).
- Both routes use `responsecache`; saving a Post busts it automatically via
  the `ClearsResponseCache` trait.
- Per-post `noindex` drives the robots meta on the show page; the site-wide
  `site_noindex` setting composes with it (OR, never overridden).

## Known quirks (don't "fix" silently — flag to the user)

- `@tiptap/*` packages are installed but **not wired up anywhere** — the post
  body is a plain `AppTextarea`. If a task says "the rich text editor", it
  doesn't exist yet.
- `featured_image` is a plain URL text input, not `AppMediaPicker`.
- The Post model has an `og_image` column the admin form doesn't expose.
- Posts Create/Edit use the older raw-`<form>` style rather than
  FormShell/AppFormField (see admin-crud skill for the preferred pattern).
