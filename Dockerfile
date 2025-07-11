# استخدم إصدار PHP الذي تريده. غيّره إلى 8.1 إذا كان مشروعك يتطلبه
FROM php:8.2-cli

# تثبيت الأدوات الأساسية التي يحتاجها Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# تثبيت إضافات PHP الشائعة
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# تثبيت Composer (مدير حزم PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تعيين مجلد العمل داخل الحاوية
WORKDIR /app

# نسخ كل ملفات المشروع إلى الحاوية
COPY . .

# تثبيت حزم المشروع باستخدام Composer
RUN composer install --no-dev --no-interaction --optimize-autoloader

# هذا السطر ليس مهماً جداً لأن Render سيستخدم أمر التشغيل الخاص به
# لكن من الجيد وجوده كقيمة افتراضية
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
