#!/bin/sh
set -e

cd /var/www/html

if [ -f composer.json ]; then
    if [ ! -d vendor ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi
fi

if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction || true
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

if [ -f artisan ] && [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction || true
fi

exec docker-php-entrypoint "$@"
