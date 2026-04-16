#!/bin/sh
set -e
cd /app

if [ ! -f vendor/sproutkit-vue/package.json ]; then
  echo "[docker] Falta frontend/vendor/sproutkit-vue (copiá el paquete @tithely/sproutkit-vue ahí)."
  exit 1
fi

echo "[docker] npm install (frontend)..."
npm install

exec "$@"
