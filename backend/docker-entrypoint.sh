#!/bin/sh
set -eu

if [ ! -f vendor/autoload.php ] || \
   [ ! -f vendor/composer/installed.php ] || \
   [ composer.lock -nt vendor/composer/installed.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

exec "$@"
