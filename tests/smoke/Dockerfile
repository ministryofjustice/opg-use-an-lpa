FROM docker.io/composer/composer:2.9-bin@sha256:5107a53c864a9fb2579406968b95c4746a906db9c018779e1b63a40ec96a145c AS composer

FROM docker.io/mlocati/php-extension-installer:2.9@sha256:3a33a57571103fbfecf5995f910225151ba70b45d6294a113d6565c72b7d4d43 AS php-extension-installer

FROM docker.io/php:8.5-cli-alpine@sha256:26c79a16621a4dc56b0fbb2fa94df4110c972b19855e5aed1cf952ecfa466785

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
