@extends('layouts.app')

@section('title', 'Manage Orders')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Order management</span>
                <h1>Inquiry orders, rental requests, buyer messages, and statuses.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="{{ route('admin.export', 'orders') }}">Export CSV</a>
                <a class="button primary" href="{{ route('admin.orders.create') }}">Create Order</a>
            </div>
        </header>

        <x-admin.navigation />

        <form class="admin-filter" method="GET" action="{{ route('admin.orders.index') }}">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Buyer, email, post, message">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button primary" type="submit">Filter</button>
        </form>

        <form class="bulk-actions" method="POST" action="{{ route('admin.orders.bulk') }}" data-bulk-form data-confirm="Apply this bulk action to the selected orders?">
            @csrf
            <label>
                <span>Bulk action</span>
                <select name="action" required>
                    <option value="approve">Approve selected</option>
                    <option value="export">Export selected</option>
                    <option value="delete">Delete selected</option>
                </select>
            </label>
            <div data-bulk-inputs></div>
            <button class="button primary" type="submit">Apply</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" data-bulk-select-all aria-label="Select all orders"></th>
                        <th>Order</th>
                        <th>Buyer</th>
                        <th>Post owner</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td data-label="Select"><input type="checkbox" class="bulk-checkbox" value="{{ $order->id }}" aria-label="Select order {{ $order->id }}"></td>
                            <td data-label="Order">
                                <strong>{{ $order->marketplaceItem->title }}</strong>
                                <span>{{ \Illuminate\Support\Str::limit($order->message, 72) }}</span>
                            </td>
                            <td data-label="Buyer">
                                <strong>{{ $order->user->name }}</strong>
                                <span>{{ $order->contact_email }}</span>
                            </td>
                            <td data-label="Post owner">{{ $order->marketplaceItem->owner->name }}</td>
                            <td data-label="Quantity">{{ number_format((float) $order->quantity, 2) }}</td>
                            <td data-label="Status">
                                <span class="status-pill status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                                <form class="quick-status-form" method="POST" action="{{ route('admin.orders.status', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" onchange="this.form.submit()" aria-label="Update order status">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td data-label="Dates">
                                {{ optional($order->start_date)->format('M d') ?: 'Anytime' }}
                                @if ($order->end_date)
                                    - {{ $order->end_date->format('M d') }}
                                @endif
                            </td>
                            <td data-label="Actions">
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.orders.edit', $order) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-confirm="Delete this order?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $orders->links() }}</div>
    </section>
@endsection
