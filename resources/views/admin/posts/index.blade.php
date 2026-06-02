@extends('layouts.app')

@section('title', 'Manage Posts')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Post management</span>
                <h1>Listings, availability, featured placement, and user posts.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="{{ route('admin.export', 'posts') }}">Export CSV</a>
                <a class="button primary" href="{{ route('admin.posts.create') }}">Create Post</a>
            </div>
        </header>

        <x-admin.navigation />

        <form class="admin-filter" method="GET" action="{{ route('admin.posts.index') }}">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Title, location, description">
            </label>
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <option value="equipment" @selected(request('category') === 'equipment')>Equipment</option>
                    <option value="goods" @selected(request('category') === 'goods')>Goods</option>
                </select>
            </label>
            <label>
                <span>Mode</span>
                <select name="transaction_type">
                    <option value="">All modes</option>
                    <option value="sale" @selected(request('transaction_type') === 'sale')>Sale</option>
                    <option value="rent" @selected(request('transaction_type') === 'rent')>Rent</option>
                </select>
            </label>
            <label>
                <span>Availability</span>
                <select name="availability">
                    <option value="">Any</option>
                    <option value="available" @selected(request('availability') === 'available')>Available</option>
                    <option value="hidden" @selected(request('availability') === 'hidden')>Hidden</option>
                </select>
            </label>
            <label>
                <span>Moderation</span>
                <select name="moderation_status">
                    <option value="">Any status</option>
                    <option value="approved" @selected(request('moderation_status') === 'approved')>Approved</option>
                    <option value="pending" @selected(request('moderation_status') === 'pending')>Pending</option>
                    <option value="rejected" @selected(request('moderation_status') === 'rejected')>Rejected</option>
                </select>
            </label>
            <button class="button primary" type="submit">Filter</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Owner</th>
                        <th>Mode</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Flag reason</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td data-label="Post">
                                <strong>{{ $post->title }}</strong>
                                <span>{{ ucfirst($post->category) }} - {{ $post->location }}</span>
                            </td>
                            <td data-label="Owner">{{ $post->owner->name }}</td>
                            <td data-label="Mode">{{ ucfirst($post->transaction_type) }}</td>
                            <td data-label="Price">{{ $post->priceLabel() }}</td>
                            <td data-label="Status">
                                <span class="status-pill status-{{ $post->moderation_status }}">{{ ucfirst($post->moderation_status) }}</span>
                                <span class="status-pill {{ $post->is_available ? 'is-good' : 'is-muted' }}">{{ $post->is_available ? 'Available' : 'Hidden' }}</span>
                                @if ($post->is_featured)
                                    <span class="status-pill is-warm">Featured</span>
                                @endif
                            </td>
                            <td data-label="Flag reason">{{ $post->flagged_reason ?: 'None' }}</td>
                            <td data-label="Orders">{{ $post->inquiries_count }}</td>
                            <td data-label="Actions">
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                                    <a href="{{ route('marketplace.show', $post) }}">View</a>
                                    <button type="button"
                                        data-preview-post
                                        data-title="{{ $post->title }}"
                                        data-owner="{{ $post->owner->name }}"
                                        data-image="{{ $post->image_url }}"
                                        data-price="{{ $post->priceLabel() }}"
                                        data-location="{{ $post->location }}"
                                        data-description="{{ $post->description }}"
                                        data-reason="{{ $post->flagged_reason ?: 'No flags' }}">Preview</button>
                                    <form method="POST" action="{{ route('admin.posts.moderate', $post) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="{{ $post->is_featured ? 'unfeature' : 'feature' }}">
                                        <button type="submit">{{ $post->is_featured ? 'Unfeature' : 'Feature' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.posts.moderate', $post) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="{{ $post->is_available ? 'hide' : 'show' }}">
                                        <button type="submit">{{ $post->is_available ? 'Hide' : 'Show' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" data-confirm="Delete this marketplace post and its related orders?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No posts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $posts->links() }}</div>
    </section>
@endsection
