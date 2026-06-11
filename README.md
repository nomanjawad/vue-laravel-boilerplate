# WebTemplate

A Laravel + Vue 3 + Inertia.js boilerplate for building small-to-medium websites. Designed to deploy on shared hosting (cPanel, SiteGround, Hostinger).

## Tech Stack

- **Backend:** Laravel 13 (PHP 8.1+)
- **Frontend:** Vue 3 (Composition API) + Tailwind CSS
- **Bridge:** Inertia.js
- **Database:** MySQL (local via MAMP/XAMPP, production via cPanel) — no SQLite
- **Bundler:** Vite

## Quick Start

```bash
# Clone and install
git clone <repo-url> my-project
cd my-project
composer install
pnpm install

# Configure
cp .env.example .env          # set MySQL credentials (MAMP: port 8889, root/root)
php artisan key:generate

# Interactive setup: site name, feature flags, admin user, migrate + seed
php artisan template:init

# Run
php artisan serve
pnpm run dev
```

Visit `http://localhost:8000`. Admin panel at `/admin` with the credentials you chose.

### Post-clone checklist

1. `cp .env.example .env` and set MySQL credentials (**MySQL only — no SQLite**)
2. `php artisan key:generate`
3. `php artisan template:init` — sets site name, writes `FEATURE_*` flags (so flags always match shipped routes), creates the admin user, runs migrate + seed (+ optional demo content)
4. `composer ide` — generates IDE helper stubs (kills Intelephense false positives)
5. `php artisan optimize` — must pass cleanly before any deploy (CI also checks this)
6. Edit `data/*.json` for static page content

## Features

| Feature | Default | Config Flag |
|---------|---------|-------------|
| Blog (Posts, Categories, Tags) | On | `FEATURE_BLOG` |
| Shop (Products, Cart, Checkout) | On | `FEATURE_SHOP` |
| Team Members | On | `FEATURE_TEAMS` |
| Contact Form | On | `FEATURE_CONTACT_FORM` |
| Careers | Off | `FEATURE_CAREERS` |
| Case Studies | Off | `FEATURE_CASE_STUDIES` |

Built-in on every project: redirect manager + 404 monitoring, newsletter capture,
cookie-consent-gated analytics, XML sitemap, JSON-LD structured data, full-page
response cache, image optimization (WebP + sizes on upload), activity log,
nightly DB backups, branded error pages, WordPress importer
(`php artisan import:wordpress export.xml`).

Toggle features in `.env`:
```
FEATURE_BLOG=true
FEATURE_SHOP=false
FEATURE_CAREERS=true
```

## Architecture

### Content Strategy

- **Static content** — Page sections (hero, features, stats) live in `data/*.json` files. Edit per project.
- **Dynamic content** — Blogs, products, careers, teams, menus, settings managed via admin panel.

### Key Directories

```
app/
├── Http/Controllers/
│   ├── Admin/          # Admin panel controllers
│   ├── Auth/           # Authentication controllers
│   └── Public/         # Public-facing controllers
├── Models/             # Eloquent models
└── Services/           # Business logic (Cart, Order, Payment, SEO, JSON data)

resources/js/
├── Layouts/            # Full layout shells ONLY (PublicLayout, AdminLayout, AuthLayout)
├── Pages/
│   ├── Admin/          # Admin panel pages
│   ├── Auth/           # Login, Register, etc.
│   └── Public/         # One folder per page: Home/Index.vue + Home/components/
│                       #   Pages compose section components; markup lives in sections.
│                       #   (See Pages/Public/Home for the reference example.)
├── Components/
│   ├── Atoms/          # Smallest reusable pieces (AppButton, Badge, SectionHeading)
│   └── Shared/         # Cross-page components (CookieConsent, NewsletterSignup, BrandLogo)
└── Composables/        # useImageUrl, useConsentScripts, ...

resources/css/
├── app.css             # Design tokens (@theme): brand colors, fonts, spacing, radii
├── admin.css           # Admin dark-theme design system (glass / btn-grad / field)
└── pages/*.css         # One file per public page — may be empty, must exist

data/                   # JSON content files for static pages
routes/                 # Feature-split route files
```

### Route Files

Routes are split by feature and loaded conditionally:

- `routes/auth.php` — Login, register, password reset
- `routes/admin.php` — Core admin (dashboard, users, settings, menus, media)
- `routes/admin-blog.php` — Blog admin (if enabled)
- `routes/admin-shop.php` — Shop admin (if enabled)
- `routes/admin-optional.php` — Careers, case studies, teams (if enabled)
- `routes/public.php` — Home, about, contact, profile
- `routes/public-blog.php` — Blog pages (if enabled)
- `routes/public-shop.php` — Shop, cart, checkout (if enabled)
- `routes/public-optional.php` — Careers, case studies (if enabled)

### Payment System

The shop uses a `DummyPaymentService` that simulates payments (90% success rate) for testing. To integrate a real gateway:

1. Create a new service implementing the same `charge()` interface
2. Bind it in `AppServiceProvider`

## Customizing Per Project

1. **Edit JSON files** in `data/` for static page content
2. **Toggle features** via `.env` flags
3. **Update branding** — logo, colors in Tailwind, site name in settings
4. **Add pages** — Create new Vue pages in `resources/js/Pages/Public/`

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for step-by-step shared hosting deployment guide.

Quick deploy with SSH:
```bash
bash deploy.sh production
```

After uploading code changes on subsequent deploys:
```bash
composer deploy   # storage:link + migrate + optimize + responsecache:clear
```

## Admin Panel

Access at `/admin` after login. Manage:
- Users & Roles
- Blog Posts, Categories, Tags
- Products & Orders
- Careers, Case Studies, Team Members
- Media Library
- Navigation Menus
- Page SEO Meta
- Site Settings (contact info, social links)
