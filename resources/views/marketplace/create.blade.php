@extends('layouts.app')

@section('title', 'Create Listing')
@section('body_class', 'form-page')

@section('content')
    <section class="page-hero compact">
        <span class="eyebrow">New listing</span>
        <h1>Sell farm goods or rent out useful equipment.</h1>
        <p>Add the details buyers need to decide quickly. New listings may be reviewed before going live.</p>
    </section>

    <section class="form-section">
        <form class="panel-form wide" method="POST" action="{{ route('marketplace.store') }}" enctype="multipart/form-data" data-listing-form data-loading-form>
            @csrf
            @if ($errors->any())
                <div class="form-errors" role="alert" aria-live="assertive">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <label>
                <span>Listing title</span>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Rice harvester, organic tomatoes, irrigation pump" required>
            </label>

            <div class="form-grid">
                <label>
                    <span>Category</span>
                    <select name="category" required>
                        <option value="equipment" @selected(old('category') === 'equipment')>Equipment</option>
                        <option value="goods" @selected(old('category') === 'goods')>Farm goods</option>
                    </select>
                </label>
                <label>
                    <span>Transaction</span>
                    <select name="transaction_type" required data-transaction-select>
                        <option value="sale" @selected(old('transaction_type') === 'sale')>Sell</option>
                        <option value="rent" @selected(old('transaction_type') === 'rent')>Rent</option>
                    </select>
                </label>
            </div>

            <div class="form-grid">
                <label data-price-field>
                    <span>Sale price</span>
                    <input type="number" min="0" step="0.01" name="price" value="{{ old('price') }}">
                </label>
                <label data-rent-field hidden>
                    <span>Rental rate</span>
                    <input type="number" min="0" step="0.01" name="rent_rate" value="{{ old('rent_rate') }}">
                </label>
                <label>
                    <span>Unit</span>
                    <input type="text" name="unit" value="{{ old('unit', 'unit') }}" placeholder="day, crate, kg, sack" required>
                </label>
            </div>

            <div class="form-grid">
                <label>
                    <span>Quantity</span>
                    <input type="number" min="0" step="0.01" name="quantity" value="{{ old('quantity', 1) }}" required>
                </label>
                <label>
                    <span>Condition</span>
                    <input type="text" name="condition" value="{{ old('condition') }}" placeholder="New, field ready, harvested today">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    <span>Location</span>
                    <input type="text" name="location" value="{{ old('location', auth()->user()->location) }}" required>
                </label>
                <label>
                    <span>Harvest date</span>
                    <input type="date" name="harvest_date" value="{{ old('harvest_date') }}">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    <span>Latitude</span>
                    <input type="number" min="-90" max="90" step="0.0000001" name="latitude" value="{{ old('latitude') }}" placeholder="Optional for map search">
                </label>
                <label>
                    <span>Longitude</span>
                    <input type="number" min="-180" max="180" step="0.0000001" name="longitude" value="{{ old('longitude') }}" placeholder="Optional for map search">
                </label>
            </div>

            <div class="form-grid">
                <label>
                    <span>Product photo</span>
                    <input type="file" name="product_photo" accept="image/png,image/jpeg,image/webp" @required(! old('image_url'))>
                </label>
                <label>
                    <span>Image URL fallback</span>
                    <input type="url" name="image_url" value="{{ old('image_url') }}" placeholder="Use only if you cannot upload">
                </label>
            </div>
            <p class="form-alt">Upload a clear product or equipment photo. JPG, PNG, or WebP up to 5 MB.</p>

            <label>
                <span>Description</span>
                <textarea name="description" rows="6" required>{{ old('description') }}</textarea>
            </label>

            <button class="button primary full" type="submit" data-loading-label="Publishing...">Publish Listing</button>
        </form>
    </section>
@endsection
