<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Models\Payout;
use App\Services\WalletLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletApiController extends Controller
{
    public function show(Request $request, WalletLedgerService $ledger): WalletResource
    {
        $wallet = $ledger->ensureWallet($request->user())
            ->load(['ledgerEntries' => fn ($query) => $query->latest()->take(25)]);

        return new WalletResource($wallet);
    }

    public function requestPayout(Request $request, WalletLedgerService $ledger): JsonResponse
    {
        $attributes = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'provider' => ['nullable', 'string', 'max:40'],
        ]);

        $payout = DB::transaction(function () use ($request, $ledger, $attributes): Payout {
            $wallet = $ledger->ensureWallet($request->user());

            if ((float) $wallet->available_balance < (float) $attributes['amount']) {
                abort(422, 'The wallet does not have enough available balance for this payout.');
            }

            $payout = Payout::create([
                'wallet_id' => $wallet->id,
                'user_id' => $request->user()->id,
                'amount' => $attributes['amount'],
                'currency' => $wallet->currency,
                'status' => 'pending',
                'provider' => $attributes['provider'] ?? 'manual',
                'scheduled_at' => now()->addDay(),
            ]);

            $ledger->debitAvailable($wallet, (float) $attributes['amount'], 'payout_pending', $payout, 'payout:'.$payout->id.':debit');

            return $payout;
        });

        return response()->json(['data' => $payout->fresh()], 201);
    }
}
