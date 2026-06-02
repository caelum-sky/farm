<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'inquiry_id' => ['required', 'exists:inquiries,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:1200'],
        ]);

        $inquiry = Inquiry::with('marketplaceItem.owner')->findOrFail($attributes['inquiry_id']);
        abort_unless($request->user()->can('view', $inquiry), 403);
        abort_unless($inquiry->status === Inquiry::STATUS_FULFILLED, 422, 'Reviews are only available after fulfillment.');

        $owner = $inquiry->marketplaceItem?->owner;
        $revieweeId = $request->user()->id === $inquiry->user_id ? $owner?->id : $inquiry->user_id;

        abort_unless($revieweeId, 422, 'Review target is unavailable.');

        $review = Review::updateOrCreate(
            ['inquiry_id' => $inquiry->id, 'reviewer_id' => $request->user()->id],
            [
                'reviewee_id' => $revieweeId,
                'marketplace_item_id' => $inquiry->marketplace_item_id,
                'rating' => $attributes['rating'],
                'body' => $attributes['body'] ?? null,
                'status' => 'published',
            ],
        );

        return response()->json(['data' => $review], 201);
    }
}
