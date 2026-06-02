#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="$(php artisan key:generate --show --no-interaction)"
  echo "APP_KEY was not provided; generated an ephemeral key for this container."
fi

if [ -n "${APP_KEY:-}" ]; then
  php artisan config:clear --no-interaction || true
fi

if [ "${APP_ENV:-}" = "production" ] && [ "${FARMBRIDGE_SKIP_PRODUCTION_CHECK:-false}" != "true" ]; then
  php artisan farmbridge:production-check --no-interaction
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

php artisan storage:link --force --no-interaction || true
php artisan optimize --no-interaction || true

exec "$@"
