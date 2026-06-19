FROM docker.io/composer/composer:2.9-bin@sha256:2cde59fd0009d0b4f0fa8532a189290352d9fe3d80fbcc3a887ac69b9eeeef27 AS composer

FROM docker.io/mlocati/php-extension-installer:2.11@sha256:bd9ea77afcbc8e55e58d55ca9a39153925367e972827d2f648c949fd0e44aaca AS php-extension-installer

FROM docker.io/php:8.4-cli-alpine@sha256:4f4fc56fe4ba7b7d241c371eda011b27ca4f3b25bf2a37956ee06e966777d696

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
