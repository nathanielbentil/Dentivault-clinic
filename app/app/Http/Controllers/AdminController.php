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
}
