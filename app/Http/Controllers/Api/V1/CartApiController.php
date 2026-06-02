<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\MarketplaceItem;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->activeCart($request)->load('items.listing')]);
    }

    public function addItem(Request $request, MarketplaceItem $marketplaceItem, CheckoutService $checkout): JsonResponse
    {
        abort_unless($marketplaceItem->isPubliclyVisible(), 404);

        $attributes = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['nullable', 'date', Rule::requiredIf($marketplaceItem->isRental())],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', Rule::requiredIf($marketplaceItem->isRental())],
        ]);

        $quote = $checkout->quoteAmounts(
            $marketplaceItem,
            (float) $attributes['quantity'],
            $attributes['start_date'] ?? null,
            $attributes['end_date'] ?? null,
        );
        $cart = $this->activeCart($request);

        $item = CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'marketplace_item_id' => $marketplaceItem->id,
                'start_date' => $attributes['start_date'] ?? null,
                'end_date' => $attributes['end_date'] ?? null,
            ],
            [
                'quantity' => $attributes['quantity'],
                'quoted_unit_amount' => $marketplaceItem->isRental() ? $marketplaceItem->rent_rate : $marketplaceItem->price,
            ],
        );

        return response()->json([
            'data' => $cart->fresh('items.listing'),
            'quote' => $quote,
            'item_id' => $item->id,
        ], 201);
    }

    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_unless($cartItem->cart?->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return response()->json(['data' => $this->activeCart($request)->load('items.listing')]);
    }

    public function checkout(Request $request, CheckoutService $checkout)
    {
        $attributes = $request->validate([
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'message' => ['nullable', 'string', 'max:1200'],
            'delivery_method' => ['nullable', Rule::in(['pickup', 'delivery'])],
            'dropoff_address' => ['nullable', 'string', 'max:255'],
        ]);
        $cart = $this->activeCart($request)->load('items.listing');
        abort_if($cart->items->isEmpty(), 422, 'Cart is empty.');

        $orders = $cart->items->map(function (CartItem $item) use ($request, $checkout, $attributes) {
            return $checkout->createCheckout($request->user(), $item->listing, array_merge($attributes, [
                'quantity' => $item->quantity,
                'start_date' => optional($item->start_date)->toDateString(),
                'end_date' => optional($item->end_date)->toDateString(),
                'message' => $attributes['message'] ?? 'Cart checkout request.',
            ]));
        });

        $cart->update(['status' => 'converted']);

        return OrderResource::collection($orders)->response()->setStatusCode(201);
    }

    private function activeCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $request->user()->id, 'status' => 'active'],
            ['expires_at' => now()->addDays(14)],
        );
    }
}
