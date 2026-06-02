<?php

use App\Jobs\ExpireBookingRequests;
use App\Services\ModerationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('farmbridge:welcome', function (): void {
    $this->comment('FarmBridge is ready for farmers, equipment owners, and produce buyers.');
})->purpose('Show a FarmBridge welcome message');

Artisan::command('farmbridge:moderate-listings', function (ModerationService $moderation): void {
    $flagged = $moderation->flagListingsNeedingReview();
    $this->info($flagged.' listing moderation updates applied.');
})->purpose('Run automated listing moderation and fraud flagging');

Artisan::command('farmbridge:expire-bookings', function (): void {
    ExpireBookingRequests::dispatch();
    $this->info('Expired booking cleanup job dispatched.');
})->purpose('Expire stale checkout and booking holds');

Artisan::command('farmbridge:production-check', function (): int {
    $failures = [];
    $warnings = [];

    $require = function (bool $condition, string $message) use (&$failures): void {
        if (! $condition) {
            $failures[] = $message;
        }
    };

    $warn = function (bool $condition, string $message) use (&$warnings): void {
        if (! $condition) {
            $warnings[] = $message;
        }
    };

    $appUrl = (string) config('app.url');
    $adminPassword = (string) env('ADMIN_PASSWORD', '');
    $dbPassword = (string) env('DB_PASSWORD', '');
    $corsOrigins = config('cors.allowed_origins', []);
    $webhookSecrets = config('farmbridge_security.webhooks.secrets', []);

    $require(app()->environment('production'), 'APP_ENV must be production.');
    $require(config('app.debug') === false, 'APP_DEBUG must be false.');
    $require((bool) config('app.key'), 'APP_KEY must be set.');
    $require(Str::startsWith($appUrl, 'https://'), 'APP_URL must use HTTPS.');
    $require(config('database.default') !== 'sqlite', 'DB_CONNECTION must not be sqlite in production.');
    $require($dbPassword !== '' && $dbPassword !== 'farmbridge_secret' && $dbPassword !== 'change-this-database-password', 'DB_PASSWORD must be a real secret.');
    $require(! in_array($adminPassword, ['', 'admin12345', 'password', 'change-this-before-deploy'], true), 'ADMIN_PASSWORD must be a real secret.');
    $require((bool) config('session.secure'), 'SESSION_SECURE_COOKIE must be true.');
    $require(config('session.driver') !== 'file' && config('session.driver') !== 'array', 'SESSION_DRIVER must be database or redis.');
    $require(config('cache.default') !== 'file' && config('cache.default') !== 'array', 'CACHE_STORE must be redis or database.');
    $require(config('queue.default') !== 'sync', 'QUEUE_CONNECTION must not be sync.');
    $require((bool) data_get(config('queue.connections.'.config('queue.default')), 'after_commit', false), 'QUEUE_AFTER_COMMIT must be true.');
    $require((bool) config('farmbridge_security.webhooks.require_signatures'), 'WEBHOOK_REQUIRE_SIGNATURES must be true.');
    $require((string) ($webhookSecrets['stripe'] ?? '') !== '', 'STRIPE_WEBHOOK_SECRET must be set.');
    $require(! empty($corsOrigins) && ! in_array('*', $corsOrigins, true), 'CORS_ALLOWED_ORIGINS must be explicit and cannot be *.');
    $require((int) config('farmbridge_security.api_token_ttl_minutes') > 0, 'API_TOKEN_TTL_MINUTES must be greater than zero.');

    $warn(config('mail.default') !== 'log' && config('mail.default') !== 'array', 'MAIL_MAILER should use a real provider for verification and password reset.');
    $warn(config('filesystems.default') !== 'local', 'FILESYSTEM_DISK should use S3-compatible object storage for production media.');
    $warn((string) env('ADMIN_ALLOWED_IPS', '') !== '', 'ADMIN_ALLOWED_IPS is empty; admin area is not IP-restricted.');
    $warn(! empty(config('farmbridge_security.trusted_image_hosts')), 'TRUSTED_IMAGE_HOSTS is empty; external listing images will be blocked.');

    foreach ($warnings as $warning) {
        $this->warn($warning);
    }

    if ($failures) {
        foreach ($failures as $failure) {
            $this->error($failure);
        }

        return Command::FAILURE;
    }

    $this->info('FarmBridge production preflight passed.');

    return Command::SUCCESS;
})->purpose('Fail deployment when required production security settings are missing');

Schedule::command('farmbridge:moderate-listings')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('farmbridge:expire-bookings')->everyFifteenMinutes()->withoutOverlapping();
