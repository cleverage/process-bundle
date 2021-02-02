ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-cli

ARG SF_ENV=sf4
ARG BLACKFIRE_PHP_VERSION=72
ARG BLACKFIRE_PROBE_VERSION=1.29.1
ARG BLACKFIRE_AGENT_VERSION=1.30.0

# Basic tools
RUN apt-get update
RUN apt-get install -y wget git zip unzip

# Blackfire install
RUN curl -o $(php -i | grep -P "^extension_dir " | sed "s/^.* => //g")/blackfire.so -D - -L -s https://packages.blackfire.io/binaries/blackfire-php/${BLACKFIRE_PROBE_VERSION}/blackfire-php-linux_amd64-php-${BLACKFIRE_PHP_VERSION}.so
RUN curl -o /usr/bin/blackfire-agent -L https://packages.blackfire.io/binaries/blackfire-agent/${BLACKFIRE_AGENT_VERSION}/blackfire-agent-linux_amd64
RUN chmod +x /usr/bin/blackfire-agent
RUN curl -o /usr/bin/blackfire -L https://packages.blackfire.io/binaries/blackfire-agent/${BLACKFIRE_AGENT_VERSION}/blackfire-cli-linux_amd64
RUN chmod +x /usr/bin/blackfire
RUN docker-php-ext-enable blackfire

# Composer install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP setup
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY Resources/tests/environment/php/conf.ini "$PHP_INI_DIR/conf.d/"

# Basic sample symfony app install
RUN mkdir /app
WORKDIR /app
ENV HOME /app
COPY Resources/tests/environment/${SF_ENV}/composer.json /app
RUN composer install

# Additionnal config files for a test env
COPY Resources/tests/environment/${SF_ENV} /app/

# Drop the process-bundle sources into this folder
RUN mkdir /src-cleverage_process
