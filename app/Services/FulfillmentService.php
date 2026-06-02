<?php

namespace App\Services;

use App\Jobs\ReleaseEscrow;
use App\Models\AppNotification;
use App\Models\Dispute;
use App\Models\EscrowTransaction;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    public function confirmOrder(Order $order, User $actor): Order
    {
        $this->ensureParticipant($order, $actor);

        return DB::transaction(function () use ($order): Order {
            $order->update([
                'status' => Order::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'version' => (int) $order->version + 1,
            ]);

            $order->sourceInquiry?->update([
                'status' => Inquiry::STATUS_APPROVED,
                'seller_accepted_at' => now(),
            ]);

            $order->bookings()->update(['status' => 'confirmed']);

            return $order->fresh(['items', 'payments', 'bookings', 'shipments', 'escrowTransactions']);
        });
    }

    public function markDelivered(Shipment $shipment, User $actor, ?string $proof = null): Shipment
    {
        $order = $shipment->order;
        abort_unless($order, 422, 'Shipment is not attached to an order.');
        $this->ensureParticipant($order, $actor);

        return DB::transaction(function () use ($shipment, $order, $proof): Shipment {
            $shipment->update([
                'status' => 'delivered',
                'proof_of_delivery' => $proof ?: $shipment->proof_of_delivery,
                'delivered_at' => now(),
            ]);

            $order->update([
                'status' => Order::STATUS_FULFILLED,
                'fulfilled_at' => now(),
                'proof_of_delivery_at' => now(),
                'version' => (int) $order->version + 1,
            ]);

            $order->sourceInquiry?->update([
                'status' => Inquiry::STATUS_FULFILLED,
                'fulfilled_at' => now(),
                'payment_status' => 'captured',
            ]);

            $order->escrowTransactions()
                ->where('status', 'held')
                ->each(fn (EscrowTransaction $escrow) => ReleaseEscrow::dispatch($escrow->id));

            return $shipment->fresh();
        });
    }

    public function openDispute(Order $order, User $actor, array $attributes): Dispute
    {
        $this->ensureParticipant($order, $actor);

        return DB::transaction(function () use ($order, $actor, $attributes): Dispute {
            $inquiry = $order->sourceInquiry;
            abort_unless($inquiry, 422, 'Order has no source inquiry for dispute tracking.');

            $dispute = Dispute::create([
                'inquiry_id' => $inquiry->id,
                'opened_by' => $actor->id,
                'type' => $attributes['type'],
                'priority' => $attributes['priority'] ?? 'normal',
                'summary' => $attributes['summary'],
                'evidence' => $attributes['evidence'] ?? null,
                'status' => 'open',
            ]);

            $order->update(['status' => Order::STATUS_DISPUTED, 'version' => (int) $order->version + 1]);
            $inquiry->update(['status' => Inquiry::STATUS_DISPUTED, 'dispute_status' => 'open']);

            $this->notifyAdmins('Dispute opened', 'Order '.$order->order_number.' has a new dispute.', [
                'order_id' => $order->id,
                'dispute_id' => $dispute->id,
            ]);

            return $dispute;
        });
    }

    public function requestRefund(Order $order, User $actor, float $amount, string $reason): Refund
    {
        $this->ensureParticipant($order, $actor);

        if ($amount <= 0 || $amount > (float) $order->total_amount) {
            throw ValidationException::withMessages(['amount' => 'Refund amount must be within the order total.']);
        }

        return DB::transaction(function () use ($order, $actor, $amount, $reason): Refund {
            $payment = $order->payments()->latest()->first();

            $refund = Refund::firstOrCreate(
                ['idempotency_key' => 'refund:'.$order->id.':'.hash('sha256', $amount.'|'.$reason)],
                [
                    'payment_id' => $payment?->id,
                    'order_id' => $order->id,
                    'inquiry_id' => $order->source_inquiry_id,
                    'requested_by' => $actor->id,
                    'status' => 'pending',
                    'amount' => $amount,
                    'currency' => $order->currency,
                    'reason' => $reason,
                ],
            );

            $order->update([
                'status' => Order::STATUS_REFUNDED,
                'refund_deadline_at' => now()->addDays(7),
                'version' => (int) $order->version + 1,
            ]);

            $order->escrowTransactions()->where('status', 'held')->update([
                'status' => 'refund_pending',
                'refunded_at' => now(),
            ]);

            return $refund;
        });
    }

    private function ensureParticipant(Order $order, User $actor): void
    {
        abort_unless(
            $actor->isAdmin() || $order->buyer_id === $actor->id || $order->seller_id === $actor->id,
            403,
        );
    }

    private function notifyAdmins(string $subject, string $body, array $data): void
    {
        User::query()
            ->where('role', User::ROLE_ADMIN)
            ->each(fn (User $admin) => AppNotification::create([
                'user_id' => $admin->id,
                'type' => 'ops.alert',
                'status' => 'pending',
                'subject' => $subject,
                'body' => $body,
                'data' => $data + ['reference' => Str::uuid()->toString()],
            ]));
    }
}
