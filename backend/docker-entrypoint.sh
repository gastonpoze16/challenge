#!/bin/sh
set -e
cd /var/www/html

# Datadog PHP tracer lee variables de entorno del proceso; exportar DD_* desde .env.
if [ -f .env ]; then
  while IFS= read -r _line || [ -n "$_line" ]; do
    _line=$(printf '%s' "$_line" | tr -d '\r')
    case "$_line" in
      ''|'#'*) continue ;;
      DD_*=*)
        _k="${_line%%=*}"
        _v="${_line#*=}"
        _v=$(printf '%s' "$_v" | sed "s/^[\"']//;s/[\"']$//")
        export "$_k=$_v"
        ;;
    esac
  done < .env
fi

if [ ! -f vendor/autoload.php ]; then
  echo "[docker] Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Compose suele exportar DB_HOST=mysql; en EC2 el RDS va en .env. Si el entorno es vacío o "mysql",
# tomar DB_HOST (y DB_PORT si falta) del archivo .env.
_from_file_host=
_from_file_port=
if [ -f .env ]; then
  _from_file_host=$(grep -E '^DB_HOST=' .env 2>/dev/null | tail -1 | cut -d= -f2- || true)
  _from_file_host=$(printf '%s' "$_from_file_host" | sed "s/^[\"']//;s/[\"']$//" | tr -d '\r')
  _from_file_port=$(grep -E '^DB_PORT=' .env 2>/dev/null | tail -1 | cut -d= -f2- || true)
  _from_file_port=$(printf '%s' "$_from_file_port" | sed "s/^[\"']//;s/[\"']$//" | tr -d '\r')
fi

case "${DB_HOST:-}" in
  ''|mysql)
    if [ -n "$_from_file_host" ]; then
      DB_HOST="$_from_file_host"
    fi
    ;;
esac

if [ -z "${DB_PORT:-}" ] && [ -n "$_from_file_port" ]; then
  DB_PORT="$_from_file_port"
fi

unset _from_file_host _from_file_port

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
