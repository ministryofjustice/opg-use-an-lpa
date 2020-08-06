FROM composer AS composer

COPY service-front/app/composer.json service-front/app/composer.lock /app/

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7-fpm-alpine

RUN set -xe \
        && apk add --update icu \
        && apk add --no-cache --virtual .build-dependencies $PHPIZE_DEPS icu-dev \
        && pecl install xdebug apcu redis \
        && docker-php-ext-install intl \
        && docker-php-ext-enable apcu redis intl \
        && pecl clear-cache \
        && apk del .build-dependencies \
        && rm -rf /var/cache/apk/*

COPY service-front/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
