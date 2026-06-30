# SiteGround-specific deploy notes

SiteGround quirks layered on top of [DEPLOY-cpanel.md](DEPLOY-cpanel.md) — everything in that doc applies, plus the items below.

## 1. PHP Manager (not the cPanel selector)

SiteGround replaced the standard cPanel PHP selector with **Site Tools → Devs → PHP Manager**. Set the version to **PHP 8.3** (or 8.4 if available) for your domain. Extensions are toggled in the same page.

## 2. SSH access

SSH is on by default for paid plans. The credentials live in **Site Tools → Devs → SSH Keys Manager**:

- generate or upload a key,
- the public half goes on the server automatically,
- the private half goes in the GitHub Actions `SSH_PRIVATE_KEY` secret.

SiteGround's SSH port is **18765** (not 22) — set `SSH_PORT=18765`.

## 3. Document root

SiteGround's default domain points at `public_html/`. You have two clean options:

**A. Point the domain at `public/`** (preferred). In Site Tools → Domain → Parked Domains, set the document root to your deploy path's `public` folder. Some plans require a support ticket to change this.

**B. Symlink trick** (no ticket required):

```bash
cd ~/public_html
rm -rf *           # clear the default placeholder
ln -s ~/site.tld/public/* .
ln -s ~/site.tld/public/.htaccess .
```

This makes `public_html/` mirror your `public/` folder. The `.htaccess` rewrite then handles routing into `index.php`.

## 4. Trailing-slash redirect loop (the SkyTech footgun)

SiteGround's default `.htaccess` removes trailing slashes. webTemplate's earlier versions shipped a `TrailingSlashRedirect` middleware that *added* them. The two fought → `ERR_TOO_MANY_REDIRECTS`. v2 removed the middleware. If you ever see redirect loops on SiteGround, check that no middleware adds trailing slashes and let Apache handle it.

## 5. mysqldump path

`DB_DUMP_BINARY_PATH=/usr/bin/mysqldump` on SiteGround. Run `which mysqldump` over SSH to confirm.

## 6. Long-running processes are killed

SiteGround terminates long-running PHP processes after ~60s. Use the scheduler-driven `queue:work --stop-when-empty` pattern from the main deploy doc; **never** run a daemonized `queue:work`.
