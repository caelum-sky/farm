<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        $user = User::query()
            ->where('api_token_hash', hash('sha256', $token))
            ->first();

        if (! $user || ! $user->isActive()) {
            return response()->json(['message' => 'Invalid or inactive API token.'], 401);
        }

        $ttlMinutes = (int) config('farmbridge_security.api_token_ttl_minutes', 1440);

        if ($ttlMinutes > 0 && (! $user->api_token_created_at || $user->api_token_created_at->lt(now()->subMinutes($ttlMinutes)))) {
            $user->forceFill([
                'api_token_hash' => null,
                'api_token_created_at' => null,
            ])->save();

            return response()->json(['message' => 'API token expired. Please sign in again.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn (): User => $user);

        $fingerprint = $request->header('X-Device-Fingerprint')
            ?: hash('sha256', ($request->ip() ?? 'unknown').'|'.($request->userAgent() ?? 'unknown'));

        Device::updateOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'user_id' => $user->id,
                'platform' => $request->header('X-Device-Platform'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'risk_score' => $request->header('X-Device-Fingerprint') ? 0 : 10,
                'last_seen_at' => now(),
            ],
        );

        return $next($request);
    }
}
