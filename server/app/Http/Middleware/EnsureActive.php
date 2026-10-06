<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivated accounts are signed out on their next request.
 * After a password reset, nothing but changing the password is allowed.
 */
class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return response()->json(['message' => 'Akun kamu tidak aktif. Hubungi atasanmu.'], 401);
        }

        if ($request->user()?->must_change_password && ! $request->routeIs('api.bootstrap', 'api.password', 'api.logout')) {
            return response()->json(['message' => 'Ganti password dulu.'], 403);
        }

        return $next($request);
    }
}
