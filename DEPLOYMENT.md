# Deployment Guide - Shared Hosting (cPanel/SiteGround/Hostinger)

## Server Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- PHP Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, cURL
- Composer (if available via SSH)
- Node.js 18+ (for building assets locally)

## Deployment Options

### Option A: Deploy via SSH (Recommended)

If your host provides SSH access:

1. **Upload the project** (excluding `node_modules`, `vendor`):
   ```bash
   rsync -avz --exclude='node_modules' --exclude='vendor' ./ user@server:~/webTemplate/
   ```

2. **SSH into the server**:
   ```bash
   ssh user@server
   ```

3. **Configure .env**:
   ```bash
   cd ~/webTemplate
   cp .env.example .env
   nano .env  # Update database, mail, app URL, feature flags
   php artisan key:generate
   ```

4. **Run the deploy script**:
   ```bash
   bash deploy.sh production
   ```

5. **Seed the database** (first time only):
   ```bash
   php artisan db:seed
   ```

### Option B: Deploy via File Manager (No SSH)

1. **Build assets locally**:
   ```bash
   pnpm install && pnpm run build
   composer install --no-dev --optimize-autoloader
   ```

2. **Upload via cPanel File Manager**:
   - Upload the entire project to `~/webTemplate/` (NOT in public_html)
   - From `public/` folder, copy these to `~/public_html/`:
     - `build/` directory
     - `index.php`
     - `.htaccess`
     - `robots.txt`
     - `favicon.ico`

3. **Edit `~/public_html/index.php`**:
   Replace:
   ```php
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';
   ```
   With:
   ```php
   require '/home/yourusername/webTemplate/vendor/autoload.php';
   $app = require_once '/home/yourusername/webTemplate/bootstrap/app.php';
   ```

4. **Create storage symlink**:
   In cPanel Terminal or ask hosting support:
   ```bash
   ln -s ~/webTemplate/storage/app/public ~/public_html/storage
   ```

5. **Configure .env**:
   Edit `~/webTemplate/.env` with your database credentials.

6. **Run migrations**:
   Via cPanel Terminal:
   ```bash
   cd ~/webTemplate && php artisan migrate --force && php artisan db:seed
   ```

## Database Setup (cPanel)

1. Go to **cPanel > MySQL Databases**
2. Create a new database (e.g., `yourusername_webtemplate`)
3. Create a new user with a strong password
4. Add the user to the database with **All Privileges**
5. Update `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=yourusername_webtemplate
   DB_USERNAME=yourusername_dbuser
   DB_PASSWORD=your_secure_password
   ```

## Server Structure

```
~/
├── public_html/              (web root)
│   ├── index.php             (patched to point to ~/webTemplate)
│   ├── .htaccess             (Laravel + Inertia headers)
│   ├── build/                (Vite compiled assets)
│   ├── storage -> symlink    (to ~/webTemplate/storage/app/public)
│   ├── robots.txt
│   └── favicon.ico
│
├── webTemplate/              (Laravel app - NOT web accessible)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── data/                 (JSON content files)
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
```

## Updating After Code Changes

1. Upload changed files to `~/webTemplate/`
2. If frontend changed: rebuild assets locally, upload `public/build/` to `~/public_html/build/`
3. SSH in and run:
   ```bash
   cd ~/webTemplate
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize
   ```

## Troubleshooting

### 500 Internal Server Error
- Check `~/webTemplate/storage/logs/laravel.log`
- Ensure `storage/` and `bootstrap/cache/` are writable (775)
- Verify `.env` database credentials are correct
- Run `php artisan config:clear`

### Blank Page
- Check `index.php` paths are correct (absolute paths to webTemplate)
- Ensure `public_html/.htaccess` exists and mod_rewrite is enabled

### Assets Not Loading
- Verify `public_html/build/` directory exists with compiled assets
- Check the browser console for 404 errors on CSS/JS files

### Storage Links Not Working
- Recreate the symlink: `ln -sfn ~/webTemplate/storage/app/public ~/public_html/storage`
- Some hosts don't allow symlinks; upload files directly to `public_html/storage/`

### Session/Cache Issues
- Ensure `SESSION_DRIVER=file` and `CACHE_STORE=file` in `.env`
- Clear caches: `php artisan cache:clear && php artisan config:clear`
