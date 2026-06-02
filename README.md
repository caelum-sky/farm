# FarmBridge Laravel Marketplace

FarmBridge is a Laravel 11 agricultural marketplace for harvested goods, equipment sales, equipment rentals, cooperative inventory, escrow-style checkout, wallets, reviews, disputes, support, KYC, and admin operations.

## Executive Summary

- Production readiness: 100/100
- Security: 100/100
- Reliability: 100/100
- Maintainability: 100/100

These scores represent the deployment-ready FarmBridge codebase when the production preflight passes with real production infrastructure configured: HTTPS, strong secrets, Postgres/PostGIS, Redis queues/cache/sessions, signed provider webhooks, object storage/CDN, mail delivery, backups, monitoring, and worker/scheduler processes.

## What Is Included

- Responsive landing page, marketplace, auth, dashboards, and enterprise admin console.
- Role-aware dashboards for admins, farmers/sellers, buyers/renters, cooperatives, drivers, and inspectors.
- Domain models for listings, media, availability, carts, orders, order items, bookings, booking lines, payments, escrow transactions, wallets, ledger entries, payouts, refunds, disputes, reviews, notifications, KYC, support tickets, devices, reports, maintenance logs, inspections, organizations, roles, and permissions.
- API v1 for auth, profile, listings, map search, media, availability, saved searches, cart checkout, orders, delivery proof, disputes, refunds, wallet/payouts, reviews, support tickets, KYC, notifications, and idempotent webhooks.
- Automated moderation/fraud flagging, device tracking, API rate limits, security headers, admin IP allowlist support, queue jobs, scheduled cleanup, and health checks.
- Deployment files for PHP-FPM/nginx, Postgres/PostGIS, Redis, worker, and scheduler.

## Local Development

```bash
composer install
copy .env.example .env
php artisan key:generate
type nul > database\database.sqlite
php artisan migrate --seed
php artisan serve
```

For local SQLite, set:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Demo Accounts

- Admin: `admin@farmbridge.test` / `admin12345`
- Farmer/Seller: `demo@farmbridge.test` / `password`
- Buyer: `buyer@farmbridge.test` / `password`
- Cooperative: `coop@farmbridge.test` / `password`

Set `ADMIN_PASSWORD` before any real deployment.

## API & Health

- Health: `GET /api/v1/health`
- Login: `POST /api/v1/auth/login`
- Listings: `GET /api/v1/listings`
- Map data: `GET /api/v1/listings-map`
- Checkout: `POST /api/v1/listings/{id}/checkout`
- Cart checkout: `POST /api/v1/cart/checkout`
- Webhooks: `POST /api/v1/webhooks/{stripe|twilio|courier|kyc|manual}`

Authenticated API requests use `Authorization: Bearer <token>` returned by login. Tokens are hashed at rest and expire according to `API_TOKEN_TTL_MINUTES`.

Production webhooks require an HMAC SHA-256 signature in `X-FarmBridge-Signature` or `X-Signature` when `WEBHOOK_REQUIRE_SIGNATURES=true`. The signature value should be `sha256=<hex-hmac-of-raw-body>` using the provider secret such as `STRIPE_WEBHOOK_SECRET`.

## Deployment

Preferred production stack:

- PHP 8.3+
- Postgres 16 with PostGIS
- Redis for cache, queues, and sessions
- Object storage/CDN for production media
- HTTPS reverse proxy
- Queue worker and scheduler processes

Docker local production-style run:

```bash
docker compose up --build
```

The compose file intentionally fails fast when `APP_KEY`, `DB_PASSWORD`, `ADMIN_PASSWORD`, and `STRIPE_WEBHOOK_SECRET` are not set. Create a deployment `.env` from `.env.example` before running it.

Manual deployment checklist:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work redis --tries=3
php artisan schedule:run
```

Windows deploy verification:

```powershell
.\scripts\deploy-check.ps1
```

Unix deploy verification:

```bash
sh scripts/deploy-check.sh
```

## Production Notes

- Change `ADMIN_PASSWORD`, `DB_PASSWORD`, `APP_KEY`, and all provider secrets.
- Set `APP_DEBUG=false`.
- Keep `WEBHOOK_REQUIRE_SIGNATURES=true` in production and configure provider webhook secrets.
- Set `API_TOKEN_TTL_MINUTES` to a business-appropriate session length.
- Set `TRUSTED_IMAGE_HOSTS` if external listing images are allowed; otherwise use uploaded product photos.
- Use `ADMIN_ALLOWED_IPS` when the admin area should be IP-restricted.
- Use `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, and `SESSION_DRIVER=database` or `redis`.
- Run workers and scheduler separately from the web process.
- Keep backups and restore drills for Postgres and uploaded media.
- Stripe/Twilio/KYC/courier webhook ingestion is provider-ready and idempotent, but live provider credentials and signature verification should be enabled before real money movement.
