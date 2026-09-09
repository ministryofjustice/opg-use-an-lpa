#
# Base image containing correct PHP, extensions and configuration
#
FROM docker.io/composer/composer:2.10-bin@sha256:ae894813776e18b7ae0fa8f9f2a63bf1aa624e287cd4c23a6d64f56fe999d8f9 AS composer

FROM docker.io/mlocati/php-extension-installer:2.11@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138 AS php-extension-installer

FROM docker.io/php:8.5-fpm-alpine3.22@sha256:95588bfaf1b890e3fc1f308a0a23539c4f03ce28a4fc770473ae3899d6669777 AS php-base

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

RUN --mount=type=secret,id=composer-auth COMPOSER_AUTH=$(cat /run/secrets/composer-auth) \
    composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --no-dev && \
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

RUN --mount=type=secret,id=composer-auth COMPOSER_AUTH=$(cat /run/secrets/composer-auth) \
    composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --ignore-platform-reqs && \
    composer check-platform-reqs

CMD ["php-fpm"]

#
# For safety reasons have the production image be the output
#
FROM app AS production
COPY scripts/docker_hardening/harden.sh /harden.sh
RUN /harden.sh && rm /harden.sh
USER www-data

CMD ["php-fpm"]
