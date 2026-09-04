---
name: launch-readiness
description: Pre-launch and Lighthouse/PageSpeed rules for sites built on this boilerplate — the SEO indexability gate, AppImage/srcset discipline, gzip + media dimensions, LCP preload, third-party embed tradeoffs, and how to run a Lighthouse audit without false positives. Derived from a real production Lighthouse audit (skyhealthpro.com, 2026-08-11: Performance 82-88, Best Practices 58, SEO 69, Accessibility 91). Use before declaring any site "done"/"live", when adding images or third-party scripts to public pages, and when a client asks to "fix the Lighthouse score".
---

# Launch readiness & Lighthouse

A real site built on this boilerplate went live with an SEO score of 69 and
Best Practices of 58. Every finding below traces back to one of a small
number of root causes — most are now fixed in the boilerplate itself, the
rest are rules for whoever builds the next page or embeds the next
third-party script. Read this before touching public-facing pages, images,
or `<head>`/Custom Code content, and before telling anyone a site is ready
to launch.

## 1. The SEO gate — check this before anything else

> On-page SEO (canonical, OG, title templates, schema, sitemap images/noindex)
> lives in the **`seo` skill** (Phase 7.5 pack). This section is only the
> go-live **indexability** gate.

**Finding:** the live site scored 0/100 on `is-crawlable` (SEO weight 4×
every other check — this alone dropped the category from ~100 to 69). Three
signals all agreed the site was blocked: `<meta name="robots"
content="noindex, nofollow">`, an `X-Robots-Tag: noindex, nofollow` response
header, and a disallow in `robots.txt`. The site had been live for a while
with `SEO_INDEXABLE` never flipped to `true`.

**Root cause:** `SEO_INDEXABLE=false` is the correct, safe *default*
(`config/template.php`) — every unfinished/staging deploy of this
boilerplate should be unindexed. The bug is that nothing in the deploy
pipeline ever surfaced the flag's state, so "ship code" and "go live to
Google" silently diverged.

**Fix already in the boilerplate:** `php artisan template:doctor
--production` now has a dedicated, unmissable check
(`TemplateDoctor::checkIndexability()`) that prints a loud `!` line whenever
`SEO_INDEXABLE` is not `true` during a production doctor run. It's a warning,
not a failure — staging domains legitimately want this — but it can never be
silently missed in deploy logs again.

**Rule for every launch:** before telling anyone a site is live, run:
```bash
curl -sI https://<domain>/ | grep -i x-robots-tag   # must be ABSENT
curl -s  https://<domain>/robots.txt                 # must not Disallow: /
```
and confirm the rendered page source has no
`<meta name="robots" content="noindex...">`. If `SEO_INDEXABLE` needs
flipping: set it in `.env`, `php artisan config:cache`, then re-check. Do
this on every go-live, not just the first one — a staging→production config
copy can silently re-disable it.

## 2. Images — use `AppImage`, not ad-hoc `<img>`

Every non-third-party Performance finding on the audited site traced back to
images. Phase 10 folded the fixes into one component + the media pipeline.

### a. `AppImage.vue` — the single discipline point

**Use `resources/js/Components/Atoms/AppImage.vue` for every public/widget
image.** Do not hand-roll `<img>` tags on public pages (oversized originals,
missing `width`/`height`, wrong `loading`/`fetchpriority` — the same bugs
keep getting reintroduced).

What it does:
- **`srcset` / `sizes`** from media `variants` — `thumb` (400w), `md`
  (1200w), original (≤2000w). Pass a MediaData-like object
  `{ url, variants, width, height, alt_text }` (what the page editor stores
  for widget image fields) or a plain URL string (falls back to a single
  `src`, no srcset).
- **`width` / `height` attributes** from media dimensions (CLS). Override
  via props when needed.
- **Lazy by default** (`loading="lazy" decoding="async"`). Pass
  **`eager`** for the one above-the-fold / LCP image — that also sets
  `fetchpriority="high"`.
- Override `sizes` per layout (gallery tiles ≈ `33vw`, hero ≈ `100vw`).

Phase-4 widgets (`Hero`, `Image`, `Gallery`, `Team`, `LatestPosts`) already
render through it. Hero uses `:eager` on its background image.

### b. Serve at display size (pipeline already does this)
`MediaService` caps originals at 2000px and generates `md` (1200) /
`thumb` (400) WebP variants. **Anything dropped into `public/images/`**
bypasses that — route imports through `MediaService::importFromContents()`
or resize yourself. Export logos/favicons close to their max display size
(2× for retina).

### c. Media dimensions (width/height columns + variant dims)
`media.width` / `media.height` are recorded at upload; each variants JSON
entry is `{ path, width, height }` (legacy string paths still work —
`Media::variantPath()` / JS `variantPath()` unwrap both). Backfill existing
rows after migrate:
```bash
php artisan migrate
php artisan media:backfill-dimensions
```
Without dimensions, AppImage cannot emit HTML `width`/`height` and
Lighthouse flags `unsized-images`.

### d. Cache headers for images — already fixed
`public/.htaccess` `ExpiresByType` includes `image/webp` (+ avif, woff).
Every `MediaService` image is WebP — without that entry, content photos
get `cacheLifetimeMs: 0`. If you touch the caching block, keep WebP listed.

### e. LCP preload on dynamic pages
`DynamicPageController` resolves the first visible `hero` / `image`
widget's media (prefers the `md` variant) and passes `lcpPreload`.
`DynamicPage.vue` emits `<link rel="preload" as="image" href="…">` via
Inertia `<Head>`. The matching `<AppImage eager>` must **not** be
`loading="lazy"` (Hero already passes `eager`).

### f. Explicit `width`/`height` (when not using AppImage)
If you must use a raw `<img>` (e.g. logo in `PublicLayout`), set literal
`width`/`height` attributes. Guard empty settings URLs with `v-if` so the
browser never renders a zero-size `<img>`.

## 3. Compression (Gzip + Brotli)

`public/.htaccess` enables:
- **`mod_deflate`** — Gzip for HTML/CSS/JS/JSON/SVG/fonts
- **`mod_brotli`** — Brotli for the same types when the host has it
  (`IfModule` — no-op when absent)

Confirm on a live Apache host:
```bash
curl -sI -H 'Accept-Encoding: gzip, br' https://<domain>/ | grep -i content-encoding
```
Expect `br` or `gzip`. Nginx hosts configure this outside `.htaccess`
(`gzip on;` / `brotli on;`) — the `.htaccess` block is Apache-only.

## 4. Lazy iframes / map embeds

Contact map embed and Custom HTML widgets auto-inject `loading="lazy"` on
`<iframe>` tags that omit it. Still paste embeds **with**
`loading="lazy"` in the snippet (widget field placeholders show this).
Don't eagerly load third-party maps/videos above the fold unless they
*are* the LCP element (rare).

## 5. Third-party embeds have a real, unavoidable cost — plan for it

The audited site embeds a LeadConnector chat widget via the Custom Code
admin module. It alone caused:
- **`third-party-cookies`** (best-practices weight 5/26 — a huge single hit):
  the widget sets a `__cf_bm` cookie that will be blocked/flagged.
- **`inspector-issues`**: Chrome DevTools cookie-partitioning warnings for
  the same script.
- **`bf-cache` failure**: the widget registers an `unload` listener on the
  main frame, which unconditionally disables the back/forward cache for the
  entire site — this is a real regression (not testing noise), confirmed by
  the failure reason naming the site's own frame URL.
- A 6+ level deep network dependency chain (loader → chat-widget bundle →
  sub-chunks → session/geo API calls → font requests), each hop adding
  latency to anything sharing the connection queue with it.

**None of this is a boilerplate bug** — it's the fixed cost of embedding a
heavy third-party marketing tool. Before adding one via Admin → Custom Code:
- Load it `async`/`defer`, or behind a user-interaction trigger (open-on-click
  chat bubble) instead of eagerly on every pageview, so it doesn't compete
  with the page's own critical rendering path.
- Tell the client up front that embedding a chat/marketing widget will cost
  points on Best Practices (cookies, bfcache) that cannot be recovered
  without dropping the widget — this is a tradeoff to disclose, not a bug to
  chase.
- Don't spend time trying to "fix" a `bf-cache`/`third-party-cookies`
  finding whose failing resource is a third-party origin you don't control.

## 6. Run Lighthouse correctly, or you'll chase phantom bugs

On the audited report, `deprecations` (best-practices weight 5/26),
`unminified-javascript`, and most of `unused-javascript` were **entirely
attributed to an active Chrome extension** (`chrome-extension://…` script
URLs — a password manager/session recorder installed in the profile used to
run the audit), not the site's own code. Chasing these would have wasted
time "fixing" a page that was never actually broken.

**Rule:** always run Lighthouse from an Incognito window with no extensions
enabled (or via `https://pagespeed.web.dev/`, or the Lighthouse CLI with
`--chrome-flags="--incognito"`), and when reviewing a report someone else
ran, check whether a failing audit's `details.items[].url` points at
`chrome-extension://` before treating it as a real site defect. Cross-check
against `unused-javascript`/`unminified-javascript`: if the *first-party*
bundle entries show real wasted-byte numbers, that's real (see §7); if every
entry is a `chrome-extension://` URL, it's audit noise.

## 7. First-party JS/CSS bundle — know the shape of this boilerplate's trade-off

This boilerplate ships **one** JS entry (`resources/js/app.ts`) and **one**
global CSS bundle (`resources/css/app.css`) for the whole app — admin panel,
every module, and every public page all share the same two build outputs
(see `AGENTS.md`/`CLAUDE.md` on `resources/css/pages/*.css`). On the audited
site, `unused-javascript` measured ~69% of the first-party bundle (82 KB of
120 KB) unused on a single marketing page — expected, since that bundle also
carries every admin page's code. This is a known, accepted simplicity
trade-off for a small-to-medium site, not a regression to fix per-page. If a
project's bundle grows large enough that this becomes a real problem (large
`unused-javascript` savings on a first-party URL, not a third-party one),
the fix is route-level code-splitting via dynamic `import()` in the page
resolver — that's a deliberate architecture change, not something to
attempt as a quick "green the report" patch.

## 8. Accessibility patterns worth getting right the first time

Found on the audited page (all fixable in the Vue markup, not boilerplate
issues, but easy to reintroduce on any new page):
- **Color contrast**: a white-text button on a `#0fae96`-style brand-teal
  background measured 2.79:1 (need 4.5:1) — check button/CTA text contrast
  against the *actual* brand color chosen for a project, not just against
  the boilerplate's default palette. Muted body text (`#7c8c88` on
  `#f7faf9`) measured 3.35:1 for the same reason — "muted" gray text needs
  checking at the specific size/weight it's actually rendered at.
- **Heading order**: don't skip levels for visual sizing. An `<h4>` used
  because "it looks like the right size" when the section has no `<h2>`/`<h3>`
  ancestor breaks screen-reader navigation — pick heading level by document
  structure, then style it to look right with CSS, not the other way round.
- **ARIA on non-interactive elements**: `aria-label` on a bare `<div>` (e.g.
  a star-rating widget) is invalid without a `role` — either add
  `role="img"` (for a static rating display) or drop the `aria-label` in
  favor of visible text, since a `<div>` with no role isn't exposed to
  assistive tech in a way that makes the label reachable.

## Quick pre-launch checklist

```bash
# 1. Indexability (see §1)
php artisan template:doctor --production   # look for the SEO_INDEXABLE line
curl -sI https://<domain>/ | grep -i x-robots-tag   # must be absent
curl -s  https://<domain>/robots.txt

# 2. Compression (see §3)
curl -sI -H 'Accept-Encoding: gzip, br' https://<domain>/ | grep -i content-encoding

# 3. Media dims backfilled after deploy/migrate (see §2c)
php artisan media:backfill-dimensions

# 4. Run Lighthouse clean (see §6)
#    Incognito Chrome, or https://pagespeed.web.dev/, not your daily-driver profile.

# 5. Skim the report for first-party (not chrome-extension://, not third-party
#    widget domain) entries in: is-crawlable, cache-insight, image-delivery-
#    insight, unsized-images, lcp-discovery-insight, color-contrast,
#    heading-order, aria-*.
```
