FROM mlocati/php-extension-installer:2.9.27@sha256:84950d61e2873817f814fd2c486d0143566a5b7aa36b9c926a138dafb9a07753 AS php-extension-installer

FROM php:8.5-cli-alpine@sha256:980a432cf92086a743d0fa4ee169374f63541ddb28e7c735252375f472b602dd

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer@sha256:f746ca10fd351429e13a6fc9599ccd41d4fc413e036ae8b0dad9e2041adcffcd /usr/bin/composer /usr/bin/

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

WORKDIR /app
