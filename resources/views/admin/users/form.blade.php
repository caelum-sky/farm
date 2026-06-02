@extends('layouts.app')

@section('title', $managedUser->exists ? 'Edit User' : 'Create User')
@section('body_class', 'admin-page')

@section('content')
    <section class="admin-shell">
        <header class="admin-page-head">
            <div>
                <span class="eyebrow">User editor</span>
                <h1>{{ $managedUser->exists ? 'Edit '.$managedUser->name : 'Create a managed user.' }}</h1>
            </div>
            <a class="button ghost" href="{{ route('admin.users.index') }}">Back to Users</a>
        </header>

        <x-admin.navigation />

        <form class="admin-form" method="POST" action="{{ $managedUser->exists ? route('admin.users.update', $managedUser) : route('admin.users.store') }}" data-loading-form>
            @csrf
            @if ($managedUser->exists)
                @method('PATCH')
            @endif

            <div class="admin-form-grid">
                <label>
                    <span>Full name</span>
                    <input type="text" name="name" value="{{ old('name', $managedUser->name) }}" required>
                </label>
                <label>
                    <span>Username</span>
                    <input type="text" name="username" value="{{ old('username', $managedUser->username) }}" placeholder="admin">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email', $managedUser->email) }}" required>
                </label>
                <label>
                    <span>Farm or business</span>
                    <input type="text" name="farm_name" value="{{ old('farm_name', $managedUser->farm_name) }}">
                </label>
                <label>
                    <span>Public bio</span>
                    <input type="text" name="bio" value="{{ old('bio', $managedUser->bio) }}" placeholder="Short marketplace profile">
                </label>
                <label>
                    <span>Phone</span>
                    <input type="tel" name="phone" value="{{ old('phone', $managedUser->phone) }}">
                </label>
                <label>
                    <span>Location</span>
                    <input type="text" name="location" value="{{ old('location', $managedUser->location) }}" required>
                </label>
                <label>
                    <span>Address</span>
                    <input type="text" name="address" value="{{ old('address', $managedUser->address) }}">
                </label>
                <label>
                    <span>Birthdate</span>
                    <input type="date" name="birthdate" value="{{ old('birthdate', optional($managedUser->birthdate)->format('Y-m-d')) }}">
                </label>
                <label>
                    <span>Gender</span>
                    <select name="gender">
                        <option value="">Prefer not to set</option>
                        <option value="female" @selected(old('gender', $managedUser->gender) === 'female')>Female</option>
                        <option value="male" @selected(old('gender', $managedUser->gender) === 'male')>Male</option>
                        <option value="non_binary" @selected(old('gender', $managedUser->gender) === 'non_binary')>Non-binary</option>
                        <option value="prefer_not_to_say" @selected(old('gender', $managedUser->gender) === 'prefer_not_to_say')>Prefer not to say</option>
                    </select>
                </label>
                <label>
                    <span>Role</span>
                    <select name="role" required>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $managedUser->role ?: 'farmer') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Theme</span>
                    <select name="theme" required>
                        @foreach ($themes as $value => $label)
                            <option value="{{ $value }}" @selected(old('theme', $managedUser->theme ?: 'harvest') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Default analytics range</span>
                    <select name="dashboard_range" required>
                        @foreach ($rangeOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('dashboard_range', $managedUser->dashboard_range ?: '7') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Account status</span>
                    <select name="status">
                        <option value="active" @selected(old('status', $managedUser->status ?: 'active') === 'active')>Active</option>
                        <option value="suspended" @selected(old('status', $managedUser->status) === 'suspended')>Suspended</option>
                        <option value="closed" @selected(old('status', $managedUser->status) === 'closed')>Closed</option>
                    </select>
                </label>
                <label>
                    <span>KYC status</span>
                    <select name="kyc_status">
                        <option value="unverified" @selected(old('kyc_status', $managedUser->kyc_status ?: 'unverified') === 'unverified')>Unverified</option>
                        <option value="pending" @selected(old('kyc_status', $managedUser->kyc_status) === 'pending')>Pending</option>
                        <option value="verified" @selected(old('kyc_status', $managedUser->kyc_status) === 'verified')>Verified</option>
                        <option value="rejected" @selected(old('kyc_status', $managedUser->kyc_status) === 'rejected')>Rejected</option>
                    </select>
                </label>
                <label>
                    <span>Risk score</span>
                    <input type="number" name="risk_score" min="0" max="100" value="{{ old('risk_score', $managedUser->risk_score ?? 0) }}">
                </label>
                <label class="check-line">
                    <input type="checkbox" name="notification_email" value="1" @checked(old('notification_email', $managedUser->notification_email ?? true))>
                    <span>Email notifications</span>
                </label>
                <label>
                    <span>Profile visibility</span>
                    <select name="profile_visibility">
                        <option value="marketplace" @selected(old('profile_visibility', $managedUser->profile_visibility ?: 'marketplace') === 'marketplace')>Marketplace visible</option>
                        <option value="private" @selected(old('profile_visibility', $managedUser->profile_visibility) === 'private')>Private</option>
                    </select>
                </label>
                <label class="check-line">
                    <input type="checkbox" name="share_location" value="1" @checked(old('share_location', $managedUser->share_location ?? true))>
                    <span>Share region</span>
                </label>
            </div>

            <label>
                <span>Profile notes</span>
                <textarea name="profile_notes" rows="4">{{ old('profile_notes', $managedUser->profile_notes) }}</textarea>
            </label>

            <div class="admin-form-grid">
                <label>
                    <span>{{ $managedUser->exists ? 'New password' : 'Password' }}</span>
                    <input type="password" name="password" autocomplete="new-password" @required(! $managedUser->exists)>
                </label>
                <label>
                    <span>Confirm password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" @required(! $managedUser->exists)>
                </label>
            </div>

            <button class="button primary full" type="submit" data-loading-label="Saving...">{{ $managedUser->exists ? 'Save User' : 'Create User' }}</button>
        </form>
    </section>
@endsection
