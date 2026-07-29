---
name: modules-reference
description: Catalog of every shipped module — what each feature does, its admin/public URLs, permissions, models, and behavior quirks (shop cart/checkout, subscribers, redirects & 404 log, custom code, audit log, users). Use to orient on which module owns a feature or how an existing feature behaves before changing it.
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
| menus | /admin/menus | menus.* | DB-driven header/footer nav (`menus` table → `menus` shared prop → PublicLayout). Top-level items only render; `parent_id` exists but no nesting UI |
| page_content | /admin/page-content(+/layout) | page_content.view/update | JSON page editor; see page-content skill |
| redirects | /admin/redirects | redirects.* | 301/302 map + 404 log (below) |
| custom_code | /admin/custom-code | custom_code.* | HTML/JS snippets (below) |
| subscribers | /admin/subscribers | subscribers.view/delete | Newsletter list (below) |

## Optional virtual (flag defaults in config/template.php)

| Module | Admin | Public | Default |
|---|---|---|---|
| blog | /admin/posts, /admin/categories, /admin/tags | /blog, /blog/{slug} | on — see blog skill |
| shop | /admin/products, /admin/orders | /shop, /cart, /checkout | on — see below |
| teams | /admin/teams | none (rendered inside pages, e.g. About) | on |
| careers | /admin/careers | /careers, /careers/{slug} | off |
| case_studies | /admin/case-studies | /case-studies, /case-studies/{slug} | off |

## Physical (`app/Modules/`)

Testimonials, Faqs, Events — admin CRUD only (`/admin/testimonials`, `/admin/faqs`,
`/admin/events`), simple schema (`title`, `body`, `is_active`, `published_at`;
Events also has a slug). Events ships a `Public/EventController` but **no public
route is registered** — wire one by hand (`{event:slug}`) or via the file-based
page router if needed.

## Behavior notes per feature

**Shop.** Cart is **session-based** (`CartService`, `session('cart')` keyed by
product id); the shared `cartCount` prop counts distinct product lines, not
quantities. Checkout requires auth; `OrderService` computes tax at **0%
(project-configurable)**, charges through `DummyPaymentService` (**a stub with
90% random success** — not a real gateway), decrements stock, generates
`ORD-YYYYMMDD-XXXXX` numbers. Admin orders are read-only except a status
PATCH (`pending|processing|completed|cancelled|refunded`) — no admin
create/delete; orders come from checkout only.

**Subscribers.** Public `POST /newsletter` (throttled 5/min, doNotCacheResponse)
upserts by lowercased email and clears `unsubscribed_at` (re-subscribe).
Admin: paginated list + streamed CSV export + delete.

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

**Audit log.** `LogsContentActivity` (dirty-only) on Post, Product, Career,
CaseStudy, Team, CustomCode, Redirect, Menu, Setting, Event, Faq, Testimonial.
NOT on Order/Subscriber/User. Auth events (login/logout/failed) logged with
passwords stripped. Viewer at /admin/audit-log (filter by log, causer, search).

**Global admin search** (`/` key or topbar) covers models each enabled module
lists under `searchable` in its manifest, permission-filtered.
