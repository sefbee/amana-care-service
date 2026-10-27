<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(18));
        $request->attributes->set('csp_nonce', $nonce);
        Vite::useCspNonce($nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if (str_contains(strtolower((string) $response->headers->get('Content-Type')), 'text/html')) {
            $policy = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "script-src-attr 'none'",
                "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com",
                "style-src-attr 'unsafe-inline'",
                "img-src 'self' data: blob: https:",
                "media-src 'self' blob: https:",
                "font-src 'self' data: https://fonts.gstatic.com",
                "connect-src 'self'",
                "frame-src 'self'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
            ]);

            $response->headers->set('Content-Security-Policy', $policy);
        }

        return $response;
    }
}
