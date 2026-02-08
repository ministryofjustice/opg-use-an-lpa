#
# Base image containing correct PHP, extensions and configuration
#
FROM docker.io/composer/composer:2.9-bin@sha256:5107a53c864a9fb2579406968b95c4746a906db9c018779e1b63a40ec96a145c AS composer

FROM docker.io/mlocati/php-extension-installer:2.9@sha256:3ebe94427d3fbb0de3858a51bedf909d4ecc4764042151eb9eaf71e79cb2b97d AS php-extension-installer

FROM docker.io/php:8.3-fpm-alpine3.22@sha256:3c3114fdb04a4d03dfccdf00ce5a3e9323677b853da0f1eb542d748a4f2f411b AS php-base

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

COPY --from=composer /composer /usr/bin/
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
COPY --from=composer /composer /usr/bin/

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
