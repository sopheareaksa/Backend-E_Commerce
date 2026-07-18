#!/bin/sh
set -e

# Render startup script for Laravel + FrankenPHP

# Ensure Laravel can write to storage and cache directories
chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true
chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true

# Generate APP_KEY if Render did not provide one (Laravel requires base64: prefix)
if [ -z "$APP_KEY" ]; then
    echo "Generating Laravel APP_KEY..."
    export APP_KEY=$(php artisan key:generate --show --no-ansi)
fi

# Wait briefly for the database to be reachable (Render DB may need a moment)
php artisan db:show --no-ansi 2>/dev/null || echo "Warning: database not reachable yet; migrations may retry."

# Run migrations
php artisan migrate --force --no-ansi

# Cache config/routes/views for production performance
php artisan config:cache --no-ansi || true
php artisan route:cache --no-ansi || true
php artisan view:cache --no-ansi || true

# Start FrankenPHP on Render's dynamic port
echo "Starting FrankenPHP on port ${PORT:-80}"
exec frankenphp php-server -r public/ --port "${PORT:-80}"
