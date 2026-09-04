---
name: settings-and-media
description: Site settings (site_settings table, SettingService whitelist-by-existence, PUBLIC_SETTINGS browser exposure, secret encryption, adding a new setting end-to-end) and the media pipeline (upload rules, WebP conversion, variants, root-relative URLs, AppMediaPicker contract). Use when touching /admin/settings, /admin/media, Setting/Media models, or image handling.
---

# Site settings

Rows in `site_settings` (`key` unique, `value`, `type`, `group`, `is_secret`),
model `App\Models\Setting`, service `App\Services\SettingService`.

## Rules that bite

- **Whitelist-by-existence**: `SettingService::update()` only UPDATEs existing
  rows. A key with no seeded row **silently no-ops on save**. Every new
  setting needs both a backfill migration (`Setting::firstOrCreate([...])`)
  AND a `SettingSeeder` entry for fresh installs.
- **PUBLIC_SETTINGS** (`HandleInertiaRequests`) is the ONLY path a setting
  reaches the browser — the table may hold secrets (SMTP, API keys). Never
  widen the whitelist casually; every key on it is visible to every visitor.
- **Secrets**: `is_secret` rows encrypt `value` at rest (model accessor),
  render as password inputs, are redacted (`''`) before Inertia, and a blank
  submission keeps the existing value. Note `Setting::get()`'s cached pluck
  bypasses decryption for secrets — read secrets via a direct model query.
- Settings are cached 1h under key `site_settings` (plain array); writes bust it.
- Model uses `ClearsResponseCache` — saving a setting clears the public
  response cache automatically.

## Adding a new setting (checklist)

1. Backfill migration: `Setting::firstOrCreate(['key' => 'x'], ['value' => '',
   'type' => 'string', 'group' => 'general'])` (pattern:
   `2026_07_29_120000_add_settings_tab_fields.php`). Pick an existing
   `group` (`general` / `contact` / `social` / `seo` / `theme`) or invent a
   new one — **tabs are driven from the DB `group` column**, so a new group
   appears as a new tab automatically.
2. Same entry in `database/seeders/SettingSeeder.php`.
3. Add field meta in `Admin\SettingController::FIELD_META` (label, `input`
   type, `placeholder`, optional `help`). Without a map entry the UI still
   shows the key with a humanised label. Group labels live in
   `GROUP_LABELS` / `GROUP_ORDER` on the same controller.
4. Only if the public site needs it: add the key to
   `HandleInertiaRequests::PUBLIC_SETTINGS` **and** a property on
   `app/Data/SettingsData.php`, then `php artisan typescript:transform`.
   (`contact_default_country` is admin-only — do **not** put it on PUBLIC_SETTINGS.)

Tabs: General / Contact / Social / SEO & Analytics / Theme. The SEO tab also
shows a loud read-only banner when `SEO_INDEXABLE` is false.
Contact tab includes `contact_default_country` (ISO alpha-2, default `BD`)
for `propaganistas/laravel-phone` validation on the public contact form.

## Theme group (admin + public synced)

Seeded keys (`group` = `theme`):

| Key | UI | Default |
|---|---|---|
| `theme_primary_color` | color picker + hex | `#6366f1` |
| `theme_font` | select (curated bunny fonts) | `instrument-sans` |
| `theme_radius` | select sm/md/lg | `md` |

`App\Support\BrandPalette` expands the primary hex into the 7 brand steps
(`50,100,300,500,600,700,900`) and **clamps** 300/500 lightness so brand
text stays legible on the dark admin shell. `App\Support\Theme` emits
`:root{ --color-brand-*; --font-sans; --radius-* }` in
`resources/views/app.blade.php` **after** `@vite`. When `theme_font` is not
the Vite-bundled default, a bunny.net stylesheet link is added (preconnect
already present). Theme keys are **not** on `PUBLIC_SETTINGS` — they reach
the browser only via that Blade CSS emit. Saving a setting busts the
response cache via the `Setting` model (`ClearsResponseCache`).

# Media

Upload pipeline (`Admin\MediaController` → `App\Services\MediaService`):

- Validation: max 10 MB, mimetypes JPEG/PNG/WebP/GIF/PDF. **SVG is
  deliberately excluded** (inline-script XSS) — don't add it.
- JPEG/PNG/WebP are re-encoded to **WebP quality 82**, capped at 2000px wide
  (re-encoding strips EXIF/GPS), stored on the `public` disk under
  `media/Y/m/{random}.webp`, with variants `md` (1200px) and `thumb` (400px)
  — variants never upscale. GIF/PDF stored as-is.
- `Media::url` returns a **root-relative** path when the URL host matches
  `app.url` (works on any serving port + CSP `img-src 'self'`); external
  S3/CDN URLs stay absolute. Consumers needing absolute URLs (og:image,
  sitemap) promote via `url()`.
- Path helpers: backend `Controller::imageUrl()` and its JS mirror
  `useImageUrl().toImageUrl` — pass-through absolute//-prefixed,
  `uploads/…` → `/uploads/…` (WordPress imports), else `/storage/…`.

## AppMediaPicker contract

`v-model` holds a media row `{id, url, variants?, alt_text?}` (or bare id).
**Upload path:** POSTs FormData to `/admin/media`; `MediaController::store`
flashes a `MediaData` DTO which `HandleInertiaRequests` forwards as
`flash.media`; the picker reads that and emits it as the new model value.
**Browse path:** "Choose from library" opens an overlay that fetches
`GET /admin/media?format=json` (paginated, `?search=`, `?type=image|pdf`).
Clicking a tile emits the same MediaData-shaped object. Grids render
`variants.thumb` (via `useImageUrl`) — never the 2000px original.
Preview resolves `variants.thumb ?? variants.md ?? url`. In Settings / content
forms the value is adapted to/from a plain URL string. If the picker
"doesn't update after upload", check the flash → `FlashData` → shared-prop
chain first.

## Media admin

`Admin/Media/Index.vue`: search (`?search=`), MIME filter (`?type=image|pdf`),
bulk select + `POST /admin/media/bulk-destroy`. Thumbnails use variant thumbs.

## Logo / favicon

`site_favicon` → `<link rel="icon">` in `resources/views/app.blade.php`.
`site_logo` → header image in `PublicLayout.vue` (falls back to site name text).

## Housekeeping

- `Media` model uses `ClearsResponseCache` (alt-text edits bust public pages).
- `php artisan media:prune` / `--delete` — orphan files on disk + rows whose
  file is missing.
- Demo placeholders go through `MediaService::importFromContents()` (real Media
  rows; no `storage/demo/` parallel namespace).
