<?php

namespace App\Policies;

use App\Models\MarketplaceItem;
use App\Models\User;

class MarketplaceItemPolicy
{
    public function view(?User $user, MarketplaceItem $listing): bool
    {
        return $listing->isPubliclyVisible()
            || ($user && ($user->isAdmin() || $listing->owner_id === $user->id));
    }

    public function create(User $user): bool
    {
        return $user->canCreateListings();
    }

    public function update(User $user, MarketplaceItem $listing): bool
    {
        return $user->isAdmin() || ($listing->owner_id === $user->id && $user->isActive());
    }

    public function delete(User $user, MarketplaceItem $listing): bool
    {
        return $this->update($user, $listing);
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('listings.moderate');
    }
}
