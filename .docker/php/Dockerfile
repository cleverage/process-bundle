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

# pcov, to measure the coverage as the CI (built from its tarball: PECL downloads are unreliable)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && curl -sSL https://github.com/krakjoe/pcov/archive/refs/tags/v1.0.12.tar.gz | tar xz -C /tmp \
    && cd /tmp/pcov-1.0.12 && phpize && ./configure && make -j"$(nproc)" && make install \
    && docker-php-ext-enable pcov \
    && cd / && rm -rf /tmp/pcov-1.0.12 && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN usermod -u $UID www-data \
    && groupmod -g $GID www-data

USER www-data:www-data

WORKDIR /var/www
