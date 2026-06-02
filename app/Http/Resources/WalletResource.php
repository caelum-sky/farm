<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'available_balance' => $this->available_balance,
            'pending_balance' => $this->pending_balance,
            'held_balance' => $this->held_balance,
            'status' => $this->status,
            'ledger' => $this->whenLoaded('ledgerEntries', fn () => $this->ledgerEntries->map(fn ($entry): array => [
                'id' => $entry->id,
                'direction' => $entry->direction,
                'type' => $entry->type,
                'amount' => $entry->amount,
                'balance_after' => $entry->balance_after,
                'status' => $entry->status,
                'created_at' => optional($entry->created_at)->toISOString(),
            ])),
        ];
    }
}
