<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'ticketable_type',
        'ticketable_id',
        'subject',
        'status',
        'priority',
        'latest_message',
        'assigned_to',
    ];

    public function ticketable(): MorphTo
    {
        return $this->morphTo();
    }
}
