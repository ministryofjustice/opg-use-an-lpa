#
# Base image containing correct PHP, extensions and configuration
#
FROM composer:2.8.9@sha256:623d15a4aae78c868a35c3942add4bb9b2f98e4a3ec26e1928c84df14695d4b0 AS composer

FROM mlocati/php-extension-installer:2.7.34@sha256:277b9751adb3120413fc71ef1859bb47fabb73b60fdbc3cc2f2af6451573046e AS php-extension-installer

FROM php:8.4.7-fpm-alpine3.21@sha256:d445d374b3b72968124be3a3a3040a995192dc88d36373bf12d406ac5475098d AS php-base

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache icu=74.2-r0 gettext=0.22.5-r0 fcgi=2.4.2-r4 \
  && install-php-extensions apcu redis-^5 intl gettext opcache \
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
