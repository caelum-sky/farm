<?php

namespace App\Jobs;

use App\Models\Inquiry;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireBookingRequests implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        Inquiry::query()
            ->where('status', Inquiry::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (Inquiry $inquiry): void {
                $inquiry->update([
                    'status' => Inquiry::STATUS_EXPIRED,
                    'payment_status' => 'expired',
                    'escrow_status' => 'expired',
                ]);

                $inquiry->order?->update(['status' => Order::STATUS_CANCELLED, 'cancelled_at' => now()]);
                $inquiry->bookings()->update(['status' => 'cancelled']);
            });
    }
}
