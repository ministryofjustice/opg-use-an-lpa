FROM composer AS composer

COPY service-viewer/app /app

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader

FROM php:7-fpm-alpine

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
        && pecl install apcu \
        && docker-php-ext-enable apcu \
        && apk del .build-dependencies

COPY service-viewer/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-viewer/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app
