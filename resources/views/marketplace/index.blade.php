@extends('layouts.app')

@section('title', 'Marketplace')
@section('body_class', 'marketplace-page')

@section('content')
    <section class="page-hero compact">
        <span class="eyebrow">Browse listings</span>
        <h1>Equipment rentals, equipment sales, and fresh farm goods.</h1>
        <p>Search local listings and contact the farmer or owner directly.</p>
    </section>

    <section class="market-section page-section">
        <form class="market-toolbar" method="GET" action="{{ route('marketplace.index') }}" data-loading-form>
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search listing" autocomplete="off">
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <option value="equipment" @selected(($filters['category'] ?? '') === 'equipment')>Equipment</option>
                    <option value="goods" @selected(($filters['category'] ?? '') === 'goods')>Farm goods</option>
                </select>
            </label>
            <label>
                <span>Mode</span>
                <select name="transaction_type">
                    <option value="">Sale and rent</option>
                    <option value="sale" @selected(($filters['transaction_type'] ?? '') === 'sale')>For sale</option>
                    <option value="rent" @selected(($filters['transaction_type'] ?? '') === 'rent')>For rent</option>
                </select>
            </label>
            <label>
                <span>Location</span>
                <input type="search" name="location" value="{{ $filters['location'] ?? '' }}" placeholder="Province or city">
            </label>
            <label>
                <span>Sort</span>
                <select name="sort">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                    <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>Price: low first</option>
                    <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>Price: high first</option>
                    <option value="availability" @selected(($filters['sort'] ?? '') === 'availability')>Most available</option>
                </select>
            </label>
            <button class="button primary" type="submit" data-loading-label="Filtering...">Filter</button>
        </form>

        <div class="result-summary" role="status">
            Showing {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} marketplace results.
        </div>

        <div class="skeleton-grid" aria-hidden="true" hidden data-skeleton>
            @for ($index = 0; $index < 3; $index++)
                <div class="skeleton-card"></div>
            @endfor
        </div>

        <div class="listing-grid" aria-live="polite">
            @forelse ($items as $item)
                <article class="listing-card reveal">
                    <a href="{{ route('marketplace.show', $item) }}" class="listing-image">
                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy" decoding="async">
                        <x-ui.status-badge :status="$item->isRental() ? 'rent' : 'sale'" :label="$item->isRental() ? 'Rent' : 'Sale'" />
                    </a>
                    <div class="listing-body">
                        <div class="listing-meta">
                            <span>{{ ucfirst($item->category) }}</span>
                            <span>{{ $item->location }}</span>
                        </div>
                        <h3><a href="{{ route('marketplace.show', $item) }}">{{ $item->title }}</a></h3>
                        <p>{{ \Illuminate\Support\Str::limit($item->description, 108) }}</p>
                        <div class="listing-foot">
                            <strong>{{ $item->priceLabel() }}</strong>
                            <small>{{ number_format($item->availableQuantity(), 2) }} available</small>
                        </div>
                        <div class="trust-row" aria-label="Listing trust signals">
                            <span>{{ $item->owner?->kyc_status === 'verified' ? 'Verified seller' : 'Seller review pending' }}</span>
                            <span>Score {{ $item->listing_score ?? 50 }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty-state message="No listings matched your filters." />
            @endforelse
        </div>

        <div class="pagination-wrap">
            {{ $items->links() }}
        </div>
    </section>
@endsection
