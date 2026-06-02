<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WebhookController extends Controller
{
    public function store(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, ['stripe', 'twilio', 'courier', 'kyc', 'manual'], true), 404);

        $this->verifySignature($request, $provider);

        $payload = $request->all();
        $eventId = $request->header('X-Event-Id')
            ?: (string) (Arr::get($payload, 'id') ?: hash('sha256', $provider.json_encode($payload)));
        $type = (string) (Arr::get($payload, 'type') ?: Arr::get($payload, 'event') ?: 'unknown');

        $event = WebhookEvent::firstOrCreate(
            ['provider' => $provider, 'event_id' => $eventId],
            [
                'type' => $type,
                'status' => 'pending',
                'payload' => $payload,
            ],
        );

        if (! $event->wasRecentlyCreated) {
            return response()->json([
                'message' => 'Duplicate webhook ignored.',
                'data' => ['id' => $event->id, 'status' => $event->status],
            ], 202);
        }

        ProcessWebhookEvent::dispatch($event->id);

        return response()->json([
            'message' => 'Webhook accepted.',
            'data' => ['id' => $event->id, 'status' => $event->status],
        ], 202);
    }

    private function verifySignature(Request $request, string $provider): void
    {
        $secrets = config('farmbridge_security.webhooks.secrets', []);
        $secret = $secrets[$provider] ?? null;
        $signature = $request->header('X-FarmBridge-Signature') ?: $request->header('X-Signature');
        $requiresSignature = (bool) config('farmbridge_security.webhooks.require_signatures', app()->environment('production'));

        if (! $secret) {
            abort_if($requiresSignature, 503, 'Webhook signing secret is not configured.');

            return;
        }

        abort_unless(is_string($signature) && $signature !== '', 401, 'Missing webhook signature.');

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        $normalizedSignature = str_starts_with($signature, 'sha256=') ? $signature : 'sha256='.$signature;

        abort_unless(hash_equals($expected, $normalizedSignature), 401, 'Invalid webhook signature.');
    }
}
