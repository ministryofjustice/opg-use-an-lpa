#
# Base image containing correct PHP, extensions and configuration
#
FROM composer:2.8.9@sha256:9f2a31e610b009bbf38cbc378384f74b60295dbe44ac0dd6077b5f74391dd15e AS composer

FROM mlocati/php-extension-installer:2.8.28@sha256:a7d3e11e022dbc563c85d3c71827cc3a0a338640bcb4dc629ce3e98bcd5875e4 AS php-extension-installer

FROM php:8.3.22-fpm-alpine3.22@sha256:13147c0420a956ae3ecab236c0a8ee8073d9bb100be6dde7f23c4c447e59ec8b AS php-base

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache icu=76.1-r1 gettext=0.24.1-r0 fcgi=2.4.6-r0 \
  && install-php-extensions apcu redis-^6 intl gettext opcache \
  && rm /usr/bin/install-php-extensions

COPY service-front/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-front/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf
COPY service-front/docker/app/health-check.sh /usr/local/bin/health-check.sh

WORKDIR /app

#
# Download production dependencies
#
FROM php-base AS dependency

COPY --from=composer /usr/bin/composer /usr/bin/
COPY service-front/app/composer.json service-front/app/composer.lock /app/

RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts --optimize-autoloader && \
    composer check-platform-reqs

#
# Create production image using base and app files
#
FROM php-base AS app

COPY service-front/app /app
COPY --from=dependency /app/vendor /app/vendor

#
# Install development dependencies and tools into production image
#
FROM app AS development
COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/

COPY service-front/docker/app/app-php-development.ini /usr/local/etc/php/conf.d/app-php.ini

RUN set -xe \
  && install-php-extensions xdebug-stable

# Development images are only ever run using compose so will have this mounted in as a volume
RUN rm -rf /app/vendor

CMD ["php-fpm"]

#
# For safety reasons have the production image be the output
#
FROM app AS production
COPY scripts/docker_hardening/harden.sh /harden.sh
RUN /harden.sh && rm /harden.sh
USER www-data

CMD ["php-fpm"]
