FROM php:8.1-cli-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /usr/bin/composer /usr/bin/
COPY ./local-ssl/certs/ca.pem /tmp/ca.pem
RUN cat /tmp/ca.pem >> /etc/ssl/certs/ca-certificates.crt

RUN set -xe \
  && apk add --update --no-cache chromium \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app
