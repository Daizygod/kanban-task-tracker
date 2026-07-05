FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        git \
        unzip \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        bcmath \
        pcntl \
        intl \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
