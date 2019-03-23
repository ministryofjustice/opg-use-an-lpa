FROM composer AS composer

COPY service-viewer/app /app

RUN composer install

FROM php:7-fpm-alpine

COPY service-viewer/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-viewer/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app
