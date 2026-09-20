#!/bin/sh
set -e

if [ ! -f storage/database.sqlite ]; then
    FIRST_RUN=1
fi

php bin/console.php migrate

if [ "$FIRST_RUN" = "1" ]; then
    php bin/console.php seed --demo
fi

exec "$@"
