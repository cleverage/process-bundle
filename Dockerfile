ARG PHP_VERSION=7.1
FROM php:${PHP_VERSION}-cli

ARG SF_ENV=sf4

# Basic tools
RUN apt-get update
RUN apt-get install -y wget git zip unzip

# Composer install
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv /composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# Basic sample symfony app install
RUN mkdir /app
WORKDIR /app
COPY Resources/tests/environment/${SF_ENV}/composer.json /app
RUN composer install

# Additionnal config files for a test env
COPY Resources/tests/environment/${SF_ENV} /app/

# Drop the process-bundle sources into this folder
RUN mkdir /src-cleverage_process
