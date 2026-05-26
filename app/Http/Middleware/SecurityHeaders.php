<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds production-grade HTTP security headers.
 *
 * Why: tourism ERP handles sensitive PII (passports, IDs). Without these
 * headers, the app is vulnerable to clickjacking, XSS, MIME-sniffing,
 * and protocol-downgrade attacks.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Block iframe embedding (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Block MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Don't leak referrer info to other origins
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable powerful APIs we don't use
        $response->headers->set('Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), accelerometer=()');

        // Force HTTPS in production (HSTS — 1 year, include subdomains)
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content Security Policy — allow only the CDNs we use
        // NOTE: still allows inline scripts/styles because the AdminLTE-style
        // templates rely on inline event handlers. Tighten later by moving
        // inline JS to external files + nonces.
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.datatables.net https://code.jquery.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https: http:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // Remove fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
