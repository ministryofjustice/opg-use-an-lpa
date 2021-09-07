FROM composer:2.0.14 AS composer

COPY service-front/app/composer.json service-front/app/composer.lock /app/

RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM composer:2.0.14 AS composer-dev

COPY service-front/app/composer.json service-front/app/composer.lock /app/

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs

FROM php:7-fpm-alpine3.14 AS production

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache icu gettext \
  && apk upgrade curl \
  && install-php-extensions apcu redis intl gettext xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY service-front/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf

WORKDIR /app

CMD php-fpm

FROM production as development

COPY --from=composer-dev /app/vendor /app/vendor

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
  && php-fpm
