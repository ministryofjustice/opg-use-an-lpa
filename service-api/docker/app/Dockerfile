#
# Base image containing correct PHP, extensions and configuration
#
FROM docker.io/composer/composer:2.10-bin@sha256:536116acd18cd2d99d0351c1dfded22c83f34cef481d83f2747ff2cbca7587cd AS composer

FROM docker.io/mlocati/php-extension-installer:2.12@sha256:1afade3e29cfc97362cf5885e5ac333bf2faab1146cb28ebbb59b17e68f87e88 AS php-extension-installer

FROM docker.io/php:8.4-fpm-alpine3.24@sha256:c70e50a06d85d73d5790848c4e6c9e32b470052e1c86d67a1fb784a97d247d60 AS php-base

ENV OPG_PHP_POOL_CHILDREN_MAX="25"

# OpenTelemetry defaults. Tracing is instrumented (see Telemetry\Tracer) but not exported unless
# OTEL_TRACES_EXPORTER is overridden at runtime, e.g. "otlp" when the collector sidecar is deployed.
ENV OTEL_PHP_AUTOLOAD_ENABLED=true \
    OTEL_SERVICE_NAME=api.use-an-lpa \
    OTEL_TRACES_EXPORTER=none \
    OTEL_METRICS_EXPORTER=none \
    OTEL_LOGS_EXPORTER=none \
    OTEL_PROPAGATORS=tracecontext,xray

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN set -xe \
  && apk add --update --no-cache fcgi=2.4.6-r0 \
  && install-php-extensions apcu redis-^6 gmp opcache opentelemetry \
  && rm /usr/bin/install-php-extensions

COPY service-api/docker/app/app-php.ini /usr/local/etc/php/conf.d/
COPY service-api/docker/app/fpm-pool.ini /usr/local/etc/php-fpm.d/zz-logging.conf
COPY service-api/docker/app/health-check.sh /usr/local/bin/health-check.sh

WORKDIR /app

#
# Download production dependencies
#
FROM php-base AS dependency

COPY --from=composer /composer /usr/bin/
COPY service-api/app/composer.json service-api/app/composer.lock /app/

RUN --mount=type=secret,id=composer-auth COMPOSER_AUTH=$(cat /run/secrets/composer-auth) \
    composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --no-dev && \
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
COPY --from=composer /composer /usr/bin/

COPY service-api/docker/app/app-php.development.ini /usr/local/etc/php/conf.d/app-php.ini

# All this neccessary for PACT FFI to work (apart from xdebug extension)
RUN set -xe \
  && apk add --no-cache libgcc=15.2.0-r5 \
  && install-php-extensions xdebug-stable ffi \
  && echo "ffi.enable=true" >> /usr/local/etc/php/conf.d/docker-php-ext-ffi.ini

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
