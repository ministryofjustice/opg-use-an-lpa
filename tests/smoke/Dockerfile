FROM mlocati/php-extension-installer:2.9.24@sha256:b17b8107fe8480d5f88c7865b83bb121a344876272eb6b7c9e9f331c931695be AS php-extension-installer

FROM php:8.5-cli-alpine@sha256:c3f49280f8b7913c59f9467f0c0586af5b377292910c07976cf7ac4d3986ef73

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app
