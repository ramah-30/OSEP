# OSEP Laravel API — production image for Render (or any Docker host).
# Build context is the backend/ folder (see render.yaml).
FROM php:8.2-apache

# --- System packages + the PHP extensions Laravel needs ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip \
        libzip-dev libpng-dev libonig-dev libicu-dev \
        ca-certificates \
    && docker-php-ext-install pdo_mysql mbstring bcmath zip gd exif pcntl intl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# --- Composer (copied from the official image) ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Point Apache at Laravel's public/ folder ---
RUN sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

# --- Application code + PHP dependencies ---
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts \
    && php artisan package:discover --ansi || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Startup script (binds Apache to Render's $PORT, then boots Laravel) ---
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["/usr/local/bin/entrypoint"]
