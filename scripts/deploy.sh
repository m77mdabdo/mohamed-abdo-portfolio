#!/usr/bin/env bash
# Installs or updates the site on the server, from the project folder:
#
#   bash scripts/deploy.sh            # after git pull (or after uploading a new copy)
#
# Safe to repeat. Takes a database backup before migrating, keeps the site in maintenance mode only while
# the database changes, and stops if the production check finds a problem.
# Needs: PHP 8.3+ with gd, intl, pdo_mysql; Composer; .env filled from .env.production.example.
# Node is optional: when it is missing, upload public/build from your computer (npm run build).
set -euo pipefail
cd "$(dirname "$0")/.."

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
[ -f .env ] || { echo "Missing .env — copy .env.production.example to .env and fill it first."; exit 1; }
grep -q '^APP_KEY=base64:' .env || "$PHP" artisan key:generate --force

echo "› PHP packages"
"$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
  echo "› Front-end build"
  npm ci --no-audit --no-fund && npm run build
elif [ ! -f public/build/manifest.json ]; then
  echo "public/build is missing and npm is not installed: run npm run build locally and upload public/build."; exit 1
fi

echo "› Database"
if "$PHP" artisan migrate:status --database=migrate >/dev/null 2>&1; then
  "$PHP" artisan portfolio:backup --no-media || echo "(backup skipped)"
fi
"$PHP" artisan down --retry=30 || true
trap '"$PHP" artisan up' EXIT
"$PHP" artisan migrate --database=migrate --force
# First install only: import the original content (the importer never overwrites the owner's edits).
if [ "$("$PHP" artisan tinker --execute='echo \App\Models\Page::count();' 2>/dev/null | tail -1)" = "0" ]; then
  "$PHP" artisan db:seed --force
fi

echo "› Files and caches"
[ -e public/storage ] || "$PHP" artisan storage:link
"$PHP" artisan optimize:clear >/dev/null
"$PHP" artisan optimize
"$PHP" artisan filament:optimize
"$PHP" artisan queue:restart
"$PHP" artisan up
trap - EXIT

echo "› Production check"
"$PHP" artisan portfolio:preflight
