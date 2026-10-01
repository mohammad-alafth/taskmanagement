#!/bin/sh
set -e

PORT=${PORT:-80}

# Apache must listen on the platform-provided $PORT (Railway/Render)
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Writable dirs (volumes fresh on first boot)
mkdir -p storage/app/private storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Laravel boot (fail-safe so container still starts if DB not ready yet)
php artisan storage:link || true
php artisan migrate --force || echo "WARNING: migrate failed, check DB env vars"
php artisan db:seed --force || echo "WARNING: seed failed"

# Production caches (ignore failure on first boot)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec apache2-foreground
