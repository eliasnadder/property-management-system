### Dockerfile for Laravel on Railway with correct key generation and required PHP extensions
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

# Install PHP dependencies
# Ensure PHP extensions are installed before running composer
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --no-scripts

# Copy application source
COPY . ./

# Copy sample env and generate APP_KEY
RUN cp .env.example .env \
    && php artisan key:generate --ansi

# Cache config and routes (optional but speeds up boot)
RUN php artisan config:cache && php artisan route:cache

# Expose dynamic port from Railway
EXPOSE ${PORT}

# Start Laravel server on host 0.0.0.0 and dynamic port
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT}"]
