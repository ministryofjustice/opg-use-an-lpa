#!/usr/bin/env bash
#
# Usage: phpcs.sh front|api file1 file2 ...

MODE=$1
shift

if [ $MODE = 'front' ]; then
    FILES=""
    for path in "$@"; do
        FILES+="${path#service-front/app/} "
    done

    docker compose run --rm -i --no-deps actor-app php /app/vendor/bin/phpcs $FILES
elif [ $MODE = 'api' ]; then
    FILES=""
    for path in "$@"; do
        FILES+="${path#service-api/app/} "
    done

    docker compose run --rm -i --no-deps api-app php /app/vendor/bin/phpcs $FILES
else
    exit 1
fi
