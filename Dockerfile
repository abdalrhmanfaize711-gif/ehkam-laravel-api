FROM php:8.4-cli

# ============================================================
# Install system dependencies and PHP extensions
# ============================================================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ============================================================
# Install Composer
# ============================================================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ============================================================
# Application directory
# ============================================================
WORKDIR /var/www/html

# ============================================================
# Copy Laravel application
# ============================================================
COPY src/ /var/www/html/

# ============================================================
# Install Laravel dependencies
# ============================================================
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# ============================================================
# Laravel storage/cache permissions
# ============================================================
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache

# ============================================================
# Application port
# DockHosting will send traffic to this port
# ============================================================
EXPOSE 8080

# ============================================================
# Start Laravel
# ============================================================
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]