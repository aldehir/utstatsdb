FROM php:8-fpm

RUN apt-get update && apt-get install -y \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && apt-get purge libjpeg62-turbo-dev libpng-dev -y

COPY . /var/www/html/
