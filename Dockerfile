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
# Stage 3: Generate static API documentation
# -----------------------------------------------------------------------------
FROM php-extensions AS docs

ARG DOCS_BASE_URL=http://localhost:8000

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN mkdir -p \
    bootstrap/cache \
    database \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Prepare an isolated SQLite environment for Scribe generation.
RUN touch database/database.sqlite \
    && cp .env.example .env

# Use the deployment URL when generating static documentation.
ENV APP_URL=${DOCS_BASE_URL}

# Install development dependencies because Scribe is require-dev.
RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Generate the application key after Composer dependencies are available.
RUN php artisan key:generate --force

# Generate Laravel's package manifest.
RUN php artisan package:discover --ansi

# Ensure the documentation database is ready.
RUN DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/database/database.sqlite \
    php artisan migrate --force

# Generate static documentation into public/docs/.
RUN DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/database/database.sqlite \
    php artisan scribe:generate


# -----------------------------------------------------------------------------
# Stage 4: Production PHP-FPM runtime
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

# Copy generated static API documentation.
COPY --from=docs /var/www/public/docs /var/www/public/docs

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
