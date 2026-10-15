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

        $patients = \App\Models\Patient::latest()->take(52)->get();
        $totalPatients = \App\Models\Patient::count();
        $admittedPatients = \App\Models\Patient::where('admission_status', 'Admitted')->count();
        $icuPatients = \App\Models\Patient::where('admission_status', 'ICU')->count();
        $outpatients = \App\Models\Patient::where('admission_status', 'Outpatient')->count();
        $totalStaff = \App\Models\User::count();

        return view('dashboard', compact('user', 'accountData', 'patients', 'totalPatients', 'admittedPatients', 'icuPatients', 'outpatients', 'totalStaff'));
    }

    /**
     * =========================================================================
     * REQUIREMENT #5: PARAMETERIZED SQL QUERIES (PDO PREPARED STATEMENTS)
     * =========================================================================
     * 
     * HOW THIS PREVENTS SQL INJECTION:
     * 1. The query template is compiled by PostgreSQL before data is attached.
     * 2. The input is passed separately as a bound parameter in the array [?].
     * 3. Malicious characters like ' OR '1'='1' -- are treated as harmless
     *    literal string data, never as executable SQL instructions.
     */
    public function testParameterizedQuery(Request $request): JsonResponse
    {
        $input = $request->input('query_input', "' OR '1'='1' --");

        // RAW PARAMETERIZED PDO QUERY DEMONSTRATION
        // Notice the placeholder '?' and the binding array [$input]
        $sql = 'SELECT id, patient_code, name, ward_room, blood_group, admission_status FROM patients WHERE patient_code = ? AND deleted_at IS NULL';
        
        $bindings = [$input];
        $results = \Illuminate\Support\Facades\DB::select($sql, $bindings);

        return response()->json([
            'status' => 'success',
            'security_type' => 'PDO Parameterized Prepared Statement',
            'raw_sql_statement' => $sql,
            'bound_parameters' => $bindings,
            'injection_neutralized' => true,
            'rows_returned' => count($results),
            'data' => $results,
            'academic_explanation' => count($results) === 0
                ? "BLOCKED: 0 Rows Returned. The PostgreSQL engine treated the payload as literal string characters, looking for a patient with code literally equal to \"$input\". Attack neutralized!"
                : "SUCCESS: Found patient record using parameterized placeholder '?' without vulnerability to injection."
        ]);
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
