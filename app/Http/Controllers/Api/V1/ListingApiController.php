<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\ListingAvailabilityBlock;
use App\Models\MarketplaceItem;
use App\Models\SavedSearch;
use App\Models\SiteSetting;
use App\Rules\TrustedImageUrl;
use App\Services\GeoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingApiController extends Controller
{
    public function index(Request $request)
    {
        $items = MarketplaceItem::query()
            ->publiclyVisible()
            ->with(['owner', 'media'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->category))
            ->when($request->filled('transaction_type'), fn (Builder $query) => $query->where('transaction_type', $request->transaction_type))
            ->when($request->filled('region'), fn (Builder $query) => $query->where('location', 'like', '%'.$request->region.'%'))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $query->where(function (Builder $nested) use ($request): void {
                    $nested->where('title', 'like', '%'.$request->q.'%')
                        ->orWhere('description', 'like', '%'.$request->q.'%')
                        ->orWhere('location', 'like', '%'.$request->q.'%');
                });
            })
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 15))))
            ->withQueryString();

        return ListingResource::collection($items);
    }

    public function store(Request $request, GeoService $geo): JsonResponse|ListingResource
    {
        $user = $request->user();

        abort_unless($user->canCreateListings(), 403);

        $settings = SiteSetting::allSettings();

        if ($settings['maintenance_mode'] === '1' || $settings['marketplace_status'] === 'paused') {
            return response()->json(['message' => 'Marketplace publishing is temporarily unavailable.'], 503);
        }

        $attributes = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in([MarketplaceItem::CATEGORY_EQUIPMENT, MarketplaceItem::CATEGORY_GOODS])],
            'transaction_type' => ['required', Rule::in([MarketplaceItem::TRANSACTION_SALE, MarketplaceItem::TRANSACTION_RENT])],
            'price' => ['nullable', 'numeric', 'min:0', 'required_if:transaction_type,sale'],
            'rent_rate' => ['nullable', 'numeric', 'min:0', 'required_if:transaction_type,rent'],
            'unit' => ['required', 'string', 'max:40'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:160'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'harvest_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:1800'],
            'image_url' => ['nullable', 'max:500', new TrustedImageUrl()],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'min_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'max_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $requiresReview = $settings['listing_review_required'] === '1' && ! $user->canBypassModeration();
        $attributes['owner_id'] = $user->id;
        $attributes['image_url'] = $attributes['image_url'] ?? ($attributes['category'] === MarketplaceItem::CATEGORY_EQUIPMENT ? '/assets/equipment-tractor.png' : '/assets/produce-crates.png');
        $attributes['is_available'] = ! $requiresReview;
        $attributes['is_featured'] = false;
        $attributes['moderation_status'] = $requiresReview ? 'pending' : 'approved';
        $attributes['lifecycle_status'] = $requiresReview ? MarketplaceItem::LIFECYCLE_REVIEW : MarketplaceItem::LIFECYCLE_ACTIVE;
        $attributes['published_at'] = $requiresReview ? null : now();
        $attributes['geo_hash'] = $geo->bucket(
            isset($attributes['latitude']) ? (float) $attributes['latitude'] : null,
            isset($attributes['longitude']) ? (float) $attributes['longitude'] : null,
        );
        $attributes['last_inventory_sync_at'] = now();

        $listing = MarketplaceItem::create($attributes)->load('owner', 'media');

        return (new ListingResource($listing))->response()->setStatusCode(201);
    }

    public function show(Request $request, MarketplaceItem $marketplaceItem): ListingResource
    {
        $marketplaceItem->load(['owner', 'media'])
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');

        abort_unless(
            $marketplaceItem->isPubliclyVisible()
                || ($request->user() && ($request->user()->isAdmin() || $marketplaceItem->owner_id === $request->user()->id)),
            404,
        );

        return new ListingResource($marketplaceItem);
    }

    public function availability(MarketplaceItem $marketplaceItem): JsonResponse
    {
        abort_unless($marketplaceItem->isPubliclyVisible(), 404);

        return response()->json([
            'listing_id' => $marketplaceItem->id,
            'available_quantity' => $marketplaceItem->availableQuantity(),
            'blocks' => $marketplaceItem->availabilityBlocks()
                ->orderBy('starts_at')
                ->get(['id', 'type', 'starts_at', 'ends_at', 'reason']),
            'bookings' => $marketplaceItem->bookings()
                ->whereIn('status', ['requested', 'confirmed', 'active'])
                ->orderBy('starts_at')
                ->get(['id', 'status', 'quantity', 'starts_at', 'ends_at']),
        ]);
    }

    public function map(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'north' => ['nullable', 'numeric', 'between:-90,90'],
            'south' => ['nullable', 'numeric', 'between:-90,90'],
            'east' => ['nullable', 'numeric', 'between:-180,180'],
            'west' => ['nullable', 'numeric', 'between:-180,180'],
            'category' => ['nullable', Rule::in([MarketplaceItem::CATEGORY_EQUIPMENT, MarketplaceItem::CATEGORY_GOODS])],
        ]);

        $items = MarketplaceItem::query()
            ->publiclyVisible()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when(isset($attributes['north'], $attributes['south']), fn (Builder $query) => $query
                ->whereBetween('latitude', [(float) $attributes['south'], (float) $attributes['north']]))
            ->when(isset($attributes['east'], $attributes['west']), fn (Builder $query) => $query
                ->whereBetween('longitude', [(float) $attributes['west'], (float) $attributes['east']]))
            ->when(isset($attributes['category']), fn (Builder $query) => $query->where('category', $attributes['category']))
            ->latest()
            ->limit(250)
            ->get()
            ->map(fn (MarketplaceItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'transaction_type' => $item->transaction_type,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'geo_hash' => $item->geo_hash,
                'price_label' => $item->priceLabel(),
                'available_quantity' => $item->availableQuantity(),
                'listing_score' => $item->listing_score,
            ]);

        return response()->json(['data' => $items]);
    }

    public function saveSearch(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'filters' => ['required', 'array'],
            'notify' => ['nullable', 'boolean'],
        ]);

        $saved = SavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $attributes['name'],
            'filters' => $attributes['filters'],
            'notify' => (bool) ($attributes['notify'] ?? true),
        ]);

        return response()->json(['data' => $saved], 201);
    }

    public function blockAvailability(Request $request, MarketplaceItem $marketplaceItem): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || $marketplaceItem->owner_id === $request->user()->id, 403);

        $attributes = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'reason' => ['nullable', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'max:32'],
        ]);

        $block = ListingAvailabilityBlock::create([
            'marketplace_item_id' => $marketplaceItem->id,
            'type' => $attributes['type'] ?? 'blocked',
            'starts_at' => $attributes['starts_at'],
            'ends_at' => $attributes['ends_at'],
            'reason' => $attributes['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $block], 201);
    }
}
