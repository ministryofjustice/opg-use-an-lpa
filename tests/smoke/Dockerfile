FROM mlocati/php-extension-installer:2.8.28@sha256:a7d3e11e022dbc563c85d3c71827cc3a0a338640bcb4dc629ce3e98bcd5875e4 AS php-extension-installer

FROM php:8.3-cli-alpine@sha256:317c33021811b501f3e2db5ad08928c1591340eaa33e7b35245ee771f5ea0824

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer@sha256:f746ca10fd351429e13a6fc9599ccd41d4fc413e036ae8b0dad9e2041adcffcd /usr/bin/composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
