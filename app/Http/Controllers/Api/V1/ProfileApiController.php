<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileApiController extends Controller
{
    public function update(Request $request): UserResource
    {
        $user = $request->user();
        $attributes = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'username' => ['nullable', 'alpha_dash', 'max:80', Rule::unique('users', 'username')->ignore($user->id)],
            'farm_name' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:600'],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['sometimes', 'required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:220'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'non_binary', 'prefer_not_to_say'])],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'theme' => ['nullable', Rule::in(['harvest', 'field', 'sunset', 'night'])],
            'dashboard_range' => ['nullable', Rule::in(['today', '3', '7', '30', 'all'])],
            'notification_email' => ['nullable', 'boolean'],
            'profile_visibility' => ['nullable', Rule::in(['marketplace', 'private'])],
            'share_location' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('email', $attributes) && $attributes['email'] !== $user->email) {
            $attributes['email_verified_at'] = null;
        }

        if (array_key_exists('phone', $attributes) && $attributes['phone'] !== $user->phone) {
            $attributes['phone_verified_at'] = null;
            $attributes['phone_verification_code_hash'] = null;
            $attributes['phone_verification_expires_at'] = null;
            $attributes['phone_verification_attempts'] = 0;
        }

        $user->update($attributes);

        return new UserResource($user->fresh());
    }

    public function password(Request $request): UserResource
    {
        $attributes = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        abort_unless(Hash::check($attributes['current_password'], $request->user()->password), 422, 'Current password is incorrect.');

        $request->user()->update([
            'password' => $attributes['password'],
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        return new UserResource($request->user()->fresh());
    }
}
