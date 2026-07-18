FROM dunglas/frankenphp:latest-php8.3

# Install system dependencies and PHP extensions needed for Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_mysql pdo_pgsql pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . /app

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Copy and make the Render startup script executable
COPY render-start.sh /app/render-start.sh
RUN chmod +x /app/render-start.sh

# Expose the dynamic port Render uses
EXPOSE 80

# Use the startup script to cache config, run migrations, and start FrankenPHP
CMD ["/app/render-start.sh"]