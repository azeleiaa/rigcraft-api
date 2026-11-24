# RigCraft API — production image (PHP 8.4 + Apache + MongoDB extension)
# NOTE: PHP 8.4 karena composer.lock mengunci symfony v8.1 (butuh php >=8.4.1).
# Jangan turunkan ke 8.3 tanpa regenerate lock dengan platform php 8.3.
FROM php:8.4-apache

# System deps + PHP extensions.
# mongodb dipasang via binary prebuilt (mlocati installer, hitungan detik),
# BUKAN via `pecl install` yang compile dari source (~2-4 menit) dan
# membuat build selalu melewati batas timeout deploy Render.
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
  && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath \
  && install-php-extensions mongodb \
  && a2enmod rewrite \
  && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

# Install PHP deps first (better layer caching).
# --no-scripts karena file `artisan` belum ada di layer ini;
# script post-autoload-dump dijalankan belakangan via dump-autoload.
COPY composer.json composer.lock ./
# Retry hingga 3x: download dist dari GitHub kadang 504 sesaat (transient)
RUN success=0; \
    for i in 1 2 3; do \
      if composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts; then success=1; break; fi; \
      echo "composer install attempt $i failed, retrying..."; sleep 15; \
    done; \
    test "$success" = "1"

# App source
COPY . .

# Baru di sini artisan tersedia -> generate optimized autoload + package discovery
RUN composer dump-autoload --optimize --no-dev --no-interaction

# Apache document root -> Laravel public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
  && chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Cache config/routes/views, ensure storage link, then serve.
# NOTE: set APP_KEY, MONGODB_URI, JWT_SECRET via platform env vars.
# Seeding/indexes are NOT run automatically — run once via:
#   php artisan db:seed --force && php artisan mongo:indexes
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["docker-entrypoint.sh"]
