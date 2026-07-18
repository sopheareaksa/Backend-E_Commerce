#!/bin/sh
# Render startup script for Laravel + FrankenPHP

# Clear and cache config for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (skip if DB is not ready; Render will retry on failure)
php artisan migrate --force --ansi

# Run seeders only on first deploy if needed
# php artisan db:seed --force --ansi

# Start FrankenPHP on Render's dynamic port
echo "Starting FrankenPHP on port ${PORT:-80}"
frankenphp php-server -r public/ --port "${PORT:-80}"
