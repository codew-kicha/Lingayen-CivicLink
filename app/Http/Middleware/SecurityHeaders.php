<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser protections on every web response. No Content-Security-Policy yet: Alpine's
 * inline expressions and the OCR libraries loaded from jsDelivr would need a nonce-based policy,
 * which is deployment work (Milestone 2 Phase 8).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');           // no clickjacking via iframes
        $response->headers->set('X-Content-Type-Options', 'nosniff');       // uploads can't be sniffed into scripts
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Don't advertise the PHP version: scanners use it to pick targets (PHP2Shell, October 2026).
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        // Over HTTPS on the live site, browsers must keep using HTTPS for a year.
        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
