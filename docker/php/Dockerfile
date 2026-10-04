FROM php:8.4-fpm

RUN apt-get update && apt-get install -y git unzip libzip-dev libpng-dev \
    && docker-php-ext-install pdo_mysql bcmath zip gd exif pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/html
