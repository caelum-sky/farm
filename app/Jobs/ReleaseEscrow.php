<?php

namespace App\Jobs;

use App\Events\EscrowReleased;
use App\Models\EscrowTransaction;
use App\Models\Order;
use App\Services\WalletLedgerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ReleaseEscrow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(public int $escrowId)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(WalletLedgerService $ledger): void
    {
        DB::transaction(function () use ($ledger): void {
            $escrow = EscrowTransaction::query()->whereKey($this->escrowId)->lockForUpdate()->firstOrFail();

            if ($escrow->status !== 'held') {
                return;
            }

            $seller = $escrow->seller;

            if ($seller) {
                $wallet = $ledger->ensureWallet($seller, $escrow->currency);
                $netAmount = $escrow->order
                    ? max(0, (float) $escrow->order->subtotal_amount - (float) $escrow->order->platform_fee_amount)
                    : (float) $escrow->amount;
                $ledger->movePendingToAvailable($wallet, $netAmount, 'escrow_released', $escrow, 'escrow:'.$escrow->id.':released');
            }

            $escrow->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            $escrow->order?->update([
                'status' => Order::STATUS_FULFILLED,
                'fulfilled_at' => now(),
            ]);

            $escrow->inquiry?->update([
                'status' => 'fulfilled',
                'payment_status' => 'captured',
                'escrow_status' => 'released',
                'fulfilled_at' => now(),
            ]);

            event(new EscrowReleased($escrow));
        });
    }
}
