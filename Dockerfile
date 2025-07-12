FROM php:8.2-fpm

# تثبيت dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app
COPY . /app

# نصّب Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# أنشئ المفتاح
RUN php artisan key:generate

# Expose المتغيّر PORT
EXPOSE ${PORT}

# Start command
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT}"]
