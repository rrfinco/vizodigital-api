#!/usr/bin/env bash
# Sync portal environment base URLs (local Docker + optional live shell).
# Usage:
#   ./scripts/sync-local.sh          # local Docker app
#   ./scripts/sync-local.sh --live   # current host .env DB (Dokploy)

set -euo pipefail
cd "$(dirname "$0")/.."

MODE="${1:-local}"

if [[ "$MODE" == "--live" || "$MODE" == "live" ]]; then
  echo "[sync] Using host PHP + current .env database..."
  php scripts/sync-portal-base-urls.php
  php artisan optimize:clear --no-interaction || true
else
  echo "[sync] Using local Docker container api-portal-app..."
  docker exec api-portal-app php scripts/sync-portal-base-urls.php
  docker exec api-portal-app php artisan optimize:clear --no-interaction || true
fi

echo "[sync] Done."
echo "Local docs:  http://localhost:9021/docs"
echo "Live docs:   https://docs.vizodigital.com/docs  (after Dokploy domain bind)"
echo "UAT API:     https://uat-api.vizodigital.com"
echo "Prod API:    https://api.vizodigital.com"
