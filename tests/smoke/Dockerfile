FROM docker.io/composer/composer:2.10-bin@sha256:536116acd18cd2d99d0351c1dfded22c83f34cef481d83f2747ff2cbca7587cd AS composer

FROM docker.io/mlocati/php-extension-installer:2.12@sha256:1afade3e29cfc97362cf5885e5ac333bf2faab1146cb28ebbb59b17e68f87e88 AS php-extension-installer

FROM docker.io/php:8.4-cli-alpine@sha256:17bbc8e655709d42c56e4eb24a9c715105a7e446358dba4d26dc06e514d520ef

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

WORKDIR /app

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf
