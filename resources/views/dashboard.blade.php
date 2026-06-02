@extends('layouts.app')

@section('title', 'Dashboard')
@section('body_class', 'dashboard-page')

@section('content')
    <section class="dashboard-hero">
        <div>
            <span class="eyebrow">{{ $roleProfile['eyebrow'] }}</span>
            <h1>{{ $roleProfile['title'] }}</h1>
            <p>{{ $roleProfile['description'] }}</p>
            <div class="trust-row dashboard-role-tags" aria-label="Account signals">
                <span>{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                <span>{{ ucfirst($user->status) }} account</span>
                <span>{{ ucfirst($user->kyc_status) }} verification</span>
                <span>{{ $user->location }}</span>
            </div>
        </div>
        @if ($user->canCreateListings())
            <a class="button primary" href="{{ route('marketplace.create') }}">Create Listing</a>
        @endif
    </section>

    <section class="role-action-grid" aria-label="Quick actions">
        @foreach ($quickActions as $action)
            <x-ui.action-card :href="$action['href']" :label="$action['label']" :description="$action['description']" />
        @endforeach
    </section>

    <section class="stat-grid" aria-label="Workspace metrics">
        @foreach ($roleCards as $card)
            <x-ui.metric-card :label="$card['label']" :value="$card['value']" :hint="$card['hint']" />
        @endforeach
    </section>

    @if ($roleAlerts)
        <x-ui.panel class="dashboard-alerts" eyebrow="Priority alerts" title="Needs attention" aria-live="polite">
            @foreach ($roleAlerts as $alert)
                <article>
                    <strong>{{ $alert['title'] }}</strong>
                    <p>{{ $alert['body'] }}</p>
                </article>
            @endforeach
        </x-ui.panel>
    @endif

    <section class="dashboard-grid">
        <x-ui.panel class="dashboard-panel" title="Order timeline">
            <div class="message-list">
                @forelse ($recentOrders as $order)
                    <article>
                        <div class="message-title-line">
                            <strong>{{ $order->order_number }}</strong>
                            <x-ui.status-badge :status="$order->status" />
                        </div>
                        <p>
                            {{ $order->items->first()?->title ?? 'Marketplace order' }}
                            @if ($order->escrowTransactions->first())
                                <span aria-hidden="true">&middot;</span> Escrow {{ $order->escrowTransactions->first()->status }}
                            @endif
                        </p>
                        <small>${{ number_format((float) $order->total_amount, 2) }} <span aria-hidden="true">&middot;</span> {{ $order->created_at->diffForHumans() }}</small>
                    </article>
                @empty
                    <x-ui.empty-state message="No order timeline yet." />
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel class="dashboard-panel" title="Your listings" :action-href="$user->canCreateListings() ? route('marketplace.create') : null" action-label="Add">
            <div class="table-list">
                @forelse ($listings as $listing)
                    <a href="{{ route('marketplace.show', $listing) }}" class="table-row">
                        <span>{{ $listing->title }}</span>
                        <strong>{{ $listing->priceLabel() }}</strong>
                        <small>{{ $listing->inquiries_count }} inquiries</small>
                    </a>
                @empty
                    <x-ui.empty-state message="No listings yet." />
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel class="dashboard-panel" title="Received inquiries">
            <div class="message-list">
                @forelse ($receivedInquiries as $inquiry)
                    <article>
                        <strong>{{ $inquiry->marketplaceItem->title }}</strong>
                        <p>{{ \Illuminate\Support\Str::limit($inquiry->message, 120) }}</p>
                        <small>{{ $inquiry->user->name }} - {{ $inquiry->contact_email }} - {{ ucfirst($inquiry->escrow_status) }}</small>
                    </article>
                @empty
                    <x-ui.empty-state message="No inquiries received yet." />
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel class="dashboard-panel" title="Your sent inquiries">
            <div class="message-list">
                @forelse ($sentInquiries as $inquiry)
                    <article>
                        <strong>{{ $inquiry->marketplaceItem->title }}</strong>
                        <p>{{ \Illuminate\Support\Str::limit($inquiry->message, 120) }}</p>
                        <small>Status: {{ ucfirst($inquiry->status) }} - Escrow: {{ ucfirst($inquiry->escrow_status) }}</small>
                    </article>
                @empty
                    <x-ui.empty-state message="No inquiries sent yet." />
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel class="dashboard-panel" title="Notifications" :action-href="route('profile.edit')" action-label="Settings">
            <div class="message-list">
                @forelse ($recentNotifications as $notification)
                    <article>
                        <div class="message-title-line">
                            <strong>{{ $notification->subject }}</strong>
                            <x-ui.status-badge :status="$notification->status" />
                        </div>
                        <p>{{ $notification->body }}</p>
                        <small>{{ $notification->created_at->diffForHumans() }}</small>
                    </article>
                @empty
                    <x-ui.empty-state message="No notifications yet." />
                @endforelse
            </div>
        </x-ui.panel>
    </section>
@endsection
