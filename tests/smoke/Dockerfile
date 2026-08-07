FROM docker.io/composer/composer:2.9-bin@sha256:2cde59fd0009d0b4f0fa8532a189290352d9fe3d80fbcc3a887ac69b9eeeef27 AS composer

FROM docker.io/mlocati/php-extension-installer:2.11@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138 AS php-extension-installer

FROM docker.io/php:8.5-cli-alpine@sha256:0554eb53778b5316f6b9a3447c9dfa3cf2141c0c02ff816c42cdc9aa240a34aa

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
