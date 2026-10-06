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

        return $response;
    }
}
