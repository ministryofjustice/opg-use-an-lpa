FROM mlocati/php-extension-installer:2.9.28@sha256:e8a3393d79113a61e536d5fed30df4f15c0e4f67d30d0fce08c02d4507cad397 AS php-extension-installer

FROM php:8.5-cli-alpine@sha256:166a9ddf511d6782b44a05a086a8de5271483441c9f8c8f5d526ae0d5466c32d

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer@sha256:f746ca10fd351429e13a6fc9599ccd41d4fc413e036ae8b0dad9e2041adcffcd /usr/bin/composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
