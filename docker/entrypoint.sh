#!/bin/sh
set -e

php artisan storage:link --force || true

if [ "$FORCE_MIGRATE" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
