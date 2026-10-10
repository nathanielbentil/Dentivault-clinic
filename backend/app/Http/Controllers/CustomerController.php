<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display the authenticated user's security and profile overview.
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $accountData = [
            'user_id' => 'USR-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'account_status' => ucfirst($user->status),
            'security_level' => $user->two_factor_confirmed_at ? 'Tier 2 (2FA Protected)' : 'Tier 1 (Password Only)',
            'recent_logins' => $user->auditLogs()->whereIn('event_type', ['auth.login', 'auth.2fa_success'])->latest('id')->take(5)->get(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'two_factor_active' => !is_null($user->two_factor_confirmed_at),
                ],
                'account' => $accountData,
            ]);
        }

        $patients = \App\Models\Patient::latest()->get();
        $totalPatients = \App\Models\Patient::count();
        $activeTreatments = \App\Models\Patient::where('status', 'In Progress')->count();
        $completedCases = \App\Models\Patient::where('status', 'Completed')->count();
        $totalStaff = \App\Models\User::count();

        return view('dashboard', compact('user', 'accountData', 'patients', 'totalPatients', 'activeTreatments', 'completedCases', 'totalStaff'));
    }

    /**
     * Update staff profile name and email (Admin Only).
     */
    public function updateName(Request $request)
    {
        $currentUser = $request->user();
        if (!$currentUser->hasRole(['Admin', 'SuperAdmin'])) {
            return back()->withErrors(['name' => 'Access Restricted: Only Chief Administrator (Dr. Prof. Nathaniel Bentil) is authorized to modify other staff identities.']);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'target_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $targetUser = $request->filled('target_user_id') 
            ? \App\Models\User::findOrFail($request->target_user_id) 
            : $currentUser;

        $oldName = $targetUser->name;
        $oldEmail = $targetUser->email;

        $targetUser->name = $request->name;
        if ($request->filled('email') && $request->email !== $targetUser->email) {
            $request->validate(['email' => 'unique:users,email,' . $targetUser->id]);
            $targetUser->email = $request->email;
        }
        $targetUser->save();

        \App\Models\AuditLog::record(
            eventType: 'auth.profile_updated',
            description: "Administrator [{$currentUser->email}] updated staff profile for [{$oldEmail}] (Name: [{$targetUser->name}], Email: [{$targetUser->email}]).",
            userId: $currentUser->id,
            severity: 'notice',
            payload: ['target_user' => $targetUser->email, 'old_name' => $oldName, 'new_name' => $targetUser->name, 'old_email' => $oldEmail, 'new_email' => $targetUser->email]
        );

        return back()->with('status', "Staff profile updated successfully for '{$targetUser->name}'!");
    }

    /**
     * Update own staff email address (Available to all staff members).
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['required', 'string'],
        ]);

        // Security authorization check with Argon2id + HMAC Pepper
        if (!\App\Services\PasswordService::verify($request->current_password, $user->password, $user->pepper_version)) {
            return back()->withErrors(['current_password' => 'Security Verification Failed: The provided current password does not match our records.']);
        }

        $oldEmail = $user->email;
        $user->email = $request->email;
        $user->save();

        \App\Models\AuditLog::record(
            eventType: 'auth.email_updated',
            description: "Staff member [{$user->name}] updated login email from [{$oldEmail}] to [{$user->email}].",
            userId: $user->id,
            severity: 'notice',
            payload: ['old_email' => $oldEmail, 'new_email' => $user->email]
        );

        return back()->with('status', "Your login email has been updated successfully to '{$user->email}'!");
    }

    /**
     * Update user master password with NIST SP 800-63B validation and Argon2id re-hashing.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = $request->user();

        // Verify current password
        if (!\App\Services\PasswordService::verify($request->current_password, $user->password, $user->pepper_version)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        // Hash new password with Argon2id + HMAC Pepper
        $hashed = \App\Services\PasswordService::hash($request->password);
        $user->update([
            'password' => $hashed['hash'],
            'pepper_version' => $hashed['pepper_version'],
            'password_changed_at' => now(),
        ]);

        \App\Models\AuditLog::record(
            eventType: 'auth.password_changed',
            description: "User [{$user->email}] successfully changed master password.",
            userId: $user->id,
            severity: 'notice'
        );

        return back()->with('status', 'Password changed successfully! Secured with Argon2id + HMAC Pepper.');
    }
}
