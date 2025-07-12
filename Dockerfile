### Dockerfile for Laravel on Railway with correct key generation

```dockerfile
# Use official PHP image with FPM
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /app

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# Copy application source
COPY . ./

# Copy sample env and generate APP_KEY
# (ensures .env exists for artisan commands)
RUN cp .env.example .env \
    && php artisan key:generate --ansi

# Cache config, routes (optional but speeds up boot)
RUN php artisan config:cache && php artisan route:cache

# Expose dynamic port from Railway
EXPOSE ${PORT}

# Start Laravel server on host 0.0.0.0 and dynamic port
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT}"]
