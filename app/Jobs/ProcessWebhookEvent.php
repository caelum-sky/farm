<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessWebhookEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $webhookEventId)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $event = WebhookEvent::findOrFail($this->webhookEventId);

        try {
            DB::transaction(function () use ($event): void {
                $event->increment('attempts');
                $event->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'last_error' => null,
                ]);

                User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->each(function (User $admin) use ($event): void {
                        AppNotification::create([
                            'user_id' => $admin->id,
                            'type' => 'webhook.processed',
                            'status' => 'pending',
                            'subject' => ucfirst($event->provider).' webhook processed',
                            'body' => $event->type.' was accepted with event id '.$event->event_id.'.',
                            'data' => ['webhook_event_id' => $event->id],
                        ]);
                    });
            });
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
