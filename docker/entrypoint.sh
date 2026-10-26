#!/bin/sh
# Runs before the web server / queue worker starts.
set -e
cd /var/www/html

# Named volumes start empty: make sure Laravel's folders exist and are writable.
mkdir -p storage/app/private storage/app/tmp storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is empty. Set it in .env (see docs/DEPLOYMENT.md), e.g. APP_KEY=base64:\$(openssl rand -base64 32)" >&2
    exit 1
fi

# Only the web container migrates, so two containers never migrate at the same time.
if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    php artisan migrate --force
fi

# Cache config, routes, views and events for speed.
php artisan optimize

exec "$@"
