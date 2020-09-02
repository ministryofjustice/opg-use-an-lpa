FROM composer AS composer

COPY service-front/app/composer.json service-front/app/composer.lock /app/

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7-fpm-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
        && apk add --update --no-cache icu gettext \
        && install-php-extensions apcu redis intl gettext xdebug \
        && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY service-front/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf

WORKDIR /app

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
    && php-fpm
