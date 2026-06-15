FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libicu-dev libpng-dev \
    libonig-dev libxml2-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    intl pdo pdo_mysql mbstring \
    xml zip opcache

RUN echo "opcache.enable=1\nopcache.memory_consumption=128\nopcache.validate_timestamps=1\nopcache.revalidate_freq=0" \
    >> /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data var/ && chmod -R 775 var/

EXPOSE 9000
CMD ["php-fpm"]
