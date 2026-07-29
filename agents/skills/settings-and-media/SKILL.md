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
   `2026_07_29_120000_add_settings_tab_fields.php`).
2. Same entry in `database/seeders/SettingSeeder.php`.
3. Add the field to the right `TABS` entry in
   `resources/js/Pages/Admin/Settings/Index.vue` (tabs are defined there, not
   by the DB `group`): General / Contact / Social / Shop / SEO & Analytics.
4. Only if the public site needs it: add the key to
   `HandleInertiaRequests::PUBLIC_SETTINGS` **and** a property on
   `app/Data/SettingsData.php`, then `php artisan typescript:transform`.

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
On upload it POSTs FormData to `/admin/media`; `MediaController::store`
flashes a `MediaData` DTO which `HandleInertiaRequests` forwards as
`flash.media`; the picker reads that and emits it as the new model value.
Preview resolves `variants.thumb ?? variants.md ?? url`. In Settings the
value is adapted to/from a plain URL string. If the picker "doesn't update
after upload", check the flash → `FlashData` → shared-prop chain first.
