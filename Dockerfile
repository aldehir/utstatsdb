FROM php:7-apache

# set up GD for graph drawing, install MySQL DB driver
RUN apt-get update && apt-get install -y \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && apt-get purge libjpeg62-turbo-dev libpng-dev -y

COPY . /var/www/html/
