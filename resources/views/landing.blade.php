@extends('layouts.app')

@section('title', 'Farm Marketplace')
@section('body_class', 'landing-page')

@section('content')
    <section class="hero-shell">
        <div class="hero-copy">
            <span class="eyebrow">Farm trade without the middle mile</span>
            <h1>{{ $siteSettings['homepage_headline'] }}</h1>
            <p>{{ $siteSettings['homepage_copy'] }}</p>
            <div class="hero-actions">
                <a class="button primary" href="{{ route('marketplace.index') }}">Explore Marketplace</a>
                <a class="button ghost" href="{{ route('signup') }}">Start Selling</a>
                <a class="button glass" href="{{ route('login') }}">Admin Login</a>
            </div>
            <div class="trust-strip" aria-label="FarmBridge marketplace stats">
                <span><strong data-count="420">0</strong> farm listings</span>
                <span><strong data-count="38">0</strong> regions served</span>
                <span><strong data-count="24">0</strong> hour response goal</span>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <img src="{{ asset('assets/hero-farm-market.png') }}" alt="" decoding="async">
            <div class="float-card float-card-a">
                <span>Rent</span>
                <strong>Harvester</strong>
                <small>$240 / day</small>
            </div>
            <div class="float-card float-card-b">
                <span>Fresh</span>
                <strong>Tomatoes</strong>
                <small>80 crates</small>
            </div>
        </div>
    </section>

    @if (! empty($siteSettings['site_announcement']))
        <section class="announcement-band">
            <span>{{ ucfirst($siteSettings['marketplace_status'] ?? 'open') }}</span>
            <p>{{ $siteSettings['site_announcement'] }}</p>
        </section>
    @endif

    <section class="admin-access-band">
        <div>
            <span class="eyebrow">Admin access</span>
            <h2>Run the marketplace from a seeded admin dashboard.</h2>
            <p>Use the admin account to review members, active listings, featured inventory, and pending inquiries.</p>
        </div>
        <a class="button primary" href="{{ route('login') }}">Open Admin Login</a>
    </section>

    <section class="category-band" aria-label="Marketplace categories">
        <article>
            <img src="{{ asset('assets/equipment-tractor.png') }}" alt="Tractor and farm tools" loading="lazy" decoding="async">
            <div>
                <span>Equipment</span>
                <h2>Rent or sell machines when nearby farms need them.</h2>
            </div>
        </article>
        <article>
            <img src="{{ asset('assets/produce-crates.png') }}" alt="Fresh harvested produce crates" loading="lazy" decoding="async">
            <div>
                <span>Farm Goods</span>
                <h2>Move fresh harvests faster from field to buyer.</h2>
            </div>
        </article>
        <article>
            <img src="{{ asset('assets/farmer-market.png') }}" alt="Farmer market delivery" loading="lazy" decoding="async">
            <div>
                <span>Local Deals</span>
                <h2>Coordinate orders, pickups, and seasonal supply.</h2>
            </div>
        </article>
    </section>

    <section class="market-section" id="marketplace">
        <div class="section-heading">
            <span class="eyebrow">Live marketplace</span>
            <h2>Featured farm listings</h2>
            <p>Filter by purpose and search what is ready for sale or rental.</p>
        </div>

        <div class="market-toolbar" data-filter-toolbar>
            <label>
                <span>Search</span>
                <input type="search" placeholder="Tomatoes, pump, tractor..." data-market-search>
            </label>
            <label>
                <span>Category</span>
                <select data-market-category>
                    <option value="all">All categories</option>
                    <option value="equipment">Equipment</option>
                    <option value="goods">Farm goods</option>
                </select>
            </label>
            <label>
                <span>Mode</span>
                <select data-market-type>
                    <option value="all">Sale and rent</option>
                    <option value="sale">For sale</option>
                    <option value="rent">For rent</option>
                </select>
            </label>
        </div>

        <div class="listing-grid" data-listing-grid aria-live="polite">
            @foreach ($featured as $item)
                @php
                    $isRent = $item->transaction_type === 'rent';
                    $price = $isRent ? $item->rent_rate : $item->price;
                    $href = isset($item->id) ? route('marketplace.show', $item) : route('marketplace.index');
                @endphp
                <article class="listing-card reveal"
                    data-category="{{ $item->category }}"
                    data-type="{{ $item->transaction_type }}"
                    data-title="{{ strtolower($item->title.' '.$item->description.' '.$item->location) }}">
                    <a href="{{ $href }}" class="listing-image">
                        <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy" decoding="async">
                        <x-ui.status-badge :status="$isRent ? 'rent' : 'sale'" :label="$isRent ? 'Rent' : 'Sale'" />
                    </a>
                    <div class="listing-body">
                        <div class="listing-meta">
                            <span>{{ ucfirst($item->category) }}</span>
                            <span>{{ $item->location }}</span>
                        </div>
                        <h3><a href="{{ $href }}">{{ $item->title }}</a></h3>
                        <p>{{ \Illuminate\Support\Str::limit($item->description, 108) }}</p>
                        <div class="listing-foot">
                            <strong>${{ number_format((float) $price, 2) }} / {{ $item->unit }}</strong>
                            <small>{{ number_format((float) $item->quantity) }} available</small>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <x-ui.empty-state message="No matching listings yet." data-empty-state hidden />
    </section>

    <section class="seller-panel">
        <div>
            <span class="eyebrow">For farmers</span>
            <h2>Post harvests, equipment rentals, or tools for sale in minutes.</h2>
            <p>Create listings with quantity, price, location, condition, and rental dates. Buyers can send inquiries directly from each item page.</p>
        </div>
        <a class="button primary" href="{{ route('signup') }}">Create Account</a>
    </section>
@endsection
