@extends('layouts.app')

@section('title', 'Admin Settings')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">Admin settings</span>
                <h1>Profile, password, theme, and website controls.</h1>
            </div>
            <a class="button ghost" href="{{ route('admin.dashboard') }}">Back to Overview</a>
        </header>

        <x-admin.navigation />

        <form class="admin-form settings-form" method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PATCH')

            <section class="settings-section">
                <div>
                    <span class="eyebrow">Account</span>
                    <h2>Admin identity</h2>
                </div>
                <div class="admin-form-grid">
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name', $admin->name) }}" required>
                    </label>
                    <label>
                        <span>Username</span>
                        <input type="text" name="username" value="{{ old('username', $admin->username) }}">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', $admin->email) }}" required>
                    </label>
                    <label>
                        <span>Operations name</span>
                        <input type="text" name="farm_name" value="{{ old('farm_name', $admin->farm_name) }}">
                    </label>
                    <label>
                        <span>Phone</span>
                        <input type="tel" name="phone" value="{{ old('phone', $admin->phone) }}">
                    </label>
                    <label>
                        <span>Location</span>
                        <input type="text" name="location" value="{{ old('location', $admin->location) }}" required>
                    </label>
                    <label>
                        <span>Theme</span>
                        <select name="theme" data-theme-preview required>
                            @foreach ($themes as $value => $label)
                                <option value="{{ $value }}" @selected(old('theme', $admin->theme ?: 'harvest') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Default analytics range</span>
                        <select name="dashboard_range" required>
                            @foreach ($rangeOptions as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('dashboard_range', $admin->dashboard_range ?: '7') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="notification_email" value="1" @checked(old('notification_email', $admin->notification_email))>
                        <span>Email notifications</span>
                    </label>
                </div>
                <label>
                    <span>Profile notes</span>
                    <textarea name="profile_notes" rows="4">{{ old('profile_notes', $admin->profile_notes) }}</textarea>
                </label>
            </section>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">Security</span>
                    <h2>Change password</h2>
                </div>
                <div class="admin-form-grid">
                    <label>
                        <span>New password</span>
                        <input type="password" name="password" autocomplete="new-password">
                    </label>
                    <label>
                        <span>Confirm password</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password">
                    </label>
                </div>
            </section>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">Website</span>
                    <h2>Landing page controls</h2>
                </div>
                <label>
                    <span>Homepage headline</span>
                    <input type="text" name="homepage_headline" value="{{ old('homepage_headline', $siteSettings['homepage_headline']) }}" required>
                </label>
                <label>
                    <span>Homepage copy</span>
                    <textarea name="homepage_copy" rows="4" required>{{ old('homepage_copy', $siteSettings['homepage_copy']) }}</textarea>
                </label>
                <div class="admin-form-grid">
                    <label>
                        <span>Announcement</span>
                        <input type="text" name="site_announcement" value="{{ old('site_announcement', $siteSettings['site_announcement']) }}">
                    </label>
                    <label>
                        <span>Marketplace status</span>
                        <select name="marketplace_status" required>
                            <option value="open" @selected(old('marketplace_status', $siteSettings['marketplace_status']) === 'open')>Open</option>
                            <option value="limited" @selected(old('marketplace_status', $siteSettings['marketplace_status']) === 'limited')>Limited</option>
                            <option value="paused" @selected(old('marketplace_status', $siteSettings['marketplace_status']) === 'paused')>Paused</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="settings-section">
                <div>
                    <span class="eyebrow">System configuration</span>
                    <h2>Marketplace operations</h2>
                </div>
                <div class="admin-form-grid">
                    <label class="check-line">
                        <input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $siteSettings['maintenance_mode']) === '1')>
                        <span>Enable maintenance mode</span>
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="orders_enabled" value="1" @checked(old('orders_enabled', $siteSettings['orders_enabled']) === '1')>
                        <span>Enable order inquiries</span>
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="rentals_enabled" value="1" @checked(old('rentals_enabled', $siteSettings['rentals_enabled']) === '1')>
                        <span>Enable rentals</span>
                    </label>
                    <label>
                        <span>Global tax rate (%)</span>
                        <input type="number" name="global_tax_rate" min="0" max="100" step="0.01" value="{{ old('global_tax_rate', $siteSettings['global_tax_rate']) }}" required>
                    </label>
                    <label>
                        <span>Platform fee rate (%)</span>
                        <input type="number" name="platform_fee_rate" min="0" max="100" step="0.01" value="{{ old('platform_fee_rate', $siteSettings['platform_fee_rate']) }}" required>
                    </label>
                    <label>
                        <span>Max active requests per user</span>
                        <input type="number" name="max_active_inquiries_per_user" min="1" max="500" value="{{ old('max_active_inquiries_per_user', $siteSettings['max_active_inquiries_per_user']) }}" required>
                    </label>
                    <label>
                        <span>Contact email alias</span>
                        <input type="email" name="support_email" value="{{ old('support_email', $siteSettings['support_email']) }}" required>
                    </label>
                    <label>
                        <span>Auto-flag keywords</span>
                        <input type="text" name="flagged_keywords" value="{{ old('flagged_keywords', $siteSettings['flagged_keywords']) }}">
                    </label>
                    <label class="check-line">
                        <input type="checkbox" name="listing_review_required" value="1" @checked(old('listing_review_required', $siteSettings['listing_review_required']) === '1')>
                        <span>Require admin review before new user listings go live</span>
                    </label>
                </div>
            </section>

            <button class="button primary full" type="submit">Save Admin and Website Settings</button>
        </form>
    </section>
@endsection
