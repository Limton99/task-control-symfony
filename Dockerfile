# PHP 8.3 FPM (Debian) + нужные расширения
FROM php:8.3-fpm

# Системные пакеты и расширения PHP
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libicu-dev libzip-dev \
    libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl pdo pdo_pgsql zip gd opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Рабочая папка
WORKDIR /var/www/html

# Настройки PHP (dev)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/php-dev.ini

# По умолчанию php-fpm слушает 9000
CMD ["php-fpm"]
