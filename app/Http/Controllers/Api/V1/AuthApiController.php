<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $attributes['email'])->first();

        if (! $user || ! Hash::check($attributes['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if (! $user->isActive()) {
            return response()->json(['message' => 'This account is not active.'], 403);
        }

        $plainToken = Str::random(64);
        $user->forceFill([
            'api_token_hash' => hash('sha256', $plainToken),
            'api_token_created_at' => now(),
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'expires_at' => now()
                ->addMinutes((int) config('farmbridge_security.api_token_ttl_minutes', 1440))
                ->toISOString(),
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill([
            'api_token_hash' => null,
            'api_token_created_at' => null,
        ])->save();

        return response()->json(['message' => 'Token revoked.']);
    }
}
