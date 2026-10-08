<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request and enforce granular permission access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissionSlug
     */
    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasPermission($permissionSlug)) {
            return $next($request);
        }

        // Permission Violation
        SecurityEvent::logAttack(
            attackType: 'unauthorized_access',
            payloadSnippet: [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'missing_permission' => $permissionSlug,
                'target_route' => $request->path(),
            ],
            userId: $user->id,
            blocked: true
        );

        AuditLog::record(
            eventType: 'rbac.permission_denied',
            description: "Access denied: User [{$user->email}] lacks permission [{$permissionSlug}] for route [/{$request->path()}].",
            userId: $user->id,
            severity: 'warning'
        );

        abort(Response::HTTP_FORBIDDEN, "Security Exception (403 Forbidden): Missing required permission [{$permissionSlug}].");
    }
}
