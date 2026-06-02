@extends('layouts.app')

@section('title', $item->title)
@section('body_class', 'detail-page')

@section('content')
    <section class="detail-shell">
        <div class="detail-media">
            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" decoding="async">
            <x-ui.status-badge :status="$item->transaction_type" :label="$item->typeLabel()" />
        </div>

        <article class="detail-content">
            <div class="listing-meta">
                <span>{{ ucfirst($item->category) }}</span>
                <span>{{ $item->location }}</span>
            </div>
            <h1>{{ $item->title }}</h1>
            <p class="detail-price">{{ $item->priceLabel() }}</p>
            <p>{{ $item->description }}</p>
            <div class="trust-row detail-trust" aria-label="Listing trust signals">
                <span>{{ $item->owner?->kyc_status === 'verified' ? 'Verified seller' : 'Seller review pending' }}</span>
                <span>Listing score {{ $item->listing_score ?? 50 }}</span>
                <span>{{ ucfirst($item->cancellation_policy ?? 'standard') }} cancellation</span>
            </div>

            <dl class="detail-facts">
                <div>
                    <dt>Quantity</dt>
                    <dd>{{ number_format((float) $item->quantity) }} {{ $item->unit }}</dd>
                </div>
                <div>
                    <dt>Condition</dt>
                    <dd>{{ $item->condition ?? 'Not specified' }}</dd>
                </div>
                <div>
                    <dt>Owner</dt>
                    <dd>{{ $item->owner->farm_name ?? $item->owner->name }}</dd>
                </div>
                <div>
                    <dt>Available now</dt>
                    <dd>{{ number_format($item->availableQuantity(), 2) }} {{ $item->unit }}</dd>
                </div>
                @if ((float) $item->deposit_amount > 0)
                    <div>
                        <dt>Rental deposit</dt>
                        <dd>${{ number_format((float) $item->deposit_amount, 2) }}</dd>
                    </div>
                @endif
                @if ($item->harvest_date)
                    <div>
                        <dt>Harvested</dt>
                        <dd>{{ $item->harvest_date->format('M d, Y') }}</dd>
                    </div>
                @endif
            </dl>
        </article>
    </section>

    <section class="inquiry-section">
        <div>
            <span class="eyebrow">Contact owner</span>
            <h2>Start a protected request for this {{ $item->isRental() ? 'rental' : 'listing' }}.</h2>
            <p>The owner receives your request, while checkout creates a payment authorization and escrow hold.</p>
        </div>

        @auth
            <form class="panel-form" method="POST" action="{{ route('marketplace.inquire', $item) }}" aria-describedby="checkout-help" data-loading-form>
                @csrf
                @if ($item->owner_id === auth()->id())
                    <div class="form-errors" role="status" aria-live="polite">
                        <p>This is your listing, so buyers will see the request form here.</p>
                    </div>
                @endif

                <p id="checkout-help" class="form-alt">Your payment is authorized and tracked through FarmBridge escrow until fulfillment.</p>
                @if ($errors->any())
                    <div class="form-errors" role="alert" aria-live="assertive">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="form-grid">
                    <label>
                        <span>Quantity</span>
                        <input type="number" min="{{ $item->min_order_quantity ?? 0.01 }}" @if ($item->max_order_quantity) max="{{ $item->max_order_quantity }}" @endif step="0.01" name="quantity" value="{{ old('quantity', $item->min_order_quantity ?? 1) }}" required>
                    </label>
                    <label>
                        <span>Contact email</span>
                        <input type="email" name="contact_email" value="{{ old('contact_email', auth()->user()->email) }}" required>
                    </label>
                </div>

                @if ($item->isRental())
                    <div class="form-grid">
                        <label>
                            <span>Start date</span>
                            <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                        </label>
                        <label>
                            <span>End date</span>
                            <input type="date" name="end_date" value="{{ old('end_date') }}" required>
                        </label>
                    </div>
                @endif

                <label>
                    <span>Phone</span>
                    <input type="tel" name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone) }}">
                </label>
                <label>
                    <span>Message</span>
                    <textarea name="message" rows="5" required>{{ old('message', 'Hello, I am interested in this listing. Is it still available?') }}</textarea>
                </label>
                <button class="button primary full" type="submit" data-loading-label="Starting..." @disabled($item->owner_id === auth()->id())>Start Protected Request</button>
            </form>
        @else
            <div class="auth-prompt">
                <p>Login or create an account to send an inquiry.</p>
                <div class="hero-actions">
                    <a class="button primary" href="{{ route('login') }}">Login</a>
                    <a class="button ghost" href="{{ route('signup') }}">Sign Up</a>
                </div>
            </div>
        @endauth
    </section>
@endsection
