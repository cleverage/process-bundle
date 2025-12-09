FROM php:8.5-fpm-alpine

ARG UID
ARG GID

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY /conf.d/ "$PHP_INI_DIR/conf.d/"

RUN apk update && apk add \
    tzdata \
    shadow \
    nano \
    bash \
    icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime \
    && sed -i "s/^;date.timezone =.*/date.timezone = Europe\/Paris/" $PHP_INI_DIR/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN usermod -u $UID www-data \
    && groupmod -g $GID www-data

USER www-data:www-data

WORKDIR /var/www
