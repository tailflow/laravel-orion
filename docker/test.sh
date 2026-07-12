#!/usr/bin/env bash
set -euo pipefail

LARAVEL_VERSION="${LARAVEL_VERSION:-^12.0}"
DATABASE="${DATABASE:-sqlite}"

composer validate
composer update --with "illuminate/contracts=${LARAVEL_VERSION}" --prefer-dist --no-progress

case "${LARAVEL_VERSION}" in
  ^8.0|^9.0|^10.0|^11.0|^12.0|^13.0)
    composer require "laravel/legacy-factories" --prefer-dist --no-progress -W
    ;;
esac

case "${LARAVEL_VERSION}" in
  ^8.0|^9.0|^10.0|^11.0|^12.0)
    composer update --with "phpunit/phpunit=^9.0" --prefer-dist --no-progress
    ;;
  ^13.0)
    composer update --with "phpunit/phpunit=^11.0" --prefer-dist --no-progress
    ;;
esac

case "${LARAVEL_VERSION}" in
  ^8.0|^9.0|^10.0|^11.0|^12.0|^13.0)
    vendor/bin/phpunit -c phpunit.xml.dist --migrate-configuration
    ;;
esac

case "${DATABASE}" in
  sqlite)
    DB_CONNECTION=sqlite DB_DATABASE=':memory:' vendor/bin/phpunit --debug
    ;;
  mysql)
    DB_CONNECTION=mysql DB_HOST=mysql DB_PORT=3306 DB_DATABASE=orion DB_USERNAME=root \
      vendor/bin/phpunit --debug
    ;;
  pgsql)
    DB_CONNECTION=pgsql DB_HOST=pgsql DB_PORT=5432 DB_DATABASE=orion \
      DB_USERNAME=postgres DB_PASSWORD=postgres \
      vendor/bin/phpunit --debug
    ;;
  *)
    echo "Unknown DATABASE: ${DATABASE} (expected sqlite, mysql, or pgsql)" >&2
    exit 1
    ;;
esac
