FROM composer AS composer

COPY service-front/app /app

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader

FROM php:7-fpm-alpine

RUN apk add --update --no-cache icu

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
        && pecl install xdebug \
        && pecl install apcu \
        && docker-php-ext-enable apcu \
        && docker-php-ext-install intl \
        && pecl clear-cache \
        && apk del .build-dependencies

COPY service-front/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
