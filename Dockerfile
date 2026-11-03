# Integrated Debrief System - production image
# One image runs everything PHP needs: nginx + php-fpm (web) or `php artisan queue:work` (queue).
# Node.js is included because Laravel runs fdr-parser through it.
#
#   docker compose up -d --build        (see docker-compose.yml and docs/DEPLOYMENT.md)

# ---- 1. Frontend assets (Vite + Tailwind) -------------------------------------
FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---- 2. PHP dependencies ---------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# ---- 3. fdr-parser dependencies (DJI flight record decoder) ----------------------
FROM node:22-bookworm-slim AS parser
WORKDIR /app
COPY fdr-parser/package.json fdr-parser/package-lock.json ./
RUN npm ci --omit=dev --no-audit --no-fund

# ---- 4. Runtime --------------------------------------------------------------------
FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor libpq-dev libzip-dev libicu-dev unzip \
    && docker-php-ext-install pdo_pgsql intl zip opcache bcmath pcntl \
    && rm -rf /var/lib/apt/lists/*

# Node.js runtime for fdr-parser (its packages come from the "parser" stage)
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-ids.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/ids.conf
COPY docker/entrypoint.sh /usr/local/bin/ids-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY --from=parser --chown=www-data:www-data /app/node_modules ./fdr-parser/node_modules

# package:discover lists only the production packages installed above (no dev tools).
RUN chmod +x /usr/local/bin/ids-entrypoint \
    && rm -f public/hot \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["ids-entrypoint"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
