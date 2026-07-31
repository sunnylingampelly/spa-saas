<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Shared before rendering so app.blade.php's inline theme-detection script (must run
        // synchronously, pre-paint, to avoid a flash of the wrong theme) can carry a nonce that
        // satisfies a strict script-src with no 'unsafe-inline'.
        $nonce = base64_encode(random_bytes(16));
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        // CSP/HSTS are only enforced in production — a strict CSP breaks Vite's local
        // dev server (HMR over ws://, unbundled module scripts), so it stays off locally.
        if (app()->environment('production')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; ".
                "script-src 'self' 'nonce-{$nonce}' https://checkout.razorpay.com; ".
                "frame-src https://api.razorpay.com https://checkout.razorpay.com; ".
                "connect-src 'self' https://api.razorpay.com; ".
                "img-src 'self' data:; ".
                "style-src 'self' 'unsafe-inline'; ".
                "font-src 'self' data:; ".
                "object-src 'none'; ".
                "base-uri 'self'; ".
                "frame-ancestors 'none';"
            );
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
