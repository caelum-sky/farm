<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\ListingFlagged;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\MarketplaceItem;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    public function store(Request $request, MarketplaceItem $marketplaceItem): JsonResponse
    {
        abort_unless($marketplaceItem->isPubliclyVisible() || $request->user()->isAdmin(), 404);

        $attributes = $request->validate([
            'reason' => ['required', 'string', 'max:80'],
            'details' => ['nullable', 'string', 'max:1200'],
        ]);

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $marketplaceItem->getMorphClass(),
            'reportable_id' => $marketplaceItem->id,
            'reason' => $attributes['reason'],
            'details' => $attributes['details'] ?? null,
            'status' => 'open',
        ]);

        $marketplaceItem->increment('report_count');
        $marketplaceItem->refresh();

        if ($marketplaceItem->report_count > 3) {
            $reason = 'Reported by more than 3 users.';
            $marketplaceItem->update([
                'flagged_reason' => $reason,
                'moderation_status' => 'pending',
                'lifecycle_status' => MarketplaceItem::LIFECYCLE_REVIEW,
                'is_available' => false,
            ]);

            event(new ListingFlagged($marketplaceItem, $reason));
        }

        User::query()
            ->where('role', User::ROLE_ADMIN)
            ->each(fn (User $admin) => AppNotification::create([
                'user_id' => $admin->id,
                'type' => 'listing.reported',
                'status' => 'pending',
                'subject' => 'Listing report submitted',
                'body' => $marketplaceItem->title.' was reported for '.$attributes['reason'].'.',
                'data' => ['listing_id' => $marketplaceItem->id, 'report_id' => $report->id],
            ]));

        return response()->json(['data' => $report], 201);
    }
}
