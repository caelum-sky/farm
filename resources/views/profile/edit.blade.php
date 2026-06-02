@extends('layouts.app')

@section('title', 'Profile Settings')
@section('body_class', 'profile-page')

@section('content')
    <section class="dashboard-hero profile-hero">
        <div>
            <span class="eyebrow">Profile settings</span>
            <h1>Manage account, security, and preferences.</h1>
            <p>Keep verification, contact details, privacy, and marketplace settings current.</p>
            <div class="trust-row dashboard-role-tags" aria-label="Verification status">
                <span>{{ $user->hasVerifiedEmail() ? 'Email verified' : 'Email unverified' }}</span>
                <span>{{ $user->phone_verified_at ? 'Phone verified' : 'Phone unverified' }}</span>
                <span>{{ ucfirst($user->theme) }} theme</span>
            </div>
        </div>
        <a class="button ghost" href="{{ route('dashboard') }}">Back to Dashboard</a>
    </section>

    <section class="profile-grid">
        <x-ui.panel class="dashboard-panel profile-card" title="Profile details">
            <form method="POST" action="{{ route('profile.update') }}" data-loading-form>
                @csrf
                @method('PATCH')

                <div class="profile-summary">
                    <div class="avatar-preview" aria-hidden="true">
                        @if ($user->profile_picture)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($user->profile_picture) }}" alt="">
                        @else
                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <span>{{ $user->email }}</span>
                    </div>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Full name</span>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                    </label>
                    <label>
                        <span>Username</span>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" placeholder="farmbridge-user">
                    </label>
                </div>

                <label>
                    <span>Farm or business</span>
                    <input type="text" name="farm_name" value="{{ old('farm_name', $user->farm_name) }}">
                </label>

                <label>
                    <span>Bio</span>
                    <textarea name="bio" rows="4" maxlength="600" placeholder="Short marketplace profile for buyers and partners.">{{ old('bio', $user->bio) }}</textarea>
                </label>

                <div class="form-grid">
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                    </label>
                    <label>
                        <span>Phone</span>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Location</span>
                        <input type="text" name="location" value="{{ old('location', $user->location) }}" autocomplete="address-level2" required>
                    </label>
                    <label>
                        <span>Address</span>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}" autocomplete="street-address">
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Gender</span>
                        <select name="gender">
                            <option value="">Prefer not to set</option>
                            <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
                            <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
                            <option value="non_binary" @selected(old('gender', $user->gender) === 'non_binary')>Non-binary</option>
                            <option value="prefer_not_to_say" @selected(old('gender', $user->gender) === 'prefer_not_to_say')>Prefer not to say</option>
                        </select>
                    </label>
                    <label>
                        <span>Birthdate</span>
                        <input type="date" name="birthdate" value="{{ old('birthdate', optional($user->birthdate)->format('Y-m-d')) }}">
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Theme</span>
                        <select name="theme">
                            @foreach (['harvest' => 'Harvest', 'field' => 'Field', 'sunset' => 'Sunset', 'night' => 'Night'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('theme', $user->theme) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Default analytics range</span>
                        <select name="dashboard_range">
                            @foreach (['today' => 'Today', '3' => '3 days', '7' => '7 days', '30' => '30 days', 'all' => 'All time'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('dashboard_range', $user->dashboard_range ?: '7') === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Profile visibility</span>
                        <select name="profile_visibility">
                            <option value="marketplace" @selected(old('profile_visibility', $user->profile_visibility) === 'marketplace')>Marketplace visible</option>
                            <option value="private" @selected(old('profile_visibility', $user->profile_visibility) === 'private')>Private</option>
                        </select>
                    </label>
                    <div class="settings-toggles">
                        <label class="check-line">
                            <input type="checkbox" name="notification_email" value="1" @checked(old('notification_email', $user->notification_email))>
                            <span>Email notifications</span>
                        </label>
                        <label class="check-line">
                            <input type="checkbox" name="share_location" value="1" @checked(old('share_location', $user->share_location ?? true))>
                            <span>Share region on listings</span>
                        </label>
                    </div>
                </div>

                <button class="button primary full" type="submit" data-loading-label="Saving...">Save Profile</button>
            </form>
        </x-ui.panel>

        <div class="profile-side">
            <x-ui.panel class="dashboard-panel" title="Verification">
                <div class="security-stack">
                    <article>
                        <strong>Email</strong>
                        <span>{{ $user->hasVerifiedEmail() ? 'Verified' : 'Needs verification' }}</span>
                        @unless ($user->hasVerifiedEmail())
                            <form method="POST" action="{{ route('verification.send') }}" data-loading-form>
                                @csrf
                                <button class="button ghost full" type="submit" data-loading-label="Sending...">Send Verification Email</button>
                            </form>
                        @endunless
                    </article>
                    <article>
                        <strong>Phone OTP</strong>
                        <span>{{ $user->phone_verified_at ? 'Verified' : 'Not verified' }}</span>
                        <form method="POST" action="{{ route('profile.phone.otp') }}" data-loading-form>
                            @csrf
                            <button class="button ghost full" type="submit" data-loading-label="Sending...">Send OTP</button>
                        </form>
                        <form method="POST" action="{{ route('profile.phone.verify') }}" data-loading-form>
                            @csrf
                            <label>
                                <span>6-digit code</span>
                                <input type="text" name="otp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code">
                            </label>
                            <button class="button primary full" type="submit" data-loading-label="Verifying...">Verify Phone</button>
                        </form>
                    </article>
                </div>
            </x-ui.panel>

            <x-ui.panel class="dashboard-panel" title="Profile picture">
                <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" data-loading-form>
                    @csrf
                    <label>
                        <span>Upload avatar</span>
                        <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" required>
                    </label>
                    <p class="form-alt">PNG, JPG, or WebP up to 2 MB.</p>
                    <button class="button primary full" type="submit" data-loading-label="Uploading...">Upload Picture</button>
                </form>
            </x-ui.panel>

            <x-ui.panel class="dashboard-panel" title="Validated ID">
                <form method="POST" action="{{ route('profile.identity') }}" enctype="multipart/form-data" data-loading-form>
                    @csrf
                    <label>
                        <span>ID type</span>
                        <select name="document_type" required>
                            <option value="national_id">National ID</option>
                            <option value="farm_registration">Farm registration</option>
                            <option value="business_registration">Business registration</option>
                            <option value="drivers_license">Driver's license</option>
                        </select>
                    </label>
                    <label>
                        <span>Validated ID file</span>
                        <input type="file" name="identity_document" accept="image/png,image/jpeg,image/webp,application/pdf" required>
                    </label>
                    <label>
                        <span>Review notes</span>
                        <textarea name="notes" rows="3" placeholder="Optional context for verification reviewers.">{{ old('notes') }}</textarea>
                    </label>
                    <p class="form-alt">Upload a clear ID or registration document. PDF, JPG, PNG, or WebP up to 5 MB.</p>
                    <button class="button primary full" type="submit" data-loading-label="Uploading...">Upload Validated ID</button>
                </form>
            </x-ui.panel>

            <x-ui.panel class="dashboard-panel" title="Change password">
                <form method="POST" action="{{ route('profile.password') }}" data-signup-form data-loading-form>
                    @csrf
                    @method('PATCH')
                    <label>
                        <span>Current password</span>
                        <input type="password" name="current_password" autocomplete="current-password" required>
                    </label>
                    <label>
                        <span>New password</span>
                        <input type="password" name="password" autocomplete="new-password" required data-password aria-describedby="profile-password-hint">
                    </label>
                    <label>
                        <span>Confirm password</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password" required data-password-confirm aria-describedby="profile-password-hint">
                    </label>
                    <p class="password-hint" id="profile-password-hint" data-password-hint>Use at least 8 characters.</p>
                    <button class="button primary full" type="submit" data-loading-label="Updating...">Update Password</button>
                </form>
            </x-ui.panel>

            <x-ui.panel class="dashboard-panel" title="Session activity">
                <div class="session-list">
                    @foreach ($sessions as $session)
                        <article>
                            <strong>{{ $session['current'] ? 'Current session' : 'Recent session' }}</strong>
                            <span>{{ $session['ip_address'] ?: 'Unknown IP' }}</span>
                            <small>{{ \Illuminate\Support\Str::limit($session['user_agent'] ?: 'Unknown device', 74) }}</small>
                            <small>{{ $session['last_activity']->diffForHumans() }}</small>
                        </article>
                    @endforeach
                </div>
            </x-ui.panel>
        </div>
    </section>
@endsection
