---
name: modules-reference
description: Catalog of every shipped module — what each feature does, its admin/public URLs, permissions, models, and behavior quirks (subscribers, redirects & 404 log, custom code, audit log, users). Use to orient on which module owns a feature or how an existing feature behaves before changing it.
---

# Module catalog

Three kinds: **core virtual** (always on), **optional virtual** (toggleable at
/admin/modules, feature-flag fallback), **physical** (`app/Modules/*`). See the
create-module skill for how the machinery works.

## Core (always enabled, nav can still be hidden)

| Module | Admin URL | Permissions | Notes |
|---|---|---|---|
| users | /admin/users, /admin/audit-log | users.*, roles.view/update, modules.manage, audit_log.view | Escalation guards: only super-admins grant super-admin; can't delete yourself; one role per user via UI |
| settings | /admin/settings | settings.view/update | Tabbed editor; see settings-and-media skill |
| media | /admin/media | media.view/create/update/delete | WebP pipeline; see settings-and-media skill |
| menus | /admin/menus | menus.* | WP-style menus: locations (header/footer), nested tree, drag-drop reorder, add-from-content (pages/posts/…). Shared as nested `menus` prop → PublicLayout |
| page_content | /admin/pages, /admin/page-content/layout | page_content.view/create/update/delete | JSON pages + widget editor; header/footer layout JSON; see page-content + widgets skills |
| redirects | /admin/redirects | redirects.* | 301/302 map + 404 log (below) |
| custom_code | /admin/custom-code | custom_code.* | HTML/JS snippets (below) |
| subscribers | /admin/subscribers | subscribers.view/delete | Newsletter list (below) |
| settings (Cache) | /admin/system/cache | settings.update | Per-layer cache panel (pages/sitemap/settings/modules/redirects/views/all) |

## Optional virtual (flag defaults in config/template.php)

| Module | Admin | Public | Default |
|---|---|---|---|
| blog | /admin/posts, /admin/categories, /admin/tags | /blog, /blog/{slug} | on — see blog skill |
| teams | /admin/teams | none (rendered inside pages, e.g. About) | on |
| careers | /admin/careers | /careers, /careers/{slug} | off |
| case_studies | /admin/case-studies | /case-studies, /case-studies/{slug} | off |
| enquiries | /admin/enquiries | stores POST /contact leads | on via `contact_form` |

## Physical (`app/Modules/`)

Testimonials, Faqs, Events — admin CRUD. FAQs are **page-wise**: nullable
`page_slug` ties each FAQ to a `data/pages/{slug}.json` page (null = global).
The `faqs` widget modes are `current_page` (default, falls back to global),
`picked` (a chosen page slug), and `global`. Scope: `Faq::forPageSlug($slug)`
(not `forPage` — that name collides with Eloquent pagination).

## Behavior notes per feature

**Subscribers.** Public `POST /newsletter` (throttled 5/min, doNotCacheResponse)
upserts by lowercased email and clears `unsubscribed_at` (re-subscribe).
Admin: paginated list + streamed CSV export + delete. Sidebar badge =
`whereNull('seen_at')` (stamped on index view).

**Enquiries.** Public contact form (`POST /contact`) persists an `Enquiry` row
**after** the honeypot check and **before** `Mail::queue`, then emails the
admin. Phone validated via `propaganistas/laravel-phone` using
`contact_default_country` (ISO alpha-2, Contact settings tab). Admin inbox at
`/admin/enquiries`: search, read/unread filter, detail (marks read), mark
unread, bulk mark-read, delete, mailto reply. Sidebar badge =
`UnreadEnquiries` (`whereNull('read_at')`, Cache 60s).

**Redirects & 404s.** `HandleRedirects` runs globally **before routing**
(catches legacy URLs), reads a cached plain-array map, preserves query strings,
counts hits. Every GET 404 is aggregated into `not_found_logs` (dashboard +
redirects page show top offenders; creating a redirect clears its 404 rows).
`from_path` can't equal `to_path` (422).

**Custom Code.** Snippets with placement `head|body_start|body_end`, injected
verbatim into **public pages only** (never the admin panel) via
`CustomCodeService` in `app.blade.php` — server-side so trackers exist before
SPA hydration. Toggle uses `$request->boolean('is_active')` defaulting FALSE
deliberately (a missing key must never silently activate injected code).

**Audit log.** `LogsContentActivity` (dirty-only) on Post, Career,
CaseStudy, Team, CustomCode, Redirect, Menu, Setting, Event, Faq, Testimonial.
NOT on Subscriber/User. Auth events (login/logout/failed) logged with
passwords stripped. Viewer at /admin/audit-log (filter by log, causer, search).

**Global admin search** (`/` key or topbar) covers models each enabled module
lists under `searchable` in its manifest, permission-filtered.
