#!/usr/bin/env bash
set -euo pipefail

echo "== Ehkam production checks =="
php artisan about --only=environment,cache,database 2>/dev/null || true

if [ "${APP_ENV:-}" != "production" ]; then echo "ERROR: APP_ENV is not production"; exit 1; fi
if [ "${APP_DEBUG:-true}" != "false" ]; then echo "ERROR: APP_DEBUG must be false"; exit 1; fi
if [ -z "${APP_KEY:-}" ]; then echo "ERROR: APP_KEY is empty"; exit 1; fi

php artisan config:clear
php artisan route:list --path=api
php artisan test
php artisan optimize

echo "== Checks completed =="
