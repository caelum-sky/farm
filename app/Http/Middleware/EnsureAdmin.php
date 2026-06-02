<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();
        abort_unless($admin?->isAdmin() && $admin->isActive(), 403);

        $allowedIps = array_filter(array_map('trim', explode(',', (string) env('ADMIN_ALLOWED_IPS', ''))));

        if ($allowedIps !== [] && ! in_array((string) $request->ip(), $allowedIps, true)) {
            abort(403, 'This admin session is not allowed from the current IP address.');
        }

        abort_if($admin->force_password_change, 403, 'Admin password must be changed before continuing.');

        return $next($request);
    }
}
