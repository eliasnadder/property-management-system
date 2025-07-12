### Dockerfile for Laravel on Railway with robust PHP extension installation
# Base image
FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    build-essential \
    libpq-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    zlib1g-dev \
    git \
    unzip \
    zip \
    curl \
    && docker-php-ext-install pdo_pgsql zip xml mbstring bcmath \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy composer files for caching
COPY composer.json composer.lock ./

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Copy application source
COPY . ./

# Prepare environment and generate APP_KEY
RUN cp .env.example .env \
    && php artisan key:generate --ansi

# Cache configuration and routes
RUN php artisan config:cache \
    && php artisan route:cache

# Expose dynamic port
EXPOSE ${PORT}

# Define entrypoint and command
ENTRYPOINT ["php", "artisan"]
CMD ["serve", "--host=0.0.0.0", "--port=${PORT}"]
