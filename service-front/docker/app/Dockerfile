FROM composer AS composer

COPY service-front/app /app

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7-fpm-alpine

RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
        && pecl install xdebug \
        && pecl install apcu \
        && pecl install redis \
        && docker-php-ext-enable apcu \
        && docker-php-ext-enable redis \
        && pecl clear-cache \
        && apk del .build-dependencies

COPY service-front/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
