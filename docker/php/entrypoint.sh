#!/bin/sh
# Prepares Laravel before starting php-fpm (or any command passed in).
set -e
cd /var/www/html

# Volumes may start empty: make sure the expected tree exists and is writable.
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public storage/app/translations bootstrap/cache public/uploads
# BookingCore redirects to its installer unless this flag file exists.
[ -f storage/installed ] || touch storage/installed
chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || true

# Wait for MySQL.
if [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for database ${DB_HOST}:${DB_PORT:-3306}..."
  i=0
  until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); } catch (Exception $e) { exit(1); }' 2>/dev/null; do
    i=$((i+1))
    if [ "$i" -ge 60 ]; then echo "Database not reachable after 120s" >&2; exit 1; fi
    sleep 2
  done
fi

# Run artisan as www-data so files it creates (logs, caches) stay writable by
# the php-fpm workers; a root-owned laravel.log turns every request into a 500.
artisan() {
  if [ "$(id -u)" = "0" ]; then
    su -s /bin/sh www-data -c "php artisan $*"
  else
    php artisan "$@"
  fi
}

# Only the main app container (php-fpm) does one-time setup; the scheduler
# reuses the image with a different command.
if [ "$1" = "php-fpm" ]; then
  [ -L public/storage ] || artisan storage:link --force >/dev/null 2>&1 || true
  artisan package:discover --ansi >/dev/null

  if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    artisan migrate --force
  fi

  artisan config:cache
  artisan route:cache || artisan route:clear
  artisan view:cache || true
fi

# Anything created above (or by a previous root `docker compose exec`) must be
# writable by php-fpm.
if [ "$(id -u)" = "0" ]; then
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
