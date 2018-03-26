FROM php:7-apache

# set up GD for graph drawing
RUN apt-get update && apt-get install -y \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get purge libjpeg62-turbo-dev libpng-dev -y

COPY . /var/www/html/
