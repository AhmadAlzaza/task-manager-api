# استخدام نسخة خفيفة وسريعة من PHP مخصصة لبيئة الإنتاج
FROM php:8.3-fpm

# تثبيت الاعتماديات الأساسية لنظام تشغيل الحاوية (Linux)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client

# تنظيف ذاكرة التخزين المؤقت لتصغير حجم الصورة
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# تثبيت إضافات PHP التي يحتاجها Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# جلب أداة Composer من صورتها الرسمية
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تحديد مسار العمل داخل الحاوية
WORKDIR /var/www

# نسخ ملفات المشروع إلى الحاوية
COPY . /var/www

# إعطاء الصلاحيات اللازمة لمجلدات التخزين والتخزين المؤقت (مهم جداً لتجنب أخطاء 500)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# فتح البورت 9000 الذي يستخدمه PHP-FPM للتواصل مع Nginx
EXPOSE 9000

# الأمر الافتراضي عند تشغيل الحاوية
CMD ["php-fpm"]
