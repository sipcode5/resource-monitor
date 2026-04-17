#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# start.sh – Start php-fpm immediately (so nginx can connect), then migrate
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail
# ── 0. Ensure storage directory tree exists (volume mount wipes it) ──────────
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
# ── 1. Start php-fpm in the background right away ───────────────────────────
# This ensures port 9000 is open before we wait for MySQL, preventing nginx 502.
php-fpm --daemonize
echo "✅ php-fpm started (daemonized)"

# ── 2. Wait for MySQL ────────────────────────────────────────────────────────
# Use nc (netcat) — Alpine's mysql-client is MariaDB-based and cannot
# authenticate against MySQL 8.x caching_sha2_password.
echo "⏳ Waiting for MySQL..."
until nc -z "${DB_HOST:-mysql}" 3306 2>/dev/null; do
    echo "  MySQL not ready – retrying in 2s..."
    sleep 2
done
echo "✅ MySQL is reachable"

# ── 3. Wait for Redis ────────────────────────────────────────────────────────
echo "⏳ Waiting for Redis..."
until php -r "
\$r = @fsockopen('${REDIS_HOST:-redis}', ${REDIS_PORT:-6379}, \$e, \$s, 2);
exit(\$r ? 0 : 1);
" 2>/dev/null; do
    echo "  Redis not ready – retrying in 2s..."
    sleep 2
done
echo "✅ Redis is reachable"

# ── 4. Bootstrap Laravel ─────────────────────────────────────────────────────
if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "base64:GENERATE_ME" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force --no-interaction

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "🚀 Bootstrap complete – php-fpm is running (PID $(cat /var/run/php-fpm.pid 2>/dev/null || echo '?'))"

# ── 5. Keep container alive (php-fpm is daemonized, not PID 1) ───────────────
exec tail -f /dev/null
