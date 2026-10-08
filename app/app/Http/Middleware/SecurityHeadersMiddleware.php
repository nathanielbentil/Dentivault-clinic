<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Inject enterprise banking security headers to protect against XSS, Clickjacking, and MIME-sniffing.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Content Security Policy (CSP): Restricts resource loading to approved origins only
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https:",
            "frame-ancestors 'none'", // Anti-Clickjacking Defense
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // 2. Anti-Clickjacking: Strictly forbid embedding in any frame/iframe
        $response->headers->set('X-Frame-Options', 'DENY');

        // 3. MIME-Sniffing Defense: Prevent browsers from interpreting files as a different MIME type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 4. Referrer Policy: Protect sensitive path information in HTTP referrer headers
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Permissions Policy: Restrict browser hardware APIs
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 6. Cross-Origin Embedder & Opener Policies
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // 7. Strict-Transport-Security (HSTS) if connection is secure
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
