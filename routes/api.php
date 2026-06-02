<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\CartApiController;
use App\Http\Controllers\Api\V1\CheckoutApiController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ListingApiController;
use App\Http\Controllers\Api\V1\KycApiController;
use App\Http\Controllers\Api\V1\MediaApiController;
use App\Http\Controllers\Api\V1\NotificationApiController;
use App\Http\Controllers\Api\V1\OrderOperationsController;
use App\Http\Controllers\Api\V1\ProfileApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\ReviewApiController;
use App\Http\Controllers\Api\V1\SupportTicketApiController;
use App\Http\Controllers\Api\V1\WalletApiController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->name('api.v1.auth.login');
    Route::post('/webhooks/{provider}', [WebhookController::class, 'store'])->name('api.v1.webhooks.store');

    Route::get('/listings', [ListingApiController::class, 'index'])->name('api.v1.listings.index');
    Route::get('/listings-map', [ListingApiController::class, 'map'])->name('api.v1.listings.map');
    Route::get('/listings/{marketplaceItem}', [ListingApiController::class, 'show'])->name('api.v1.listings.show');
    Route::get('/listings/{marketplaceItem}/availability', [ListingApiController::class, 'availability'])->name('api.v1.listings.availability');

    Route::middleware('api.token')->group(function (): void {
        Route::get('/me', [AuthApiController::class, 'me'])->name('api.v1.me');
        Route::patch('/me', [ProfileApiController::class, 'update'])->name('api.v1.profile.update');
        Route::patch('/me/password', [ProfileApiController::class, 'password'])->name('api.v1.profile.password');
        Route::post('/me/kyc', [KycApiController::class, 'store'])->name('api.v1.kyc.store');
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])->name('api.v1.auth.logout');

        Route::post('/listings', [ListingApiController::class, 'store'])->name('api.v1.listings.store');
        Route::post('/listings/saved-searches', [ListingApiController::class, 'saveSearch'])->name('api.v1.saved-searches.store');
        Route::post('/listings/{marketplaceItem}/availability-blocks', [ListingApiController::class, 'blockAvailability'])->name('api.v1.listings.availability-blocks.store');
        Route::post('/listings/{marketplaceItem}/media', [MediaApiController::class, 'store'])->name('api.v1.listings.media.store');
        Route::post('/listings/{marketplaceItem}/checkout', [CheckoutApiController::class, 'store'])->name('api.v1.checkout.store');
        Route::post('/listings/{marketplaceItem}/reports', [ReportApiController::class, 'store'])->name('api.v1.reports.store');

        Route::get('/cart', [CartApiController::class, 'show'])->name('api.v1.cart.show');
        Route::post('/cart/items/{marketplaceItem}', [CartApiController::class, 'addItem'])->name('api.v1.cart.items.store');
        Route::delete('/cart/items/{cartItem}', [CartApiController::class, 'removeItem'])->name('api.v1.cart.items.destroy');
        Route::post('/cart/checkout', [CartApiController::class, 'checkout'])->name('api.v1.cart.checkout');

        Route::get('/orders', [CheckoutApiController::class, 'index'])->name('api.v1.orders.index');
        Route::get('/orders/{order}', [CheckoutApiController::class, 'show'])->name('api.v1.orders.show');
        Route::post('/orders/{order}/confirm', [OrderOperationsController::class, 'confirm'])->name('api.v1.orders.confirm');
        Route::post('/orders/{order}/disputes', [OrderOperationsController::class, 'dispute'])->name('api.v1.orders.disputes.store');
        Route::post('/orders/{order}/refunds', [OrderOperationsController::class, 'refund'])->name('api.v1.orders.refunds.store');
        Route::post('/shipments/{shipment}/deliver', [OrderOperationsController::class, 'deliver'])->name('api.v1.shipments.deliver');

        Route::get('/wallet', [WalletApiController::class, 'show'])->name('api.v1.wallet.show');
        Route::post('/wallet/payouts', [WalletApiController::class, 'requestPayout'])->name('api.v1.wallet.payouts.store');

        Route::post('/reviews', [ReviewApiController::class, 'store'])->name('api.v1.reviews.store');

        Route::get('/support-tickets', [SupportTicketApiController::class, 'index'])->name('api.v1.support.index');
        Route::post('/support-tickets', [SupportTicketApiController::class, 'store'])->name('api.v1.support.store');

        Route::get('/notifications', [NotificationApiController::class, 'index'])->name('api.v1.notifications.index');
        Route::patch('/notifications/{notification}/read', [NotificationApiController::class, 'markRead'])->name('api.v1.notifications.read');
    });
});
