#!/usr/bin/env bash
# Update a bare-metal (non-Docker) server to the latest code.
# Run from the project folder as the deploy user:  bash scripts/deploy.sh
set -euo pipefail
cd "$(dirname "$0")/.."

php artisan down --retry=30 || true

git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build

php artisan migrate --force
php artisan optimize
php artisan queue:restart   # supervisor restarts the worker with the new code

php artisan up
echo "Deployed $(git rev-parse --short HEAD)"
