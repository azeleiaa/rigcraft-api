# RigCraft API — production image (PHP 8.3 + Apache + MongoDB extension)
FROM php:8.3-apache

# System deps + PHP extensions needed by Laravel + mongodb driver
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
  && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && a2enmod rewrite \
  && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# App source
COPY . .

# Apache document root -> Laravel public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
  && chown -R www-data:www-data storage bootstrap/cache \
  && chmod -R 775 storage bootstrap/cache

EXPOSE 80

# Cache config/routes/views, ensure storage link, then serve.
# NOTE: set APP_KEY, MONGODB_URI, JWT_SECRET via platform env vars.
# Seeding/indexes are NOT run automatically — run once via:
#   php artisan db:seed --force && php artisan mongo:indexes
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache; apache2-foreground"]
