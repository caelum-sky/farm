<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::query()
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 15))));

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'latest_message' => ['required', 'string', 'max:2000'],
            'ticketable_type' => ['nullable', 'string', 'max:120'],
            'ticketable_id' => ['nullable', 'integer'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'ticketable_type' => $attributes['ticketable_type'] ?? null,
            'ticketable_id' => $attributes['ticketable_id'] ?? null,
            'subject' => $attributes['subject'],
            'priority' => $attributes['priority'] ?? 'normal',
            'latest_message' => $attributes['latest_message'],
            'status' => 'open',
        ]);

        return response()->json(['data' => $ticket], 201);
    }
}
