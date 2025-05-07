FROM php:8.2-fpm

# Install necessary dependencies
RUN apt-get update && apt-get install -y \
    sqlite3 libsqlite3-dev unzip curl \
    && docker-php-ext-install pdo pdo_sqlite

# Install Redis extension for PHP
RUN pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www