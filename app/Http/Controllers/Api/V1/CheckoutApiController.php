<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\MarketplaceItem;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckoutApiController extends Controller
{
    public function store(Request $request, MarketplaceItem $marketplaceItem, CheckoutService $checkout)
    {
        $attributes = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['nullable', 'date', Rule::requiredIf($marketplaceItem->isRental())],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', Rule::requiredIf($marketplaceItem->isRental())],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'message' => ['nullable', 'string', 'max:1200'],
            'delivery_method' => ['nullable', Rule::in(['pickup', 'delivery'])],
            'dropoff_address' => ['nullable', 'string', 'max:255'],
        ]);

        $order = $checkout->createCheckout($request->user(), $marketplaceItem, $attributes);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::query()
            ->with(['buyer', 'seller', 'items', 'payments', 'escrowTransactions', 'bookings', 'shipments'])
            ->when(! $user->isAdmin(), function (Builder $query) use ($user): void {
                $query->where(function (Builder $visible) use ($user): void {
                    $visible->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 15))))
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($request->user()->can('view', $order), 403);

        $order->load(['buyer', 'seller', 'items', 'payments', 'escrowTransactions', 'bookings', 'shipments']);

        return new OrderResource($order);
    }
}
