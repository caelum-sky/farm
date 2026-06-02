<?php

namespace App\Events;

use App\Models\MarketplaceItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListingFlagged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public MarketplaceItem $listing, public string $reason)
    {
    }
}
