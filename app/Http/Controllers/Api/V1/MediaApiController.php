<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ListingMedia;
use App\Models\MarketplaceItem;
use App\Rules\TrustedImageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MediaApiController extends Controller
{
    public function store(Request $request, MarketplaceItem $marketplaceItem): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || $marketplaceItem->owner_id === $request->user()->id, 403);

        $attributes = $request->validate([
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:url'],
            'url' => ['nullable', 'max:800', 'required_without:file', new TrustedImageUrl()],
            'type' => ['nullable', Rule::in(['image', 'document'])],
            'alt_text' => ['nullable', 'string', 'max:160'],
        ]);

        $path = $attributes['url'] ?? null;
        $url = $attributes['url'] ?? null;
        $disk = 'public';

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('listing-media', 'public');
            $url = Storage::disk('public')->url($path);
        }

        $media = ListingMedia::create([
            'marketplace_item_id' => $marketplaceItem->id,
            'type' => $attributes['type'] ?? 'image',
            'disk' => $disk,
            'path' => $path,
            'url' => $url,
            'alt_text' => $attributes['alt_text'] ?? $marketplaceItem->title,
            'moderation_status' => $request->user()->isAdmin() ? 'approved' : 'pending',
            'metadata' => [
                'uploaded_by' => $request->user()->id,
                'source' => $request->hasFile('file') ? 'upload' : 'url',
            ],
        ]);

        return response()->json(['data' => $media], 201);
    }
}
