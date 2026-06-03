#!/bin/bash
# ==============================================================================
# deploy.sh - Deploy Laravel app to shared hosting (cPanel/SiteGround/Hostinger)
# ==============================================================================
# Usage: bash deploy.sh [environment]
#   environment: "production" (default) or "staging"
#
# Required environment variables (or set defaults below):
#   PUBLIC_HTML_PATH  - Path to your web root (default: ~/public_html)
#   APP_PATH          - Path to your Laravel app (default: ~/webTemplate)
# ==============================================================================

set -e

ENV=${1:-production}
PUBLIC_HTML_PATH=${PUBLIC_HTML_PATH:-"$HOME/public_html"}
APP_PATH=${APP_PATH:-"$HOME/webTemplate"}

echo ""
echo "========================================"
echo "  Deploying to: $ENV"
echo "  App path:     $APP_PATH"
echo "  Public HTML:  $PUBLIC_HTML_PATH"
echo "========================================"
echo ""

# 1. Install PHP dependencies
echo "[1/9] Installing PHP dependencies..."
if [ "$ENV" = "production" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
else
    composer install --optimize-autoloader --no-interaction --quiet
fi
echo "  Done."

# 2. Install Node dependencies and build assets
echo "[2/9] Installing Node dependencies..."
if command -v pnpm &> /dev/null; then
    pnpm install --frozen-lockfile --silent 2>/dev/null || pnpm install --silent
    echo "[3/9] Building frontend assets..."
    pnpm run build
elif command -v npm &> /dev/null; then
    npm ci --silent 2>/dev/null || npm install --silent
    echo "[3/9] Building frontend assets..."
    npm run build
else
    echo "  WARNING: Neither pnpm nor npm found. Skipping asset build."
    echo "  Make sure assets are pre-built in public/build/"
fi
echo "  Done."

# 4. Copy public files to public_html
echo "[4/9] Copying public files to $PUBLIC_HTML_PATH..."
rm -rf "$PUBLIC_HTML_PATH/build"
cp -r public/build "$PUBLIC_HTML_PATH/build"
cp public/index.php "$PUBLIC_HTML_PATH/index.php"
cp public/.htaccess "$PUBLIC_HTML_PATH/.htaccess"
cp public/favicon.ico "$PUBLIC_HTML_PATH/favicon.ico" 2>/dev/null || true
cp public/robots.txt "$PUBLIC_HTML_PATH/robots.txt" 2>/dev/null || true
echo "  Done."

# 5. Patch index.php paths
echo "[5/9] Patching index.php paths..."
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS sed
    sed -i '' "s|__DIR__.'/../vendor/autoload.php'|'$APP_PATH/vendor/autoload.php'|g" "$PUBLIC_HTML_PATH/index.php"
    sed -i '' "s|__DIR__.'/../bootstrap/app.php'|'$APP_PATH/bootstrap/app.php'|g" "$PUBLIC_HTML_PATH/index.php"
else
    # Linux sed
    sed -i "s|__DIR__.'/../vendor/autoload.php'|'$APP_PATH/vendor/autoload.php'|g" "$PUBLIC_HTML_PATH/index.php"
    sed -i "s|__DIR__.'/../bootstrap/app.php'|'$APP_PATH/bootstrap/app.php'|g" "$PUBLIC_HTML_PATH/index.php"
fi
echo "  Done."

# 6. Create storage symlink
echo "[6/9] Creating storage symlink..."
ln -sfn "$APP_PATH/storage/app/public" "$PUBLIC_HTML_PATH/storage"
echo "  Done."

# 7. Set permissions
echo "[7/9] Setting file permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
find storage -type d -exec chmod 775 {} \; 2>/dev/null || true
echo "  Done."

# 8. Run migrations
echo "[8/9] Running migrations..."
php artisan migrate --force
echo "  Done."

# 9. Optimize for production
echo "[9/9] Optimizing application..."
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache
echo "  Done."

echo ""
echo "========================================"
echo "  Deployment complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "  1. Ensure .env is configured on the server"
echo "  2. Run 'php artisan db:seed' if this is a fresh install"
echo "  3. Run 'php artisan storage:link' if storage symlink fails"
echo ""
