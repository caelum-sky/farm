<?php

namespace App\Providers;

use App\Models\Inquiry;
use App\Models\MarketplaceItem;
use App\Models\Order;
use App\Policies\InquiryPolicy;
use App\Policies\MarketplaceItemPolicy;
use App\Policies\OrderPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::policy(MarketplaceItem::class, MarketplaceItemPolicy::class);
        Gate::policy(Inquiry::class, InquiryPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        RateLimiter::for('api', function (Request $request) {
            $limit = match (true) {
                $request->is('api/v1/listings') && $request->isMethod('post') => 12,
                str_contains($request->path(), '/checkout') => 30,
                str_contains($request->path(), '/payouts') => 10,
                str_contains($request->path(), '/reports') => 20,
                default => $request->user()?->isAdmin() ? 240 : 90,
            };

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
