#!/usr/bin/env bash
# Despliegue frontend en EC2 (Nuxt build + PM2).
# Variables: CHALLENGE_ROOT (default ~/challenge), DEPLOY_BRANCH (default main).
set -euo pipefail

ROOT="${CHALLENGE_ROOT:-$HOME/challenge}"
BRANCH="${DEPLOY_BRANCH:-main}"

cd "$ROOT"
git fetch origin
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

cd "$ROOT/frontend"
# npm ci falla si package-lock.json no está alineado con package.json; el CI usa npm install.
npm install --no-audit --no-fund
npm run build
# Primera vez: no existe el proceso; las siguientes: reload sin downtime.
if pm2 show nuxt-front >/dev/null 2>&1; then
  npm run pm2:reload
else
  npm run pm2:start
fi
