#
# Base image containing correct PHP, extensions and configuration
#
FROM composer:2.9.3@sha256:c404e6f07bdebf8a8c605be5b5fab88eef737f6e4acfba3651d39c824ce224d4 AS composer

FROM mlocati/php-extension-installer:2.9.28@sha256:e8a3393d79113a61e536d5fed30df4f15c0e4f67d30d0fce08c02d4507cad397 AS php-extension-installer

FROM php:8.5.2-fpm-alpine3.22@sha256:e1f098c0d7c7aafa0c9af53a1e61c9fd876db250df92cf9cb7fe96ef705960f1 AS php-base

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache fcgi=2.4.6-r0 \
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

# All this neccessary for PACT FFI to work (apart from xdebug extension)
RUN set -xe \
  && apk add --no-cache libgcc=14.2.0-r6 \
  && install-php-extensions xdebug-stable ffi \
  && echo "ffi.enable=true" >> /usr/local/etc/php/conf.d/docker-php-ext-ffi.ini

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
