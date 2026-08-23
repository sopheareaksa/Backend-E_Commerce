#!/bin/bash
set -e

# Configure Apache to listen on Render's dynamic $PORT
PORT="${PORT:-80}"
sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Cache configs if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Automatically run migrations & seed products on startup if cloud DB is provided
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ] && [ "$DB_HOST" != "localhost" ]; then
    echo "Running migrations and seeding default products..."
    php artisan migrate --seed --force || true
fi

exec "$@"
