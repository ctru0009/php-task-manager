FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libxml2-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli dom mbstring xml xmlwriter \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-task-manager.ini

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html
