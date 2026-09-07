# Universal frontend-build prompt

Copy everything inside the block below into a fresh AI agent session (Claude Code
or similar) running **inside a clone of this repo**, after `php artisan template:init`
has been run. Replace every `{{PLACEHOLDER}}` with the client's real values —
delete any optional line that doesn't apply.

---

```
You are building the custom public frontend for a client website on top of the
webTemplate universal CMS backend (Laravel 13 + Vue 3 + Inertia v3 + Tailwind v4,
already set up in this repo). The backend is generic and stays untouched — your
job is ONLY the public-facing look: widget components, the public layout, theme,
and page content.

## Client brief

- Client / site name: {{CLIENT_NAME}}
- Industry / what they do: {{INDUSTRY_AND_SERVICES}}
- Target audience: {{TARGET_AUDIENCE}}
- Design direction: {{DESIGN_DIRECTION}}  (e.g. "minimal & premium, lots of whitespace", "bold & colorful", "corporate & trustworthy")
- Reference sites the client likes: {{REFERENCE_URLS}}  (optional)
- Brand primary color (hex): {{BRAND_HEX}}
- Preferred font: {{FONT_NAME}}  (must exist on bunny.net fonts; otherwise pick the closest)
- Border radius feel: {{RADIUS}}  (sm = sharp / md = balanced / lg = soft)
- Logo / brand assets: {{ASSET_LOCATION_OR_UPLOAD_NOTE}}
- Pages the site needs: {{PAGE_LIST}}  (e.g. home, about, services, contact)
- Modules to enable: {{MODULE_LIST}}  (choose from: blog, testimonials, faqs, events, teams, case_studies, careers, subscribers)
- Anything custom the design needs that the shipped widgets can't express: {{CUSTOM_SECTIONS_OR_NONE}}
- Copy/content source: {{CONTENT_SOURCE}}  (e.g. "client doc at …", "write placeholder copy for the industry")
- Languages: single language, {{LANGUAGE}}

## Before writing any code — read these, in order

1. `docs/FRONTEND.md` — the frontend contract (mental model, render path, rules). Follow it exactly.
2. `agents/skills/widgets/SKILL.md` — only if you add or change a widget TYPE.
3. `agents/skills/launch-readiness/SKILL.md` — before adding any public image or third-party script, and before calling the site done.
4. `resources/js/types/widgets.d.ts` — the exact data shape of every widget.

## Hard rules (violating any of these breaks the backend contract)

- pnpm only, never npm. No new heavy dependencies without strong reason.
- Vue 3 `<script setup lang="ts">` + Tailwind v4 utilities. No Ziggy / route()
  helpers — links use literal root-relative paths ("/about", "/blog").
- Every public image renders through the `AppImage` component (srcset, width/
  height, lazy by default, `eager` only for the above-the-fold hero image).
- The Hero widget owns the page's single H1; every other widget starts at H2.
- Style with the theme tokens (`--color-brand-*` steps, `--font-sans`, radius
  token) — never hard-code the brand hex in components. The admin Theme
  settings must keep working: changing the color in the admin restyles the site.
- Do NOT touch: controllers, `WidgetDataResolver`, `config/widgets.php` field
  contracts of existing types, the `PUBLIC_SETTINGS` whitelist, permissions,
  migrations, or anything under `app/` — unless step 6 (new widget type)
  explicitly requires a registry entry + resolver case.
- SEO is handled globally (`seo` shared prop → PublicLayout `<Head>`): never
  emit your own `<title>`/meta/canonical in page or widget components.
- Header/footer chrome comes from shared Inertia props: `layout` (header/footer
  JSON), `menus` (nested tree), `settings` (whitelisted keys), `siteLogo`.
  Consume them — don't invent parallel config.

## The work, in order

1. **Theme**: set `theme_primary_color` = {{BRAND_HEX}}, `theme_font` =
   {{FONT_NAME}}, `theme_radius` = {{RADIUS}} (Settings → Theme, or seed/update
   the `site_settings` rows). Verify both admin and public pick it up.
2. **Modules**: enable exactly {{MODULE_LIST}} from /admin/modules (or via the
   modules table); disable the rest so the sidebar and sitemap stay clean.
3. **PublicLayout** (`resources/js/Layouts/PublicLayout.vue`): restyle the
   header (logo via `siteLogo` + `AppImage`, nav from `menus.header`, CTA from
   `layout.header`), the footer (columns from `layout.footer`, socials from
   `settings`), and the mobile nav to match the design direction. Keep the
   existing `<Head>`/SEO and consent logic intact.
4. **Widget components** (`resources/js/Components/Widgets/*.vue`): restyle
   every shipped widget the site will use (hero, rich_text, feature_grid,
   stats, cta, image, gallery, contact_form, plus the collection widgets for
   the enabled modules: testimonials, faqs, team, latest_posts). Keep each
   component's `data` prop typed from `@/types/widgets` and keep rendering
   every registry field — editors rely on all of them working. Empty/missing
   data must render nothing, never a broken block.
5. **Blog & collection pages** (only for enabled modules): restyle
   `Pages/Public/Blog/{Index,Show}.vue`, category archive, and
   `Careers`/`CaseStudies` pages to match. Body HTML stays `v-html` output of
   the block editor — style it via typography classes.
6. **Custom sections** ({{CUSTOM_SECTIONS_OR_NONE}}): only if a design section
   truly can't be expressed with existing widgets, add a NEW widget type
   following `agents/skills/widgets`: registry entry in `config/widgets.php`
   (serializable, every field has a placeholder) → `php artisan widgets:types`
   → `Components/Widgets/YourType.vue` → map in `DynamicPage.vue` →
   `WidgetDataResolver` case only if it needs DB rows. Prefer composing
   existing widgets over inventing new ones.
7. **Pages**: create/update `data/pages/{slug}.json` for {{PAGE_LIST}} — real
   widget stacks per the design (home = hero + proof + services + CTA + …),
   with copy from {{CONTENT_SOURCE}}. Fill each page's `seo` block (meta title
   ≤60 chars, description ≤160). Set up header/footer menus and
   `data/header.json` / `data/footer.json` via the admin (Menus, Header &
   Footer screens) or directly in the JSON.
8. **Responsive + polish pass**: mobile-first check of every page; loading
   `eager` only on the LCP hero image; no horizontal scroll; visible focus
   states; color contrast meets WCAG AA against the brand palette.

## Definition of done — run all of it, fix until green

1. `pnpm build` — vue-tsc must pass with zero errors.
2. `php artisan optimize` — must stay clean.
3. `php artisan template:doctor` — all green.
4. Browser pass with `composer dev`: every page in {{PAGE_LIST}} renders
   correctly at mobile/tablet/desktop widths; the contact form submits and the
   enquiry appears in /admin (test one bad phone number, one valid); changing
   the theme color in /admin restyles the public site; every enabled collection
   widget shows real rows; drafts and unknown slugs 404.
5. Lighthouse on the home page per `launch-readiness` — Performance/SEO/Best
   Practices/Accessibility all green (≥90), noting the documented third-party
   exceptions.
6. Report: list every file you changed, every widget type added (if any), and
   anything from the brief you could not satisfy and why. Do not mark the site
   launch-ready — leave `SEO_INDEXABLE=false`; flipping it is a human decision.
```

---

## Placeholder cheat-sheet

| Placeholder | What to put there |
|---|---|
| `{{CLIENT_NAME}}` | Business/site name as it should appear on the site |
| `{{INDUSTRY_AND_SERVICES}}` | 1–3 sentences: what they do, what they sell/offer |
| `{{TARGET_AUDIENCE}}` | Who visits the site and what they should do (call, book, buy) |
| `{{DESIGN_DIRECTION}}` | Adjectives + vibe; screenshots/Figma links if you have them |
| `{{REFERENCE_URLS}}` | Sites the client likes (optional — delete the line if none) |
| `{{BRAND_HEX}}` | e.g. `#0E7C66` |
| `{{FONT_NAME}}` | e.g. `Inter`, `Sora`, `Manrope` (bunny.net fonts) |
| `{{RADIUS}}` | `sm`, `md`, or `lg` |
| `{{ASSET_LOCATION_OR_UPLOAD_NOTE}}` | Where the logo/photos are, or "upload via /admin/media first" |
| `{{PAGE_LIST}}` | Slugged list: `home, about, services, contact` |
| `{{MODULE_LIST}}` | Subset of: blog, testimonials, faqs, events, teams, case_studies, careers, subscribers |
| `{{CUSTOM_SECTIONS_OR_NONE}}` | Sections the shipped widgets can't express, or `none` |
| `{{CONTENT_SOURCE}}` | Doc/link with real copy, or "write industry placeholder copy" |
| `{{LANGUAGE}}` | e.g. `English` |
