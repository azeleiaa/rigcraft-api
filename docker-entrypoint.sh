#!/bin/sh
# Render (dan kebanyakan PaaS) menyuntik $PORT (default 10000) dan
# health check menembak port itu. Apache default listen 80 -> harus
# diarahkan ke $PORT setiap container start, kalau tidak deploy timeout.
set -e

PORT=${PORT:-80}

# Kunci satu MPM saat runtime (bukan cuma saat build): beberapa base image /
# installer mengaktifkan mpm_event di samping prefork sehingga Apache mati
# dengan "More than one MPM loaded" dan container crash-loop.
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
