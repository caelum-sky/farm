<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'invoice_number' => $this->invoice_number,
            'type' => $this->type,
            'status' => $this->status,
            'subtotal_amount' => $this->subtotal_amount,
            'tax_amount' => $this->tax_amount,
            'platform_fee_amount' => $this->platform_fee_amount,
            'deposit_amount' => $this->deposit_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'invoice_issued_at' => optional($this->invoice_issued_at)->toISOString(),
            'proof_of_delivery_at' => optional($this->proof_of_delivery_at)->toISOString(),
            'refund_deadline_at' => optional($this->refund_deadline_at)->toISOString(),
            'buyer' => new UserResource($this->whenLoaded('buyer')),
            'seller' => new UserResource($this->whenLoaded('seller')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'listing_id' => $item->marketplace_item_id,
                'title' => $item->title,
                'item_type' => $item->item_type,
                'quantity' => $item->quantity,
                'unit_amount' => $item->unit_amount,
                'line_total_amount' => $item->line_total_amount,
                'start_date' => optional($item->start_date)->toDateString(),
                'end_date' => optional($item->end_date)->toDateString(),
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'provider' => $payment->provider,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'authorized_at' => optional($payment->authorized_at)->toISOString(),
                'captured_at' => optional($payment->captured_at)->toISOString(),
            ])),
            'escrow' => $this->whenLoaded('escrowTransactions', fn () => $this->escrowTransactions->map(fn ($escrow): array => [
                'id' => $escrow->id,
                'status' => $escrow->status,
                'amount' => $escrow->amount,
                'held_at' => optional($escrow->held_at)->toISOString(),
                'released_at' => optional($escrow->released_at)->toISOString(),
            ])),
            'bookings' => $this->whenLoaded('bookings', fn () => $this->bookings->map(fn ($booking): array => [
                'id' => $booking->id,
                'status' => $booking->status,
                'listing_id' => $booking->marketplace_item_id,
                'starts_at' => optional($booking->starts_at)->toISOString(),
                'ends_at' => optional($booking->ends_at)->toISOString(),
                'deposit_amount' => $booking->deposit_amount,
            ])),
            'shipments' => $this->whenLoaded('shipments', fn () => $this->shipments->map(fn ($shipment): array => [
                'id' => $shipment->id,
                'status' => $shipment->status,
                'delivery_method' => $shipment->delivery_method,
                'pickup_address' => $shipment->pickup_address,
                'dropoff_address' => $shipment->dropoff_address,
                'delivered_at' => optional($shipment->delivered_at)->toISOString(),
            ])),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
