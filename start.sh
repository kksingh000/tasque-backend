#!/usr/bin/env bash
set -e

echo "→ Generating app key if missing..."
php artisan key:generate --force --no-interaction || true

echo "→ Running migrations..."
php artisan migrate --force --no-interaction

echo "→ Seeding (idempotent)..."
php artisan db:seed --force --no-interaction || true

echo "→ Starting server on port ${PORT:-8000}..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
