#
# Base image containing correct PHP, extensions and configuration
#
FROM php:8.1.23-fpm-alpine3.18 AS php-base

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache fcgi \
  && install-php-extensions apcu \
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

RUN composer install --prefer-dist --no-dev --no-interaction --no-scripts --optimize-autoloader
RUN composer check-platform-reqs

#
# Create production image using base and app files
#
FROM php-base AS app

COPY service-api/app /app
COPY --from=dependency /app/vendor /app/vendor

#
# Install development dependencies and tools into production image
#
FROM app as development
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/

COPY service-api/docker/app/app-php.development.ini /usr/local/etc/php/conf.d/app-php.ini

RUN set -xe \
  && install-php-extensions xdebug-stable \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Development images are only ever run using compose so will have this mounted in as a volume
RUN rm -rf /app/vendor

CMD ([[ -z "${ENABLE_XDEBUG}" ]] || docker-php-ext-enable xdebug) \
  && php-fpm

#
# For safety reasons have the production image be the output
#
FROM app as production
COPY scripts/docker_hardening/harden.sh /harden.sh
RUN /harden.sh && rm /harden.sh
USER www-data

CMD ["php-fpm"]
