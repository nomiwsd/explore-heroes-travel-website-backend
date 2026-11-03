# syntax=docker/dockerfile:1
# Explore Heroes — Laravel backend
#
# Targets:
#   app  PHP-FPM running Laravel (also used for the scheduler)
#   web  nginx: serves Laravel's public files, proxies PHP to `app`, and
#        routes every other path to the Next.js frontend container.

# ---------------------------------------------------------------- vendor ---
FROM composer:2 AS vendor
WORKDIR /app
# composer.json's classmap points into modules/, so the source must be present
# before install. Platform extensions are installed in the runtime image.
COPY . .
RUN composer install --no-dev --no-scripts --optimize-autoloader --prefer-dist \
      --no-interaction --ignore-platform-reqs

# ------------------------------------------------------------------- app ---
FROM php:8.2-fpm-bookworm AS app
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mysqli gd zip intl bcmath exif opcache pcntl soap gmp \
 && apt-get update && apt-get install -y --no-install-recommends unzip \
 && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-explore-heroes.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
# Strip CRLF in case the repo was checked out on Windows.
RUN tr -d '\r' < /usr/local/bin/entrypoint > /tmp/ep && mv /tmp/ep /usr/local/bin/entrypoint && chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
      storage/logs storage/app/public storage/app/translations bootstrap/cache public/uploads \
 && chown -R www-data:www-data storage bootstrap/cache public/uploads public/locales 2>/dev/null || true

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ------------------------------------------------------------------- web ---
FROM nginx:1.27-alpine AS web
COPY --from=vendor /app/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
