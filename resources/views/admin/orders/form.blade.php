@extends('layouts.app')

@section('title', $order->exists ? 'Edit Order' : 'Create Order')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Order editor</span>
                <h1>{{ $order->exists ? 'Edit order #'.$order->id : 'Create a managed order.' }}</h1>
            </div>
            <a class="button ghost" href="{{ route('admin.orders.index') }}">Back to Orders</a>
        </header>

        <x-admin.navigation />

        <form class="admin-form" method="POST" action="{{ $order->exists ? route('admin.orders.update', $order) : route('admin.orders.store') }}">
            @csrf
            @if ($order->exists)
                @method('PATCH')
            @endif

            <div class="admin-form-grid">
                <label>
                    <span>Post</span>
                    <select name="marketplace_item_id" required>
                        @foreach ($posts as $post)
                            <option value="{{ $post->id }}" @selected((int) old('marketplace_item_id', $order->marketplace_item_id) === $post->id)>
                                {{ $post->title }} - {{ $post->owner->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Buyer</span>
                    <select name="user_id" required>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) old('user_id', $order->user_id) === $user->id)>
                                {{ $user->name }} - {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Quantity</span>
                    <input type="number" min="0.01" step="0.01" name="quantity" value="{{ old('quantity', $order->quantity ?: 1) }}" required>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $order->status ?: 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Start date</span>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($order->start_date)->toDateString()) }}">
                </label>
                <label>
                    <span>End date</span>
                    <input type="date" name="end_date" value="{{ old('end_date', optional($order->end_date)->toDateString()) }}">
                </label>
                <label>
                    <span>Contact phone</span>
                    <input type="tel" name="contact_phone" value="{{ old('contact_phone', $order->contact_phone) }}">
                </label>
                <label>
                    <span>Contact email</span>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $order->contact_email) }}" required>
                </label>
            </div>

            <label>
                <span>Message</span>
                <textarea name="message" rows="5" required>{{ old('message', $order->message) }}</textarea>
            </label>

            <button class="button primary full" type="submit">{{ $order->exists ? 'Save Order' : 'Create Order' }}</button>
        </form>
    </section>
@endsection
