ARG PHP_VERSION=8.1
FROM php:${PHP_VERSION}-cli

# Basic tools
RUN apt-get update
RUN apt-get install -y wget git zip unzip

# Composer install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP setup
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY Resources/tests/environment/php/conf.ini "$PHP_INI_DIR/conf.d/"

# Basic sample symfony app install
ARG SF_ENV=sf5
ENV APP_ENV test
RUN mkdir /app
WORKDIR /app
ENV HOME /app
COPY Resources/tests/environment/${SF_ENV}/composer.json /app
RUN composer install

# Additionnal config files for a test env
COPY phpstan.neon /app/
COPY Resources/tests/environment/${SF_ENV} /app/

# Drop the process-bundle sources into this folder
RUN mkdir /src-cleverage_process
