ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

RUN apt-get update \
    && apt-get install -y git unzip libpq-dev libsqlite3-dev default-libmysqlclient-dev libzip-dev \
    && docker-php-ext-install pdo_mysql pgsql pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app
