#!/usr/bin/env bash
# Despliegue backend en EC2 (Docker: API + queue + Reverb).
# Variables: CHALLENGE_ROOT (default ~/challenge), DEPLOY_BRANCH (default main).
set -euo pipefail

ROOT="${CHALLENGE_ROOT:-$HOME/challenge}"
BRANCH="${DEPLOY_BRANCH:-main}"

cd "$ROOT"
git fetch origin
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

cd "$ROOT/backend"
docker run --rm -v "$PWD":/var/www/html -w /var/www/html composer:2 \
  composer install --no-interaction --prefer-dist

docker build -t challenge-api .

docker rm -f challenge-api challenge-queue challenge-reverb 2>/dev/null || true
docker network create challenge-net 2>/dev/null || true

docker run -d --name challenge-api --restart unless-stopped \
  -v "$PWD":/var/www/html \
  -p 8000:8000 \
  --network challenge-net \
  challenge-api

docker run -d --name challenge-queue --restart unless-stopped \
  -v "$PWD":/var/www/html \
  --network challenge-net \
  challenge-api php artisan queue:work --sleep=1 --tries=3 --timeout=120

docker run -d --name challenge-reverb --restart unless-stopped \
  -v "$PWD":/var/www/html \
  -p 8081:8081 \
  --network challenge-net \
  challenge-api php artisan reverb:start --host=0.0.0.0 --port=8081
