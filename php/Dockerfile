FROM php:8.2-fpm

# Extensions PHP
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev libfreetype-dev \
    libzip-dev zip unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Config PHP
RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=55M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
