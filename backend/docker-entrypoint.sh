#!/bin/sh
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
  echo "[docker] Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"

echo "[docker] Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
  sleep 1
done

# Opcional: asegurar APP_KEY si el .env está vacío (primer arranque)
if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "[docker] Generating APP_KEY..."
  php artisan key:generate --force --no-interaction || true
fi

exec "$@"
