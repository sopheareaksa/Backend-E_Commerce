FROM dunglas/frankenphp:latest-php8.3

# Install system dependencies and PHP extensions needed for Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files (.dockerignore excludes .env, node_modules, vendor, etc.)
COPY . /app

# Remove any accidentally copied .env or local env files
RUN rm -f /app/.env /app/.env.backup /app/.env.production

# Install Laravel dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Ensure storage/cache directories exist and are writable
RUN mkdir -p /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Copy and make the Render startup script executable
COPY render-start.sh /app/render-start.sh
RUN chmod +x /app/render-start.sh

# Expose the dynamic port Render uses
EXPOSE 80

# Use the startup script to set up the app and start FrankenPHP
CMD ["/app/render-start.sh"]
