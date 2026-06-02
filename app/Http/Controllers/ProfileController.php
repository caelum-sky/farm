<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\KycVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'sessions' => $this->sessions($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'farm_name' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:600'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:220'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'non_binary', 'prefer_not_to_say'])],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'theme' => ['required', Rule::in(['harvest', 'field', 'sunset', 'night'])],
            'dashboard_range' => ['required', Rule::in(['today', '3', '7', '30', 'all'])],
            'notification_email' => ['nullable', 'boolean'],
            'profile_visibility' => ['required', Rule::in(['marketplace', 'private'])],
            'share_location' => ['nullable', 'boolean'],
        ]);

        $emailChanged = $attributes['email'] !== $user->email;
        $phoneChanged = ($attributes['phone'] ?? null) !== $user->phone;
        $attributes['notification_email'] = $request->boolean('notification_email');
        $attributes['share_location'] = $request->boolean('share_location');

        if ($emailChanged) {
            $attributes['email_verified_at'] = null;
        }

        if ($phoneChanged) {
            $attributes['phone_verified_at'] = null;
            $attributes['phone_verification_code_hash'] = null;
            $attributes['phone_verification_expires_at'] = null;
            $attributes['phone_verification_attempts'] = 0;
        }

        $user->update($attributes);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->audit($user->id, 'profile_updated', 'Updated profile settings.');

        return back()->with('status', $emailChanged
            ? 'Profile saved. A new email verification link was sent.'
            : 'Profile saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if (! Hash::check($attributes['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $request->user()->update([
            'password' => $attributes['password'],
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        $this->audit($request->user()->id, 'password_changed', 'Changed account password.');

        return back()->with('status', 'Password updated.');
    }

    public function avatar(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $path = $attributes['avatar']->store('avatars', 'public');

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update(['profile_picture' => $path]);
        $this->audit($user->id, 'avatar_updated', 'Updated profile avatar.');

        return back()->with('status', 'Profile picture updated.');
    }

    public function identity(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'document_type' => ['required', Rule::in(['national_id', 'farm_registration', 'business_registration', 'drivers_license'])],
            'identity_document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1200'],
        ]);

        $user = $request->user();
        $path = $attributes['identity_document']->store('kyc-documents/'.$user->id, 'private');

        $kyc = KycVerification::create([
            'user_id' => $user->id,
            'provider' => 'manual',
            'provider_reference' => 'WEB-KYC-'.$user->id.'-'.now()->format('YmdHis'),
            'status' => 'pending',
            'document_type' => $attributes['document_type'],
            'checks' => ['identity' => 'pending', 'document' => 'pending'],
            'review_notes' => $attributes['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        Document::create([
            'documentable_type' => $kyc->getMorphClass(),
            'documentable_id' => $kyc->id,
            'uploaded_by' => $user->id,
            'type' => $attributes['document_type'],
            'disk' => 'private',
            'path' => $path,
            'status' => 'pending',
            'metadata' => [
                'original_name' => $attributes['identity_document']->getClientOriginalName(),
                'size' => $attributes['identity_document']->getSize(),
                'mime' => $attributes['identity_document']->getMimeType(),
                'source' => 'profile',
            ],
        ]);

        $user->update(['kyc_status' => 'pending']);
        $this->audit($user->id, 'identity_document_uploaded', 'Uploaded validated ID for manual review.');

        return back()->with('status', 'Validated ID uploaded for review.');
    }

    public function sendPhoneOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->phone) {
            return back()->withErrors(['phone' => 'Add a phone number before requesting verification.']);
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'phone_verification_code_hash' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verification_attempts' => 0,
        ])->save();

        AppNotification::create([
            'user_id' => $user->id,
            'channel' => 'sms',
            'type' => 'profile.phone_otp',
            'status' => 'queued',
            'subject' => 'Phone verification code',
            'body' => app()->isProduction()
                ? 'A phone verification code was requested.'
                : 'Demo phone verification code: '.$code,
            'sent_at' => now(),
        ]);

        $message = app()->isProduction()
            ? 'Phone verification code queued for SMS delivery.'
            : 'Demo phone verification code: '.$code;

        return back()->with('status', $message);
    }

    public function verifyPhoneOtp(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user->phone_verification_code_hash || ! $user->phone_verification_expires_at || $user->phone_verification_expires_at->isPast()) {
            return back()->withErrors(['otp_code' => 'The phone verification code has expired. Request a new one.']);
        }

        if ($user->phone_verification_attempts >= 5) {
            return back()->withErrors(['otp_code' => 'Too many attempts. Request a new code.']);
        }

        if (! Hash::check($attributes['otp_code'], $user->phone_verification_code_hash)) {
            $user->increment('phone_verification_attempts');

            return back()->withErrors(['otp_code' => 'That verification code is not correct.']);
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'phone_verification_code_hash' => null,
            'phone_verification_expires_at' => null,
            'phone_verification_attempts' => 0,
        ])->save();

        $this->audit($user->id, 'phone_verified', 'Verified phone number.');

        return back()->with('status', 'Phone number verified.');
    }

    private function sessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [[
                'current' => true,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'last_activity' => now(),
            ]];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->limit(6)
            ->get()
            ->map(fn ($session): array => [
                'current' => $session->id === $request->session()->getId(),
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => now()->setTimestamp((int) $session->last_activity),
            ])
            ->all();
    }

    private function audit(int $userId, string $action, string $summary): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'summary' => $summary,
            'metadata' => ['source' => 'profile'],
        ]);
    }
}
