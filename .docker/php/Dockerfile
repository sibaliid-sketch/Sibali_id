# PHP-FPM container image
# Build PHP-FPM 8.2 image for app
# Multi-stage: base php:8.2-fpm, install extensions (pdo_mysql, redis, gd, mbstring, exif, bcmath, zip), composer install (no-dev for prod), build assets if needed, create non-root user www, enable opcache in prod. Include Xdebug layer in dev image.

# Base stage
FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    redis \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (no-dev for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy application code
COPY . .

# Create non-root user
RUN addgroup -g 1000 www && \
    adduser -D -s /bin/sh -G www www

# Set permissions
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Production stage
FROM base AS production

# Enable opcache
RUN docker-php-ext-install opcache

COPY .docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Switch to non-root user
USER www

# Development stage
FROM base AS development

# Install Xdebug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Switch to non-root user
USER www

# Default to production
FROM production
