### Dockerfile for Laravel on Railway with explicit ENTRYPOINT and CMD
# Use official PHP image with FPM
FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    git \
    unzip \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql zip

# Set working directory
WORKDIR /app

# Copy composer files and install Composer
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Install PHP dependencies (allow scripts to run for package discovery)
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Copy application source
COPY . ./

# Ensure .env exists and generate APP_KEY
RUN cp .env.example .env \
    && php artisan key:generate --ansi

# Cache config and routes for faster boot
RUN php artisan config:cache && php artisan route:cache

# Expose dynamic port from Railway
EXPOSE ${PORT}

# Set entrypoint and default command explicitly
ENTRYPOINT ["php", "artisan"]
CMD ["serve", "--host=0.0.0.0", "--port=${PORT}"]
