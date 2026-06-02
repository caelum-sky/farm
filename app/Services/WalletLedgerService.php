<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletLedgerService
{
    public function ensureWallet(User $user, string $currency = 'USD'): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency' => $currency],
            ['status' => 'active'],
        );
    }

    public function creditPending(Wallet $wallet, float $amount, string $type, ?Model $ledgerable = null, ?string $idempotencyKey = null, array $metadata = []): WalletLedgerEntry
    {
        return $this->post($wallet, 'pending_balance', 'credit', $type, $amount, $ledgerable, $idempotencyKey, $metadata);
    }

    public function creditAvailable(Wallet $wallet, float $amount, string $type, ?Model $ledgerable = null, ?string $idempotencyKey = null, array $metadata = []): WalletLedgerEntry
    {
        return $this->post($wallet, 'available_balance', 'credit', $type, $amount, $ledgerable, $idempotencyKey, $metadata);
    }

    public function debitAvailable(Wallet $wallet, float $amount, string $type, ?Model $ledgerable = null, ?string $idempotencyKey = null, array $metadata = []): WalletLedgerEntry
    {
        if ((float) $wallet->available_balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'The wallet does not have enough available balance for this action.',
            ]);
        }

        return $this->post($wallet, 'available_balance', 'debit', $type, $amount, $ledgerable, $idempotencyKey, $metadata);
    }

    public function movePendingToAvailable(Wallet $wallet, float $amount, string $type, ?Model $ledgerable = null, ?string $idempotencyKey = null, array $metadata = []): WalletLedgerEntry
    {
        if ((float) $wallet->pending_balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'The wallet does not have enough pending balance to release.',
            ]);
        }

        return DB::transaction(function () use ($wallet, $amount, $type, $ledgerable, $idempotencyKey, $metadata): WalletLedgerEntry {
            if ($idempotencyKey) {
                $existing = WalletLedgerEntry::where('idempotency_key', $idempotencyKey)->first();

                if ($existing) {
                    return $existing;
                }
            }

            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $locked->pending_balance = round((float) $locked->pending_balance - $amount, 2);
            $locked->available_balance = round((float) $locked->available_balance + $amount, 2);
            $locked->save();

            return WalletLedgerEntry::create([
                'wallet_id' => $locked->id,
                'ledgerable_type' => $ledgerable?->getMorphClass(),
                'ledgerable_id' => $ledgerable?->getKey(),
                'direction' => 'credit',
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $locked->available_balance,
                'idempotency_key' => $idempotencyKey,
                'metadata' => array_merge($metadata, ['from' => 'pending_balance', 'to' => 'available_balance']),
            ]);
        });
    }

    private function post(Wallet $wallet, string $balanceColumn, string $direction, string $type, float $amount, ?Model $ledgerable, ?string $idempotencyKey, array $metadata): WalletLedgerEntry
    {
        return DB::transaction(function () use ($wallet, $balanceColumn, $direction, $type, $amount, $ledgerable, $idempotencyKey, $metadata): WalletLedgerEntry {
            if ($idempotencyKey) {
                $existing = WalletLedgerEntry::where('idempotency_key', $idempotencyKey)->first();

                if ($existing) {
                    return $existing;
                }
            }

            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $delta = $direction === 'credit' ? $amount : -$amount;
            $locked->{$balanceColumn} = round((float) $locked->{$balanceColumn} + $delta, 2);
            $locked->save();

            return WalletLedgerEntry::create([
                'wallet_id' => $locked->id,
                'ledgerable_type' => $ledgerable?->getMorphClass(),
                'ledgerable_id' => $ledgerable?->getKey(),
                'direction' => $direction,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $locked->{$balanceColumn},
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata ?: null,
            ]);
        });
    }
}
