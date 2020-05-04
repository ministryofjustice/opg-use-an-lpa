# CicrleCI have already done all the hard work of setting up headless chrome and such with PHP7.x
FROM circleci/php:7-cli-browsers

RUN sudo mkdir /test && sudo chown circleci.circleci /test
COPY tests/smoke/ /test
COPY tests/features /test/features
WORKDIR /test

RUN docker-php-ext-disable xdebug

RUN composer install --prefer-dist --no-suggest --no-interaction --no-scripts --optimize-autoloader

ENTRYPOINT []