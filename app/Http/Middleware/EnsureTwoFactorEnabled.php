<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_confirmed_at === null && ! $request->routeIs('admin.two-factor.setup')) {
            return redirect()->route('admin.two-factor.setup');
        }

        return $next($request);
    }
}
