FROM php:8-cli-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app
