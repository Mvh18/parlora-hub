#!/bin/sh
# Entry point of the development `app` service. On a fresh clone the named
# volumes for vendor/ and node_modules/ are empty: install both from the
# lockfiles, create .env and the app key, and migrate before starting the
# command (by default `composer dev`).
set -eu

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --no-progress
fi

if [ ! -f node_modules/.modules.yaml ]; then
    pnpm install --frozen-lockfile
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --ansi
fi

if [ "${APP_MIGRATE_ON_START:-true}" = "true" ]; then
    php artisan migrate --force --ansi
fi

exec "$@"
