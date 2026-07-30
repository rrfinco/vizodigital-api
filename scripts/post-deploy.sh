#!/usr/bin/env bash
# Laravel production sync for Dokploy / Nixpacks deploys.
# Keeps storage + caches healthy on every container start.
# Intentionally does NOT run migrations.

set -euo pipefail

cd /app

echo "[post-deploy] Preparing storage & bootstrap cache directories..."
mkdir -p \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chmod -R ug+rwx storage bootstrap/cache || true

echo "[post-deploy] Linking public storage..."
php artisan storage:link --force --no-interaction 2>/dev/null || true

echo "[post-deploy] Clearing stale caches..."
php artisan optimize:clear --no-interaction

echo "[post-deploy] Rebuilding production caches..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan event:cache --no-interaction

if php artisan list --raw 2>/dev/null | grep -q '^filament:optimize'; then
  echo "[post-deploy] Optimizing Filament..."
  php artisan filament:optimize --no-interaction || true
fi

echo "[post-deploy] Done."
