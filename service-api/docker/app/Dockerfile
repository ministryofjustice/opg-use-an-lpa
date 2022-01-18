FROM composer:2.0.14 AS composer

COPY service-api/app /app

RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts --optimize-autoloader

FROM composer:2.0.14 AS composer-dev

COPY service-api/app /app

RUN composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader

FROM php:7-fpm-alpine3.14 AS production

# RUN apk add --update --no-cache fcgi \
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
  && apk upgrade curl \
  && pecl install xdebug \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && pecl clear-cache \
  && apk del .build-dependencies

COPY service-api/app /app
COPY --from=composer /app/vendor /app/vendor
COPY service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-api/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf
COPY service-api/docker/app/health-check.sh /usr/local/bin/health-check.sh

WORKDIR /app

HEALTHCHECK --interval=10s --timeout=30s --retries=3 CMD ["/usr/local/bin/health-check.sh"]

CMD ["php-fpm"]

FROM production as development

COPY --from=composer-dev /app/vendor /app/vendor

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
  && php-fpm
