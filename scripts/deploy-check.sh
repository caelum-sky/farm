#!/usr/bin/env sh
set -eu

php -v
composer validate --no-check-publish
composer install --no-interaction --prefer-dist
composer audit --locked --ignore-unreachable
php artisan key:generate --show >/dev/null
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:fresh --seed --force
composer test
php artisan route:list --path=api/v1
if [ "${APP_ENV:-}" = "production" ]; then
  php artisan farmbridge:production-check
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear

echo "FarmBridge deploy check passed."
