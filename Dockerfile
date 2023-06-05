ARG PHP_VERSION=7

FROM composer:2 AS build

WORKDIR /build

COPY composer.* ./

RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs --classmap-authoritative

COPY . .

RUN composer dump-autoload -o

FROM php:${PHP_VERSION}-cli-alpine

RUN apk add --no-cache -t .production-deps \
    hiredis libzip

RUN set -xe \
    && apk add --no-cache -t .build-deps $PHPIZE_DEPS \
    hiredis-dev libzip-dev \
    && pecl install -f apcu-5.1.22 redis-5.3.7 mongodb-1.15.1 \
    && wget -qO- https://github.com/nrk/phpiredis/archive/v1.1.zip | busybox unzip - \
    && ( \
        cd phpiredis-1.1 \
        && phpize \
        && ./configure --enable-phpiredis \
        && make \
        && make install \
    ) \
    && rm -r phpiredis-1.1 \
    && docker-php-source extract \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install -j$(nproc) pcntl zip \
    && docker-php-ext-enable apcu redis mongodb phpiredis \
    && docker-php-source delete \
    && pecl clear-cache \
    && rm -rf /tmp/* \
    && apk del --purge .build-deps

COPY --from=build --chown=www-data:www-data /build /var/www/resque
COPY --from=build /usr/bin/composer /usr/local/bin/composer

RUN chmod -R +x /var/www/resque/bin
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/resque
