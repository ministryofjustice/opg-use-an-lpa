#
# Base image containing correct PHP, extensions and configuration
#
FROM composer:2.8.8@sha256:1e30756b14356f49b84a9086c0bbba05ab10f76df3f73fb536582b71fb1b1078 AS composer

FROM mlocati/php-extension-installer:2.7.34@sha256:277b9751adb3120413fc71ef1859bb47fabb73b60fdbc3cc2f2af6451573046e AS php-extension-installer

FROM php:8.4.7-fpm-alpine3.21@sha256:d445d374b3b72968124be3a3a3040a995192dc88d36373bf12d406ac5475098d AS php-base

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

RUN set -xe \
  && apk add --update --no-cache fcgi=2.4.2-r4 \
  && install-php-extensions apcu gmp opcache \
  && rm /usr/bin/install-php-extensions

COPY service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-api/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf
COPY service-api/docker/app/health-check.sh /usr/local/bin/health-check.sh

WORKDIR /app

#
# Download production dependencies
#
FROM php-base AS dependency

COPY --from=composer /usr/bin/composer /usr/bin/
COPY service-api/app/composer.json service-api/app/composer.lock /app/

RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts --optimize-autoloader && \
    composer check-platform-reqs

#
# Create production image using base and app files
#
FROM php-base AS app

COPY service-api/app /app
COPY --from=dependency /app/vendor /app/vendor

#
# Install development dependencies and tools into production image
#
FROM app AS development
COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/

COPY service-api/docker/app/app-php.development.ini /usr/local/etc/php/conf.d/app-php.ini

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
