# Render uses this Dockerfile to build the backend service.
# Single-stage php-fpm + nginx via supervisord.

FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
        nginx supervisor bash git curl unzip \
        libpng libpng-dev libzip-dev oniguruma-dev postgresql-dev icu-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip bcmath intl gd \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (layer cache)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist || true

# App
COPY . .
RUN composer dump-autoload --optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
