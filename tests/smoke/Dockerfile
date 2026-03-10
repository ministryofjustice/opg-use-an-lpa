FROM docker.io/composer/composer:2.9-bin@sha256:d2cb40ee5709ed47a41f0d5de97a270c73c30d2efc16163714c57f56bf2690cf AS composer

FROM docker.io/mlocati/php-extension-installer:2.9@sha256:3ebe94427d3fbb0de3858a51bedf909d4ecc4764042151eb9eaf71e79cb2b97d AS php-extension-installer

FROM docker.io/php:8.3-cli-alpine@sha256:8281275c2b7fb10c149a049e832fae6e936890f839561dbe2ca0d69fc322853f

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
