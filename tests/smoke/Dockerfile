FROM docker.io/composer/composer:2.10-bin@sha256:536116acd18cd2d99d0351c1dfded22c83f34cef481d83f2747ff2cbca7587cd AS composer

FROM docker.io/mlocati/php-extension-installer:2.11@sha256:b6d3fa381b9ba5cf051117c1c601d6a523b590e534bf3d56eb4fbe352949c138 AS php-extension-installer

FROM docker.io/php:8.4-cli-alpine@sha256:2f389f933c3cc58cc622bd243bb4ecff7e6553e2de4387a239bca640c988be19

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=composer /composer /usr/bin/

WORKDIR /app

RUN set -xe \
  && apk add --update --no-cache chromium autoconf \
  && ln -s /usr/bin/chromium-browser /usr/bin/google-chrome-stable \
  && install-php-extensions xdebug \
  && rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del autoconf

COPY tests/smoke/composer.json tests/smoke/composer.lock /app/

RUN --mount=type=secret,id=composer-auth COMPOSER_AUTH=$(cat /run/secrets/composer-auth) \
    composer install --prefer-dist --no-interaction --no-scripts --optimize-autoloader --no-dev && \
    composer check-platform-reqs
