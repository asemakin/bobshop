FROM php:8.2-apache

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      git unzip libzip-dev; \
    docker-php-ext-install mysqli pdo pdo_mysql; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer install if needed (will run inside the container)
# RUN composer install --no-interaction --no-progress --prefer-dist || true