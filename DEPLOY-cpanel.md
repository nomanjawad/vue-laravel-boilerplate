# cPanel / Shared-hosting deploy guide

This doc is for cPanel / Hostinger / StackCP / SiteGround-style shared hosts.
The GitHub Actions deploy workflow at [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml) handles the build + rsync; this guide covers the one-off **server-side** setup and the gotchas that bite during a fresh deploy.

## 1. SSH access + workflow secrets

The deploy workflow needs these GitHub repo secrets:

| Secret           | What it is                                                   |
| ---------------- | ------------------------------------------------------------ |
| `SSH_HOST`       | Your server hostname (e.g. `vps.host.com`)                   |
| `SSH_PORT`       | Usually `22` on cPanel, `65002` on Hostinger                 |
| `SSH_USERNAME`   | Your cPanel/SSH user                                         |
| `SSH_PRIVATE_KEY`| The **private** half of an SSH key whose public half is in `~/.ssh/authorized_keys` on the server |
| `DEPLOY_PATH`    | Absolute path to the deploy root, e.g. `/home/USER/site.tld` |

The workflow rsyncs the built app to `DEPLOY_PATH`, then SSHes in and runs `template:doctor --production`, `migrate --force`, `optimize:clear`, etc.

## 2. PHP version selector

Both cPanel and Hostinger let you pick a PHP version per domain. **Pick PHP 8.3 or 8.4.** Symptom of mismatch: the deploy succeeds but the first page request 500s with a "platform_check" error inside `vendor/composer/`.

Required extensions (PHP Selector → Extensions):

`gd · pdo_mysql · openssl · mbstring · bcmath · intl · zip · curl`

## 3. Document root → `/public`

In cPanel: **Domains → manage your domain → Document Root** → set to `/home/USER/site.tld/public`. If you can't change the document root (some shared hosts pin it to `public_html`), use the symlink trick:

```bash
ln -s /home/USER/site.tld/public/* /home/USER/public_html/
```

…or move `public/`'s contents to `public_html/` and adjust the paths in `public/index.php` and `public/build/manifest.json`. Generally just changing the document root is cleaner — only fall back to symlinks if forced.

## 4. `.env` location

The deploy workflow **never** ships your `.env` — you put it on the server once, by hand. Copy `.env.example` to `DEPLOY_PATH/.env` and fill in:

```env
APP_URL=https://site.tld
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

SEO_INDEXABLE=true   # remove the noindex header once you're ready

# Optional: paste the path returned by `which mysqldump` for daily backups.
DB_DUMP_BINARY_PATH=/usr/bin/mysqldump

# Optional: token for /debug.php (see §7).
DEBUG_TOKEN=
```

## 5. Cron line — runs scheduler + queue

Add this single cron entry in cPanel → Cron Jobs (or `crontab -e`):

```
* * * * * cd /home/USER/site.tld && php artisan schedule:run >> /dev/null 2>&1
```

Laravel's scheduler tick drives **everything**: nightly DB backups, queued mail (`queue:work --stop-when-empty` runs from the scheduler — no long-running worker required), responsecache pruning, etc.

## 6. The footgun: `config:cache` silently winning

If you edit `.env` on the server and nothing changes, you cached the config before editing. **Always** run `php artisan optimize:clear` after editing `.env`. The deploy workflow already does this. Locally, `php artisan template:doctor` reminds you.

## 7. `public/debug.php` — the recovery hatch

When the app fatals before Laravel boots (missing extension, bad .env, broken vendor), normal artisan commands are useless. `public/debug.php` is a token-gated, pure-PHP page that:

- prints the PHP/extension/DB/storage/Vite state in green/red,
- tails `storage/logs/laravel.log`,
- exposes one-click buttons for `optimize:clear`, `storage:link`, `migrate`, `responsecache:clear`.

To enable on a server, set `DEBUG_TOKEN=<random-32-char-string>` in `.env`. Then visit `https://site.tld/debug.php?t=<token>`. Without a matching token the file returns blank 404 — leaving it deployed is safe.

Once your site is stable, remove `DEBUG_TOKEN` from `.env` to disable the page.

## 8. After a deploy: what to verify

```bash
php artisan template:doctor --production
```

Green across the board → you're done. Any red row prints its exact fix.
