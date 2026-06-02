<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->isAdmin()
            || $inquiry->user_id === $user->id
            || $inquiry->marketplaceItem?->owner_id === $user->id;
    }

    public function update(User $user, Inquiry $inquiry): bool
    {
        return $user->isAdmin() || $inquiry->marketplaceItem?->owner_id === $user->id;
    }
}
