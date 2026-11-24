#!/bin/sh
# Render (dan kebanyakan PaaS) menyuntik $PORT (default 10000) dan
# health check menembak port itu. Apache default listen 80 -> harus
# diarahkan ke $PORT setiap container start, kalau tidak deploy timeout.
set -e

PORT=${PORT:-80}

sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
