<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\FulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderOperationsController extends Controller
{
    public function confirm(Request $request, Order $order, FulfillmentService $fulfillment): OrderResource
    {
        return new OrderResource($fulfillment->confirmOrder($order, $request->user()));
    }

    public function deliver(Request $request, Shipment $shipment, FulfillmentService $fulfillment): JsonResponse
    {
        $attributes = $request->validate([
            'proof_of_delivery' => ['nullable', 'string', 'max:700'],
        ]);

        return response()->json([
            'data' => $fulfillment->markDelivered($shipment, $request->user(), $attributes['proof_of_delivery'] ?? null),
        ]);
    }

    public function dispute(Request $request, Order $order, FulfillmentService $fulfillment): JsonResponse
    {
        $attributes = $request->validate([
            'type' => ['required', Rule::in(['damage', 'late_return', 'no_show', 'quality', 'payment', 'delivery', 'other'])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'summary' => ['required', 'string', 'max:1600'],
            'evidence' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $fulfillment->openDispute($order, $request->user(), $attributes),
        ], 201);
    }

    public function refund(Request $request, Order $order, FulfillmentService $fulfillment): JsonResponse
    {
        $attributes = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        return response()->json([
            'data' => $fulfillment->requestRefund($order, $request->user(), (float) $attributes['amount'], $attributes['reason']),
        ], 201);
    }
}
