<?php

namespace App\Jobs;

use App\Models\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAppNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $notificationId)
    {
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(): void
    {
        AppNotification::query()
            ->whereKey($this->notificationId)
            ->where('status', 'pending')
            ->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
    }
}
