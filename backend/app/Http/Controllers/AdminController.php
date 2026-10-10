<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display the SuperAdmin executive dashboard.
     */
    public function index(): View|JsonResponse
    {
        $users = User::with('roles')->latest('id')->paginate(15);
        $roles = Role::all();
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'locked_users' => User::where('status', 'locked')->count(),
            'audit_events_count' => AuditLog::count(),
        ];

        if (request()->wantsJson()) {
            return response()->json([
                'stats' => $stats,
                'users' => $users,
                'roles' => $roles,
            ]);
        }

        return view('admin.dashboard', compact('users', 'roles', 'stats'));
    }

    /**
     * Update a user's role assignment.
     */
    public function assignRole(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($request->role_id);
        $user->roles()->sync([$role->id => ['assigned_by' => $request->user()->id, 'created_at' => now()]]);

        AuditLog::record(
            eventType: 'rbac.role_assigned',
            description: "SuperAdmin [{$request->user()->email}] assigned role [{$role->name}] to user [{$user->email}].",
            userId: $request->user()->id,
            severity: 'notice',
            payload: ['target_user' => $user->email, 'new_role' => $role->name]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => "Role [{$role->name}] assigned successfully."]);
        }

        return back()->with('status', "Role updated to {$role->name}.");
    }

    /**
     * Delete a staff user account permanently.
     */
    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        // 1. Prevent admin from deleting their own active account
        if ($user->id === $request->user()->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Security Policy: You cannot delete your own active administrator account.'], 403);
            }
            return back()->withErrors(['error' => 'Security Policy: You cannot delete your own active administrator account.']);
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $userId = $user->id;

        // 2. Revoke all personal access tokens and role associations
        $user->tokens()->delete();
        $user->roles()->detach();
        $user->delete();

        // 3. Record tamper-evident audit log
        AuditLog::record(
            eventType: 'user.deleted',
            description: "Chief Admin [{$request->user()->email}] permanently deleted staff account [{$userEmail}] (Name: {$userName}).",
            userId: $request->user()->id,
            severity: 'warning',
            payload: [
                'deleted_user_id' => $userId,
                'deleted_user_email' => $userEmail,
                'deleted_user_name' => $userName,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => "Staff account for [{$userName}] ({$userEmail}) was successfully deleted."]);
        }

        return back()->with('status', "Staff account for [{$userName}] ({$userEmail}) was permanently deleted.");
    }

    /**
     * Display the 3-2-1 Backup & Disaster Recovery management dashboard.
     */
    public function backups(): View
    {
        $backupLogs = \App\Models\BackupLog::latest('id')->paginate(15);
        $localBackups = \App\Services\CloudBackupService::listCloudBackups();
        
        return view('admin.backups', compact('backupLogs', 'localBackups'));
    }

    /**
     * Trigger an on-demand encrypted backup & cloud replication.
     */
    public function triggerBackup(): RedirectResponse
    {
        try {
            $local = \App\Services\BackupService::createLocalBackup();
            \App\Services\CloudBackupService::uploadToCloud($local['filepath']);
            return back()->with('status', "Encrypted backup [{$local['filename']}] generated and synced to cloud storage!");
        } catch (\Throwable $e) {
            return back()->withErrors(['backup_error' => "Backup execution failed: " . $e->getMessage()]);
        }
    }

    /**
     * Trigger an automated Disaster Recovery restore drill.
     */
    public function triggerDrill(): RedirectResponse
    {
        try {
            $result = \App\Services\DisasterRecoveryService::runDrill();
            return back()->with('status', "Disaster Recovery drill completed with 100% integrity! RTO: {$result['total_rto_ms']} ms | RPO: {$result['rpo_status']}");
        } catch (\Throwable $e) {
            return back()->withErrors(['drill_error' => "DR Drill failed: " . $e->getMessage()]);
        }
    }
}
