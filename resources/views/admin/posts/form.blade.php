@extends('layouts.app')

@section('title', $post->exists ? 'Edit Post' : 'Create Post')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Post editor</span>
                <h1>{{ $post->exists ? 'Edit '.$post->title : 'Create a marketplace post.' }}</h1>
            </div>
            <a class="button ghost" href="{{ route('admin.posts.index') }}">Back to Posts</a>
        </header>

        <x-admin.navigation />

        <form class="admin-form" method="POST" action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}" enctype="multipart/form-data" data-listing-form data-loading-form>
            @csrf
            @if ($post->exists)
                @method('PATCH')
            @endif

            <div class="admin-form-grid">
                <label>
                    <span>Owner</span>
                    <select name="owner_id" required>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((int) old('owner_id', $post->owner_id) === $owner->id)>
                                {{ $owner->name }}{{ $owner->farm_name ? ' - '.$owner->farm_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Title</span>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required>
                </label>
                <label>
                    <span>Category</span>
                    <select name="category" required>
                        <option value="equipment" @selected(old('category', $post->category ?: 'equipment') === 'equipment')>Equipment</option>
                        <option value="goods" @selected(old('category', $post->category) === 'goods')>Farm goods</option>
                    </select>
                </label>
                <label>
                    <span>Mode</span>
                    <select name="transaction_type" required data-transaction-select>
                        <option value="sale" @selected(old('transaction_type', $post->transaction_type ?: 'sale') === 'sale')>For sale</option>
                        <option value="rent" @selected(old('transaction_type', $post->transaction_type) === 'rent')>For rent</option>
                    </select>
                </label>
                <label data-price-field>
                    <span>Sale price</span>
                    <input type="number" min="0" step="0.01" name="price" value="{{ old('price', $post->price) }}">
                </label>
                <label data-rent-field>
                    <span>Rent rate</span>
                    <input type="number" min="0" step="0.01" name="rent_rate" value="{{ old('rent_rate', $post->rent_rate) }}">
                </label>
                <label>
                    <span>Unit</span>
                    <input type="text" name="unit" value="{{ old('unit', $post->unit ?: 'unit') }}" required>
                </label>
                <label>
                    <span>Quantity</span>
                    <input type="number" min="0" step="0.01" name="quantity" value="{{ old('quantity', $post->quantity ?: 1) }}" required>
                </label>
                <label>
                    <span>Condition</span>
                    <input type="text" name="condition" value="{{ old('condition', $post->condition) }}">
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" value="{{ old('location', $post->location) }}" required>
                </label>
                <label>
                    <span>Latitude</span>
                    <input type="number" min="-90" max="90" step="0.0000001" name="latitude" value="{{ old('latitude', $post->latitude) }}">
                </label>
                <label>
                    <span>Longitude</span>
                    <input type="number" min="-180" max="180" step="0.0000001" name="longitude" value="{{ old('longitude', $post->longitude) }}">
                </label>
                <label>
                    <span>Harvest date</span>
                    <input type="date" name="harvest_date" value="{{ old('harvest_date', optional($post->harvest_date)->toDateString()) }}">
                </label>
                <label>
                    <span>Product photo</span>
                    <input type="file" name="product_photo" accept="image/png,image/jpeg,image/webp">
                </label>
                <label>
                    <span>Image URL fallback</span>
                    <input type="text" name="image_url" value="{{ old('image_url', $post->image_url) }}" placeholder="/assets/produce-crates.png">
                </label>
                <label class="check-line">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))>
                    <span>Featured on landing page</span>
                </label>
                <label class="check-line">
                    <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $post->exists ? $post->is_available : true))>
                    <span>Visible and available</span>
                </label>
                <label>
                    <span>Moderation status</span>
                    <select name="moderation_status">
                        <option value="approved" @selected(old('moderation_status', $post->moderation_status ?: 'approved') === 'approved')>Approved</option>
                        <option value="pending" @selected(old('moderation_status', $post->moderation_status) === 'pending')>Pending</option>
                        <option value="rejected" @selected(old('moderation_status', $post->moderation_status) === 'rejected')>Rejected</option>
                    </select>
                </label>
                <label>
                    <span>Lifecycle status</span>
                    <select name="lifecycle_status">
                        <option value="active" @selected(old('lifecycle_status', $post->lifecycle_status ?: 'active') === 'active')>Active</option>
                        <option value="review" @selected(old('lifecycle_status', $post->lifecycle_status) === 'review')>Review</option>
                        <option value="hidden" @selected(old('lifecycle_status', $post->lifecycle_status) === 'hidden')>Hidden</option>
                        <option value="rejected" @selected(old('lifecycle_status', $post->lifecycle_status) === 'rejected')>Rejected</option>
                    </select>
                </label>
                <label>
                    <span>Deposit amount</span>
                    <input type="number" min="0" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', $post->deposit_amount ?? 0) }}">
                </label>
                <label>
                    <span>Min order quantity</span>
                    <input type="number" min="0.01" step="0.01" name="min_order_quantity" value="{{ old('min_order_quantity', $post->min_order_quantity ?? 0.01) }}">
                </label>
                <label>
                    <span>Max order quantity</span>
                    <input type="number" min="0.01" step="0.01" name="max_order_quantity" value="{{ old('max_order_quantity', $post->max_order_quantity) }}">
                </label>
                <label>
                    <span>Cancellation policy</span>
                    <select name="cancellation_policy">
                        <option value="flexible" @selected(old('cancellation_policy', $post->cancellation_policy ?: 'standard') === 'flexible')>Flexible</option>
                        <option value="standard" @selected(old('cancellation_policy', $post->cancellation_policy ?: 'standard') === 'standard')>Standard</option>
                        <option value="strict" @selected(old('cancellation_policy', $post->cancellation_policy) === 'strict')>Strict</option>
                    </select>
                </label>
            </div>

            <label>
                <span>Description</span>
                <textarea name="description" rows="5" required>{{ old('description', $post->description) }}</textarea>
            </label>

            <button class="button primary full" type="submit" data-loading-label="Saving...">{{ $post->exists ? 'Save Post' : 'Create Post' }}</button>
        </form>
    </section>
@endsection
