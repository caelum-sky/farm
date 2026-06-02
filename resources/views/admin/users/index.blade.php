@extends('layouts.app')

@section('title', 'Manage Users')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">User management</span>
                <h1>Accounts, roles, themes, and access.</h1>
            </div>
            <div class="admin-head-actions">
                <a class="button ghost" href="{{ route('admin.export', 'users') }}">Export CSV</a>
                <a class="button primary" href="{{ route('admin.users.create') }}">Create User</a>
            </div>
        </header>

        <x-admin.navigation />

        <form class="admin-filter" method="GET" action="{{ route('admin.users.index') }}">
            <label>
                <span>Search</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, username, email">
            </label>
            <label>
                <span>Role</span>
                <select name="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button ghost" type="button" data-filter-toggle>Advanced</button>
            <button class="button primary" type="submit">Filter</button>
        </form>

        <aside class="filter-drawer" data-filter-drawer aria-hidden="true">
            <form method="GET" action="{{ route('admin.users.index') }}">
                <div class="drawer-heading">
                    <div>
                        <span class="eyebrow">Advanced filters</span>
                        <h2>User segments</h2>
                    </div>
                    <button type="button" data-filter-close aria-label="Close filters">Close</button>
                </div>
                <label>
                    <span>Date joined from</span>
                    <input type="date" name="joined_from" value="{{ request('joined_from') }}">
                </label>
                <label>
                    <span>Date joined to</span>
                    <input type="date" name="joined_to" value="{{ request('joined_to') }}">
                </label>
                <label>
                    <span>Verification status</span>
                    <select name="verification">
                        <option value="">Any status</option>
                        <option value="verified" @selected(request('verification') === 'verified')>Verified</option>
                        <option value="unverified" @selected(request('verification') === 'unverified')>Unverified</option>
                    </select>
                </label>
                <label>
                    <span>Region</span>
                    <select name="region">
                        <option value="">All regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
                        @endforeach
                    </select>
                </label>
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="role" value="{{ request('role') }}">
                <button class="button primary full" type="submit">Apply Advanced Filters</button>
            </form>
        </aside>

        <form class="bulk-actions" method="POST" action="{{ route('admin.users.bulk') }}" data-bulk-form data-confirm="Apply this bulk action to the selected users?">
            @csrf
            <label>
                <span>Bulk action</span>
                <select name="action" required>
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
                        <th><input type="checkbox" data-bulk-select-all aria-label="Select all users"></th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Verification</th>
                        <th>Joined</th>
                        <th>Posts</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td data-label="Select"><input type="checkbox" class="bulk-checkbox" value="{{ $user->id }}" aria-label="Select {{ $user->name }}"></td>
                            <td data-label="User">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->username ? '@'.$user->username.' - ' : '' }}{{ $user->email }}</span>
                            </td>
                            <td data-label="Role">{{ ucfirst($user->role) }}</td>
                            <td data-label="Location">{{ $user->location }}</td>
                            <td data-label="Verification">
                                <span class="status-pill {{ $user->email_verified_at ? 'is-good' : 'is-warm' }}">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</span>
                            </td>
                            <td data-label="Joined">{{ $user->created_at->format('M d, Y') }}</td>
                            <td data-label="Posts">{{ $user->marketplace_items_count }}</td>
                            <td data-label="Orders">{{ $user->inquiries_count }}</td>
                            <td data-label="Actions">
                                <div class="admin-row-actions">
                                    <a href="{{ route('admin.users.edit', $user) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" aria-label="Change role for {{ $user->name }}">
                                            @foreach ($roles as $value => $label)
                                                <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete this user and all related posts/orders?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $users->links() }}</div>
    </section>
@endsection
