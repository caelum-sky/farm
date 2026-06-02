<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        return response()->json($notifications);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $notification->update(['read_at' => now(), 'status' => 'read']);

        return response()->json(['data' => $notification->fresh()]);
    }
}
