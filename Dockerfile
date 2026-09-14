# syntax=docker/dockerfile:1

FROM node:24-alpine AS assets
WORKDIR /app
ARG VITE_APP_NAME="Tidal PTC"
ENV VITE_APP_NAME=$VITE_APP_NAME
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY lang lang
COPY vite.config.js jsconfig.json ./
RUN npm run build

FROM dunglas/frankenphp:1-php8.5 AS app
WORKDIR /app

RUN install-php-extensions pdo_pgsql intl redis pcntl opcache zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-app.ini"

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build public/build
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover \
    && cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/ \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

USER www-data
EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
