FROM docker.io/composer/composer:2.10-bin@sha256:536116acd18cd2d99d0351c1dfded22c83f34cef481d83f2747ff2cbca7587cd AS composer

FROM docker.io/mlocati/php-extension-installer:2.11@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138 AS php-extension-installer

FROM docker.io/php:8.5-cli-alpine@sha256:93684051146ec037620855feb77f278090bde45ddc030801cd3f2a7685bc4deb

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

WORKDIR /app

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf
