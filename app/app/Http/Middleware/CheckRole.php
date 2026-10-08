<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request and enforce strict Role-Based Access Control (RBAC).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has any of the authorized roles
        if ($user->hasRole($roles)) {
            return $next($request);
        }

        // Privilege Escalation / Unauthorized Access Intercepted
        $userRoleList = $user->roles->pluck('name')->implode(', ') ?: 'No Role';
        $requiredRoles = implode(', ', $roles);

        SecurityEvent::logAttack(
            attackType: 'unauthorized_access',
            payloadSnippet: [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $userRoleList,
                'required_roles' => $requiredRoles,
                'target_route' => $request->path(),
            ],
            userId: $user->id,
            blocked: true
        );

        AuditLog::record(
            eventType: 'rbac.access_denied',
            description: "Unauthorized access attempt: User [{$user->email}] ({$userRoleList}) attempted accessing role-restricted route [/{$request->path()}]. Required: [{$requiredRoles}].",
            userId: $user->id,
            severity: 'warning',
            payload: ['target' => $request->path(), 'required_roles' => $roles]
        );

        abort(Response::HTTP_FORBIDDEN, 'Security Exception (403 Forbidden): Your account role does not have authorization to access this banking resource.');
    }
}
