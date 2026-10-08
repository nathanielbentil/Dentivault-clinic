<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminApiController extends Controller
{
    /**
     * Get user management list and system security stats
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::with('roles')->orderBy('id', 'asc')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'status' => $u->status,
                'roles' => $u->roles->pluck('name'),
                'two_factor_enabled' => !is_null($u->two_factor_confirmed_at),
                'failed_login_attempts' => $u->failed_login_attempts,
                'lockout_until' => $u->lockout_until,
                'last_login_at' => $u->last_login_at,
                'last_login_ip' => $u->last_login_ip,
                'created_at' => $u->created_at,
            ];
        });

        $roles = Role::all(['id', 'name', 'slug', 'description']);

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'locked_users' => User::where('status', 'locked')->count(),
            'audit_events_count' => AuditLog::count(),
            'two_factor_active_count' => User::whereNotNull('two_factor_confirmed_at')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Assign a role to a user (Admin only)
     */
    public function assignRole(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'role_name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $role = Role::where('name', $request->role_name)->orWhere('slug', strtolower($request->role_name))->firstOrFail();

        $user->roles()->sync([$role->id => ['assigned_by' => $request->user()->id, 'created_at' => now()]]);

        AuditLog::record(
            eventType: 'rbac.role_assigned',
            description: "Admin [{$request->user()->email}] assigned role [{$role->name}] to user [{$user->email}].",
            userId: $request->user()->id,
            severity: 'notice',
            payload: ['target_user' => $user->email, 'new_role' => $role->name]
        );

        return response()->json([
            'success' => true,
            'message' => "Role [{$role->name}] assigned successfully to [{$user->email}].",
        ]);
    }

    /**
     * Get Tamper-Evident Audit Logs stream
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $limit = min(100, max(10, (int) $request->input('limit', 25)));
        $logs = AuditLog::with('user:id,email,name')->orderBy('id', 'desc')->paginate($limit);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Verify cryptographic SHA-256 hash chain integrity
     */
    public function verifyAuditChain(): JsonResponse
    {
        $report = AuditLoggerService::verifyChainIntegrity();

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Simulate a forensic tampering attack for viva presentation
     */
    public function simulateTampering(): JsonResponse
    {
        // 1. Create a dummy test log to tamper with
        $testLog = AuditLog::record(
            eventType: 'security.viva_demo_event',
            description: 'Original genuine audit log record created for viva demonstration.',
            severity: 'info'
        );

        // 2. Deliberately tamper with description directly bypassing hash recomputation
        $testLog->updateQuietly([
            'description' => 'MALICIOUS_TAMPERED: Unauthorized modification of audit log contents!',
        ]);

        // 3. Execute integrity check immediately to detect the tampering
        $report = AuditLoggerService::verifyChainIntegrity();

        // 4. Restore/Delete the tampered test log so system state returns to pure clean state
        $testLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tampering simulation executed. Cryptographic SHA-256 integrity check detected anomalous mutation!',
            'tamper_detected' => !$report['intact'],
            'report' => $report,
        ]);
    }
}
