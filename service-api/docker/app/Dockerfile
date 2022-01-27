FROM composer:2.0.14 AS composer

COPY service-api/app/composer.json service-api/app/composer.lock /app/

RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs


FROM composer:2.0.14 AS composer-dev

COPY service-api/app/composer.json service-api/app/composer.lock /app/

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs


FROM php:7.4-fpm-alpine3.15 AS production

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache fcgi \
  && apk upgrade --update --no-cache curl \
  && install-php-extensions apcu xdebug \
  && rm /usr/bin/install-php-extensions

COPY service-api/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-api/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf
COPY service-api/docker/app/health-check.sh /usr/local/bin/health-check.sh

WORKDIR /app

CMD ["php-fpm"]


FROM production as development

COPY --from=composer-dev /app/vendor /app/vendor

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && rm /usr/bin/install-php-extensions

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
  && php-fpm
