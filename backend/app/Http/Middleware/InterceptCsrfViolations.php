<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

class InterceptCsrfViolations
{
    /**
     * Intercept and log cross-site request forgery (CSRF) attempts for threat telemetry.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (TokenMismatchException $e) {
            $user = $request->user();

            SecurityEvent::logAttack(
                attackType: 'csrf_violation',
                payloadSnippet: [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->header('referer'),
                ],
                userId: $user ? $user->id : null,
                blocked: true
            );

            AuditLog::record(
                eventType: 'security.csrf_blocked',
                description: "CSRF token mismatch violation intercepted on route [/{$request->path()}].",
                userId: $user ? $user->id : null,
                severity: 'warning',
                payload: ['method' => $request->method(), 'ip' => $request->ip()]
            );

            throw $e;
        }
    }
}
