@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-hero">
            <div>
                <span class="eyebrow">Admin dashboard</span>
                <h1>Manage FarmBridge from one responsive console.</h1>
                <p>Track users, orders, marketplace posts, site settings, and operations health.</p>
            </div>
            <form class="admin-range" method="GET" action="{{ route('admin.dashboard') }}">
                <label>
                    <span>Analytics range</span>
                    <select name="range" onchange="this.form.submit()">
                        @foreach ($rangeOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) $range === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="button primary" type="submit">Apply</button>
            </form>
        </header>

        <x-admin.navigation />

        <section class="admin-command-bar">
            <div>
                <span class="status-pill status-{{ $siteSettings['marketplace_status'] }}">{{ ucfirst($siteSettings['marketplace_status']) }}</span>
                <strong>{{ $siteSettings['site_announcement'] ?: 'FarmBridge operations are ready.' }}</strong>
            </div>
            <div class="admin-export-actions">
                <a href="{{ route('admin.export', 'users') }}">Export Users</a>
                <a href="{{ route('admin.export', 'posts') }}">Export Posts</a>
                <a href="{{ route('admin.export', 'orders') }}">Export Orders</a>
                <a href="{{ route('admin.export', 'audit') }}">Export Audit</a>
            </div>
        </section>

        <section class="admin-kpi-grid" aria-label="{{ $rangeLabel }} analytics">
            <article>
                <span>Users</span>
                <strong>{{ number_format($stats['users']) }}</strong>
                <small>{{ $rangeLabel }}</small>
                <em class="trend-badge {{ $trends['users'] === null || $trends['users'] >= 0 ? 'is-up' : 'is-down' }}">
                    {{ $trends['users'] === null ? 'New' : (($trends['users'] >= 0 ? '+' : '').$trends['users'].'%') }}
                </em>
            </article>
            <article>
                <span>Posts</span>
                <strong>{{ number_format($stats['posts']) }}</strong>
                <small>{{ $stats['availablePosts'] }} available</small>
                <em class="trend-badge {{ $trends['posts'] === null || $trends['posts'] >= 0 ? 'is-up' : 'is-down' }}">
                    {{ $trends['posts'] === null ? 'New' : (($trends['posts'] >= 0 ? '+' : '').$trends['posts'].'%') }}
                </em>
            </article>
            <article>
                <span>Orders</span>
                <strong>{{ number_format($stats['orders']) }}</strong>
                <small>{{ $stats['pendingOrders'] }} pending</small>
                <em class="trend-badge {{ $trends['orders'] === null || $trends['orders'] >= 0 ? 'is-up' : 'is-down' }}">
                    {{ $trends['orders'] === null ? 'New' : (($trends['orders'] >= 0 ? '+' : '').$trends['orders'].'%') }}
                </em>
            </article>
            <article>
                <span>Estimated value</span>
                <strong>${{ number_format($stats['estimatedValue'], 2) }}</strong>
                <small>Approved and fulfilled</small>
                <em class="trend-badge {{ $trends['estimatedValue'] === null || $trends['estimatedValue'] >= 0 ? 'is-up' : 'is-down' }}">
                    {{ $trends['estimatedValue'] === null ? 'New' : (($trends['estimatedValue'] >= 0 ? '+' : '').$trends['estimatedValue'].'%') }}
                </em>
            </article>
        </section>

        <section class="admin-action-grid">
            <a href="{{ route('admin.users.create') }}">
                <span>Users</span>
                <strong>Create or edit accounts</strong>
            </a>
            <a href="{{ route('admin.posts.create') }}">
                <span>Posts</span>
                <strong>Publish or manage listings</strong>
            </a>
            <a href="{{ route('admin.orders.create') }}">
                <span>Orders</span>
                <strong>Create or update orders</strong>
            </a>
            <a href="{{ route('admin.settings.edit') }}">
                <span>Settings</span>
                <strong>Theme, password, site copy</strong>
            </a>
        </section>

        <section class="admin-analytics-grid">
            <article class="admin-panel wide">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Activity</span>
                        <h2>Orders vs revenue</h2>
                    </div>
                    <small>{{ $rangeLabel }}</small>
                </div>
                <canvas class="admin-canvas tall" data-chart="multi-line"
                    data-labels='@json(collect($chart)->pluck('label'))'
                    data-orders='@json(collect($chart)->pluck('orders'))'
                    data-revenue='@json(collect($valueChart)->pluck('value'))'></canvas>
                <div class="chart-legend">
                    <span><i class="bar-orders"></i> Orders</span>
                    <span><i class="bar-users"></i> Revenue</span>
                </div>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Order mix</h2>
                    <a href="{{ route('admin.orders.index') }}">Manage</a>
                </div>
                <canvas class="admin-canvas" data-chart="doughnut"
                    data-labels='@json($statusCounts->keys()->map(fn ($status) => ucfirst($status))->values())'
                    data-values='@json($statusCounts->values())'></canvas>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Value trend</h2>
                    <small>Approved and fulfilled</small>
                </div>
                <canvas class="admin-canvas" data-chart="line"
                    data-labels='@json(collect($valueChart)->pluck('label'))'
                    data-values='@json(collect($valueChart)->pluck('value'))'></canvas>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Post categories</h2>
                    <a href="{{ route('admin.posts.index') }}">Manage</a>
                </div>
                <canvas class="admin-canvas" data-chart="doughnut"
                    data-labels='@json($categoryCounts->keys()->map(fn ($category) => ucfirst($category))->values())'
                    data-values='@json($categoryCounts->values())'></canvas>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">System health</span>
                        <h2>Server status</h2>
                    </div>
                </div>
                <div class="metric-list health-list">
                    <div>
                        <span>Database</span>
                        <strong>{{ ucfirst($systemHealth['database']) }}</strong>
                    </div>
                    <div>
                        <span>DB latency</span>
                        <strong>{{ $systemHealth['dbLatency'] }}ms</strong>
                    </div>
                    <div>
                        <span>API latency</span>
                        <strong>{{ $systemHealth['apiLatency'] }}ms</strong>
                    </div>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Risk ops</span>
                        <h2>Production queues</h2>
                    </div>
                </div>
                <div class="metric-list">
                    <div>
                        <span>Listing reviews</span>
                        <strong>{{ $opsReadiness['pendingReviews'] }}</strong>
                    </div>
                    <div>
                        <span>Open reports</span>
                        <strong>{{ $opsReadiness['openReports'] }}</strong>
                    </div>
                    <div>
                        <span>Disputes</span>
                        <strong>{{ $opsReadiness['openDisputes'] }}</strong>
                    </div>
                    <div>
                        <span>Pending payouts</span>
                        <strong>{{ $opsReadiness['pendingPayouts'] }}</strong>
                    </div>
                    <div>
                        <span>Support tickets</span>
                        <strong>{{ $opsReadiness['supportTickets'] }}</strong>
                    </div>
                    <div>
                        <span>Unverified users</span>
                        <strong>{{ $opsReadiness['unverifiedUsers'] }}</strong>
                    </div>
                    <div>
                        <span>Orders in escrow</span>
                        <strong>{{ $opsReadiness['ordersInEscrow'] }}</strong>
                    </div>
                    <div>
                        <span>Held escrows</span>
                        <strong>{{ $opsReadiness['heldEscrows'] }}</strong>
                    </div>
                    <div>
                        <span>Payment risks</span>
                        <strong>{{ $opsReadiness['failedPayments'] }}</strong>
                    </div>
                    <div>
                        <span>Active bookings</span>
                        <strong>{{ $opsReadiness['activeBookings'] }}</strong>
                    </div>
                    <div>
                        <span>Pending KYC</span>
                        <strong>{{ $opsReadiness['pendingKyc'] }}</strong>
                    </div>
                    <div>
                        <span>Webhook failures</span>
                        <strong>{{ $opsReadiness['failedWebhooks'] }}</strong>
                    </div>
                    <div>
                        <span>Risky devices</span>
                        <strong>{{ $opsReadiness['riskyDevices'] }}</strong>
                    </div>
                </div>
            </article>

            <article class="admin-panel chart-card">
                <div class="panel-heading">
                    <h2>Location spread</h2>
                    <small>Listings by region</small>
                </div>
                <canvas class="admin-canvas" data-chart="bars"
                    data-labels='@json($topLocations->keys()->values())'
                    data-values='@json($topLocations->values())'></canvas>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Order status</h2>
                    <a href="{{ route('admin.orders.index') }}">Manage</a>
                </div>
                <div class="metric-list">
                    @forelse ($statusCounts as $status => $total)
                        <div>
                            <span>{{ ucfirst($status) }}</span>
                            <strong>{{ $total }}</strong>
                        </div>
                    @empty
                        <p class="empty-state">No orders in this range.</p>
                    @endforelse
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>User roles</h2>
                    <a href="{{ route('admin.users.index') }}">Manage</a>
                </div>
                <div class="metric-list">
                    @forelse ($roleCounts as $role => $total)
                        <div>
                            <span>{{ ucfirst($role) }}</span>
                            <strong>{{ $total }}</strong>
                        </div>
                    @empty
                        <p class="empty-state">No new users in this range.</p>
                    @endforelse
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Post categories</h2>
                    <a href="{{ route('admin.posts.index') }}">Manage</a>
                </div>
                <div class="metric-list">
                    @forelse ($categoryCounts as $category => $total)
                        <div>
                            <span>{{ ucfirst($category) }}</span>
                            <strong>{{ $total }}</strong>
                        </div>
                    @empty
                        <p class="empty-state">No posts in this range.</p>
                    @endforelse
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Top locations</h2>
                </div>
                <div class="location-drilldown">
                    @forelse ($locationDrilldown as $location)
                        <details>
                            <summary>
                                <span>{{ $location['region'] }}</span>
                                <strong>{{ $location['total'] }}</strong>
                            </summary>
                            <i style="--progress: {{ $location['percent'] }}%"></i>
                            <div>
                                @foreach ($location['cities'] as $city)
                                    <small>{{ $city['name'] }} - {{ $city['post'] }}</small>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <p class="empty-state">No marketplace locations yet.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="admin-pivot-grid">
            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Pivot table</span>
                        <h2>Orders by category and status</h2>
                    </div>
                </div>
                <div class="admin-table-wrap compact">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                @foreach ($orderStatuses as $status => $label)
                                    <th>{{ $label }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orderPivot as $category => $row)
                                <tr>
                                    <td data-label="Category">{{ ucfirst($category) }}</td>
                                    @foreach ($orderStatuses as $status => $label)
                                        <td data-label="{{ $label }}">{{ $row[$status] }}</td>
                                    @endforeach
                                    <td data-label="Total"><strong>{{ $row['total'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Pivot table</span>
                        <h2>Posts by category and mode</h2>
                    </div>
                </div>
                <div class="admin-table-wrap compact">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Sale</th>
                                <th>Rent</th>
                                <th>Featured</th>
                                <th>Available</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($postPivot as $category => $row)
                                <tr>
                                    <td data-label="Category">{{ ucfirst($category) }}</td>
                                    <td data-label="Sale">{{ $row['sale'] }}</td>
                                    <td data-label="Rent">{{ $row['rent'] }}</td>
                                    <td data-label="Featured">{{ $row['featured'] }}</td>
                                    <td data-label="Available">{{ $row['available'] }}</td>
                                    <td data-label="Total"><strong>{{ $row['total'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="admin-ops-grid">
            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Moderation</span>
                        <h2>Posts needing attention</h2>
                    </div>
                    <a href="{{ route('admin.posts.index') }}">All posts</a>
                </div>
                <div class="admin-list action-list">
                    @forelse ($moderationQueue as $post)
                        <div>
                            <strong>{{ $post->title }}</strong>
                            <span>{{ $post->owner->name }} - {{ $post->is_available ? 'Visible' : 'Hidden' }} - {{ $post->is_featured ? 'Featured' : 'Not featured' }}</span>
                            <small>Reason: {{ $post->flagged_reason ?: 'Needs admin review.' }}</small>
                            <div class="admin-row-actions">
                                <form method="POST" action="{{ route('admin.posts.moderate', $post) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.posts.moderate', $post) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit">Reject</button>
                                </form>
                                <a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                            </div>
                        </div>
                    @empty
                        <p class="empty-state">No moderation work right now.</p>
                    @endforelse
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Queue</span>
                        <h2>Pending orders</h2>
                    </div>
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}">All pending</a>
                </div>
                <div class="admin-list action-list">
                    @forelse ($pendingOrders as $order)
                        <div>
                            <strong>{{ $order->marketplaceItem->title }}</strong>
                            <span>{{ $order->user->name }} - {{ $order->contact_email }}</span>
                            <form class="quick-status-form" method="POST" action="{{ route('admin.orders.status', $order) }}">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()">
                                    @foreach ($orderStatuses as $status => $label)
                                        <option value="{{ $status }}" @selected($order->status === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @empty
                        <p class="empty-state">No pending orders.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="admin-recent-grid">
            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Recent users</h2>
                    <a href="{{ route('admin.users.index') }}">View all</a>
                </div>
                <div class="admin-list">
                    @foreach ($recentUsers as $user)
                        <a href="{{ route('admin.users.edit', $user) }}">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }} - {{ ucfirst($user->role) }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Recent posts</h2>
                    <a href="{{ route('admin.posts.index') }}">View all</a>
                </div>
                <div class="admin-list">
                    @foreach ($recentPosts as $post)
                        <a href="{{ route('admin.posts.edit', $post) }}">
                            <strong>{{ $post->title }}</strong>
                            <span>{{ $post->owner->name }} - {{ $post->priceLabel() }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Recent orders</h2>
                    <a href="{{ route('admin.orders.index') }}">View all</a>
                </div>
                <div class="admin-list">
                    @foreach ($recentOrders as $order)
                        <a href="{{ route('admin.orders.edit', $order) }}">
                            <strong>{{ $order->marketplaceItem->title }}</strong>
                            <span>{{ $order->user->name }} - {{ ucfirst($order->status) }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="admin-panel">
                <div class="panel-heading">
                    <h2>Audit trail</h2>
                    <a href="{{ route('admin.audit.index') }}">View all</a>
                </div>
                <div class="admin-list">
                    @foreach ($recentAuditLogs as $log)
                        <a href="{{ route('admin.audit.index', ['q' => $log->action]) }}">
                            <strong>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</strong>
                            <span>{{ $log->summary }} - {{ optional($log->created_at)->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            </article>
        </section>
    </section>
@endsection
