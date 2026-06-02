<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\ListingMedia;
use App\Models\MarketplaceItem;
use App\Models\SiteSetting;
use App\Rules\TrustedImageUrl;
use App\Services\CheckoutService;
use App\Services\GeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MarketplaceController extends Controller
{
    public function landing(): View
    {
        $siteSettings = SiteSetting::defaults();

        try {
            $featured = MarketplaceItem::query()
                ->publiclyVisible()
                ->featured()
                ->with('owner')
                ->latest()
                ->take(6)
                ->get();

            $siteSettings = SiteSetting::publicSettings();
        } catch (Throwable) {
            $featured = collect();
        }

        if ($featured->isEmpty()) {
            $featured = $this->fallbackItems();
        }

        return view('landing', compact('featured', 'siteSettings'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::in([MarketplaceItem::CATEGORY_EQUIPMENT, MarketplaceItem::CATEGORY_GOODS])],
            'transaction_type' => ['nullable', Rule::in([MarketplaceItem::TRANSACTION_SALE, MarketplaceItem::TRANSACTION_RENT])],
            'location' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', Rule::in(['newest', 'price_low', 'price_high', 'availability'])],
        ]);

        $items = MarketplaceItem::query()
            ->publiclyVisible()
            ->with('owner')
            ->when(! empty($filters['category']), fn ($query) => $query->where('category', $filters['category']))
            ->when(! empty($filters['transaction_type']), fn ($query) => $query->where('transaction_type', $filters['transaction_type']))
            ->when(! empty($filters['location']), fn ($query) => $query->where('location', 'like', '%'.$filters['location'].'%'))
            ->when(! empty($filters['q']), function ($query) use ($filters) {
                $query->where(function ($nested) use ($filters) {
                    $nested->where('title', 'like', '%'.$filters['q'].'%')
                        ->orWhere('description', 'like', '%'.$filters['q'].'%');
                });
            })
            ->when(($filters['sort'] ?? 'newest') === 'price_low', fn ($query) => $query->orderByRaw('COALESCE(price, rent_rate, 0) asc'))
            ->when(($filters['sort'] ?? 'newest') === 'price_high', fn ($query) => $query->orderByRaw('COALESCE(price, rent_rate, 0) desc'))
            ->when(($filters['sort'] ?? 'newest') === 'availability', fn ($query) => $query->orderByDesc('quantity')->orderByDesc('created_at'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn ($query) => $query->latest())
            ->paginate(9)
            ->withQueryString();

        return view('marketplace.index', compact('items', 'filters'));
    }

    public function show(MarketplaceItem $marketplaceItem): View
    {
        $marketplaceItem->load('owner');
        $user = Auth::user();

        abort_unless(
            $marketplaceItem->isPubliclyVisible()
                || ($user && ($user->isAdmin() || $marketplaceItem->owner_id === $user->id)),
            404,
        );

        return view('marketplace.show', ['item' => $marketplaceItem]);
    }

    public function create(): View
    {
        abort_unless(Auth::user()->canCreateListings(), 403);
        abort_if($this->siteIsClosedFor(Auth::user()), 503, 'Marketplace publishing is temporarily unavailable.');

        return view('marketplace.create');
    }

    public function store(Request $request, GeoService $geo): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->canCreateListings(), 403);
        abort_if($this->siteIsClosedFor($user), 503, 'Marketplace publishing is temporarily unavailable.');

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
            'product_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_without:image_url'],
            'image_url' => ['nullable', 'max:500', 'required_without:product_photo', new TrustedImageUrl()],
        ]);
        $productPhoto = $request->file('product_photo');
        unset($attributes['product_photo']);

        $settings = SiteSetting::allSettings();

        if ($attributes['transaction_type'] === MarketplaceItem::TRANSACTION_RENT && $settings['rentals_enabled'] !== '1') {
            return back()->withErrors(['transaction_type' => 'Equipment rentals are temporarily paused.'])->withInput();
        }

        $requiresReview = $settings['listing_review_required'] === '1' && ! $user->canBypassModeration();

        $attributes['owner_id'] = $user->id;
        $uploadedPath = null;

        if ($productPhoto) {
            $uploadedPath = $productPhoto->store('listing-media/'.$user->id, 'public');
            $attributes['image_url'] = Storage::url($uploadedPath);
        } else {
            $attributes['image_url'] = $attributes['image_url'] ?? $this->defaultImage($attributes['category']);
        }
        $attributes['is_available'] = ! $requiresReview;
        $attributes['is_featured'] = false;
        $attributes['moderation_status'] = $requiresReview ? 'pending' : 'approved';
        $attributes['lifecycle_status'] = $requiresReview
            ? MarketplaceItem::LIFECYCLE_REVIEW
            : MarketplaceItem::LIFECYCLE_ACTIVE;
        $attributes['published_at'] = $requiresReview ? null : now();
        $attributes['geo_hash'] = $geo->bucket(
            isset($attributes['latitude']) ? (float) $attributes['latitude'] : null,
            isset($attributes['longitude']) ? (float) $attributes['longitude'] : null,
        );
        $attributes['last_inventory_sync_at'] = now();

        $item = MarketplaceItem::create($attributes);

        if ($uploadedPath) {
            ListingMedia::create([
                'marketplace_item_id' => $item->id,
                'type' => 'image',
                'disk' => 'public',
                'path' => $uploadedPath,
                'url' => Storage::url($uploadedPath),
                'alt_text' => $item->title,
                'sort_order' => 0,
                'moderation_status' => $requiresReview ? 'pending' : 'approved',
                'metadata' => [
                    'source' => 'seller_upload',
                    'original_name' => $productPhoto->getClientOriginalName(),
                    'size' => $productPhoto->getSize(),
                    'mime' => $productPhoto->getMimeType(),
                ],
            ]);
        }

        $message = $requiresReview
            ? 'Your listing is saved for admin review before it goes live.'
            : 'Your listing is live.';

        return redirect()->route('marketplace.show', $item)->with('status', $message);
    }

    public function inquire(Request $request, MarketplaceItem $marketplaceItem, CheckoutService $checkout): RedirectResponse
    {
        $user = Auth::user();

        $attributes = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['nullable', 'date', Rule::requiredIf($marketplaceItem->isRental())],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', Rule::requiredIf($marketplaceItem->isRental())],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['required', 'email', 'max:160'],
            'message' => ['required', 'string', 'max:1200'],
        ]);

        $order = $checkout->createCheckout($user, $marketplaceItem, $attributes);

        return back()->with('status', 'Request '.$order->order_number.' created with payment authorization held in escrow.');
    }

    private function siteIsClosedFor($user): bool
    {
        $settings = SiteSetting::allSettings();

        return ! $user->isAdmin()
            && ($settings['maintenance_mode'] === '1' || $settings['marketplace_status'] === 'paused');
    }

    private function quoteAmounts(MarketplaceItem $listing, float $quantity, ?string $startDate, ?string $endDate, array $settings): array
    {
        $days = 1;

        if ($listing->isRental() && $startDate && $endDate) {
            $days = max(1, (int) Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);
        }

        $rate = $listing->isRental() ? (float) $listing->rent_rate : (float) $listing->price;
        $subtotal = round($rate * $quantity * $days, 2);
        $tax = round($subtotal * ((float) ($settings['global_tax_rate'] ?? 0) / 100), 2);
        $fee = round($subtotal * ((float) ($settings['platform_fee_rate'] ?? 0) / 100), 2);

        return [
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'platform_fee_amount' => $fee,
            'escrow_amount' => $subtotal + $tax + $fee + (float) $listing->deposit_amount,
        ];
    }

    private function defaultImage(string $category): string
    {
        return $category === MarketplaceItem::CATEGORY_EQUIPMENT
            ? asset('assets/equipment-tractor.png')
            : asset('assets/produce-crates.png');
    }

    private function fallbackItems(): Collection
    {
        return collect([
            (object) [
                'title' => 'Rice Combine Harvester',
                'category' => 'equipment',
                'transaction_type' => 'rent',
                'price' => null,
                'rent_rate' => 240,
                'unit' => 'day',
                'quantity' => 1,
                'condition' => 'Field ready',
                'location' => 'Central Luzon',
                'description' => 'Fuel-efficient harvester available for seasonal rice fields.',
                'image_url' => asset('assets/equipment-tractor.png'),
            ],
            (object) [
                'title' => 'Fresh Tomato Crates',
                'category' => 'goods',
                'transaction_type' => 'sale',
                'price' => 18,
                'rent_rate' => null,
                'unit' => 'crate',
                'quantity' => 80,
                'condition' => 'Harvested today',
                'location' => 'Laguna',
                'description' => 'Firm tomatoes sorted for restaurants, groceries, and community buyers.',
                'image_url' => asset('assets/produce-crates.png'),
            ],
            (object) [
                'title' => 'Compact Irrigation Pump',
                'category' => 'equipment',
                'transaction_type' => 'sale',
                'price' => 520,
                'rent_rate' => null,
                'unit' => 'unit',
                'quantity' => 3,
                'condition' => 'New',
                'location' => 'Davao',
                'description' => 'Portable pump for vegetable rows, nurseries, and small orchard blocks.',
                'image_url' => asset('assets/farmer-market.png'),
            ],
        ]);
    }
}
