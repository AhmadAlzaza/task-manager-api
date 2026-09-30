# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Stage 1: Build PHP extensions
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-bookworm AS php-extensions

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && rm -rf /var/lib/apt/lists/*


# -----------------------------------------------------------------------------
# Stage 2: Build application and install production dependencies
# -----------------------------------------------------------------------------
FROM php-extensions AS build

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Create Laravel writable directories.
RUN mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Remove development-only Laravel/Scribe configuration and
# any cached bootstrap files copied from the development environment.
RUN rm -f \
        config/scribe.php \
        bootstrap/cache/*.php \
    && sed -i '/ScribeServiceProvider/d' bootstrap/providers.php

# Install production-only Composer dependencies.
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Rebuild the autoloader without running Composer scripts.
RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-interaction \
    --no-scripts

# Generate Laravel's production package manifest.
RUN php artisan package:discover --ansi


# -----------------------------------------------------------------------------
# Stage 3: Production PHP-FPM runtime
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-bookworm AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng16-16 \
        libonig5 \
        libxml2 \
    && rm -rf /var/lib/apt/lists/*

# Copy compiled PHP extensions.
COPY --from=php-extensions \
    /usr/local/lib/php/extensions/ \
    /usr/local/lib/php/extensions/

COPY --from=php-extensions \
    /usr/local/etc/php/conf.d/ \
    /usr/local/etc/php/conf.d/

# Use PHP production configuration.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN printf '%s\n' 'expose_php = Off' > "$PHP_INI_DIR/conf.d/99-production.ini"

WORKDIR /var/www

# Copy the prepared production application.
COPY --from=build /var/www /var/www

# Ensure Laravel writable directories exist and have the correct permissions.
RUN mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R ug+rwX \
        storage \
        bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
