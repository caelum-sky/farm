Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

php -v
composer validate --no-check-publish
composer install --no-interaction --prefer-dist
composer audit --locked --ignore-unreachable
php artisan key:generate --show | Out-Null
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:fresh --seed --force
composer test
php artisan route:list --path=api/v1
if ($env:APP_ENV -eq "production") {
    php artisan farmbridge:production-check
}
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear

Write-Host "FarmBridge deploy check passed."
