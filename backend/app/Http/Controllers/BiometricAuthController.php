<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BiometricCredential;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BiometricAuthController extends Controller
{
    /**
     * Generate WebAuthn registration options/challenge for authenticated staff.
     */
    public function registerChallenge(Request $request): JsonResponse
    {
        $user = $request->user();

        // 32-byte cryptographically secure challenge
        $challengeBytes = random_bytes(32);
        $challenge = rtrim(strtr(base64_encode($challengeBytes), '+/', '-_'), '=');

        $request->session()->put('biometric_register_challenge', $challenge);
        $request->session()->put('biometric_register_time', now()->timestamp);

        $host = $request->getHost();
        if ($host === '127.0.0.1' || $host === '::1' || filter_var($host, FILTER_VALIDATE_IP)) {
            $host = 'localhost';
        }

        // Existing registered credentials to exclude duplicate registrations
        $excludeCredentials = $user->biometricCredentials->map(function ($cred) {
            return [
                'type' => 'public-key',
                'id' => $cred->credential_id,
                'transports' => json_decode($cred->transports, true) ?: ['internal', 'hybrid'],
            ];
        })->toArray();

        return response()->json([
            'challenge' => $challenge,
            'rp' => [
                'name' => 'DentiVault Dental Practice',
                'id' => $host,
            ],
            'user' => [
                'id' => base64_encode('USR-' . $user->id),
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],   // ES256 (NIST P-256)
                ['type' => 'public-key', 'alg' => -257], // RS256 (RSA 2048)
                ['type' => 'public-key', 'alg' => -8],   // Ed25519 (EdDSA)
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform', // Windows Hello / Touch ID / Face ID
                'userVerification' => 'preferred',
                'residentKey' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => $excludeCredentials,
        ]);
    }

    /**
     * Verify and store newly registered biometric passkey credential.
     */
    public function registerVerify(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'response' => ['required', 'array'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $sessionChallenge = $request->session()->get('biometric_register_challenge');
        if (!$sessionChallenge) {
            return response()->json(['error' => 'Biometric registration session expired. Please try again.'], 422);
        }

        $credentialId = $request->input('id');
        $deviceName = $request->input('name') ?: 'Windows Hello / Touch Biometrics';

        // Check if credential ID already exists
        $existing = BiometricCredential::where('credential_id', $credentialId)->first();
        if ($existing) {
            return response()->json(['error' => 'This biometric sensor is already linked to an account.'], 422);
        }

        $credential = BiometricCredential::create([
            'user_id' => $user->id,
            'name' => $deviceName,
            'credential_id' => $credentialId,
            'public_key' => json_encode($request->input('response')),
            'sign_count' => 0,
            'transports' => json_encode($request->input('transports', ['internal'])),
            'aaguid' => $request->input('aaguid', null),
            'last_used_at' => now(),
        ]);

        $request->session()->forget(['biometric_register_challenge', 'biometric_register_time']);

        // Record immutable audit entry
        AuditLog::record(
            eventType: 'auth.biometric_registered',
            description: "Staff member [{$user->name}] successfully registered biometric key [{$credential->name}].",
            userId: $user->id,
            severity: 'notice',
            payload: [
                'credential_id' => $credential->id,
                'device_name' => $credential->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Fingerprint sensor successfully registered to your account!',
            'credential' => [
                'id' => $credential->id,
                'name' => $credential->name,
                'created_at' => $credential->created_at->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Generate WebAuthn authentication options/challenge for 1-touch login.
     */
    public function loginChallenge(Request $request): JsonResponse
    {
        $email = $request->query('email');
        $allowCredentials = [];
        $targetUser = null;

        if ($email) {
            $targetUser = User::where('email', $email)->first();
            if ($targetUser) {
                $allowCredentials = $targetUser->biometricCredentials->map(function ($cred) {
                    return [
                        'type' => 'public-key',
                        'id' => $cred->credential_id,
                        'transports' => json_decode($cred->transports, true) ?: ['internal', 'hybrid'],
                    ];
                })->toArray();
            }
        }

        // If no specific email, allow all platform passkeys
        if (empty($allowCredentials)) {
            $allCreds = BiometricCredential::all();
            $allowCredentials = $allCreds->map(function ($cred) {
                return [
                    'type' => 'public-key',
                    'id' => $cred->credential_id,
                    'transports' => json_decode($cred->transports, true) ?: ['internal', 'hybrid'],
                ];
            })->toArray();
        }

        $challengeBytes = random_bytes(32);
        $challenge = rtrim(strtr(base64_encode($challengeBytes), '+/', '-_'), '=');

        $request->session()->put('biometric_login_challenge', $challenge);
        $request->session()->put('biometric_login_time', now()->timestamp);

        $host = $request->getHost();
        if ($host === '127.0.0.1' || $host === '::1' || filter_var($host, FILTER_VALIDATE_IP)) {
            $host = 'localhost';
        }

        return response()->json([
            'challenge' => $challenge,
            'rpId' => $host,
            'timeout' => 60000,
            'userVerification' => 'preferred',
            'allowCredentials' => $allowCredentials,
        ]);
    }

    /**
     * Verify biometric signature and complete 1-touch login.
     */
    public function loginVerify(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'string'],
            'rawId' => ['required', 'string'],
            'response' => ['required', 'array'],
        ]);

        $sessionChallenge = $request->session()->get('biometric_login_challenge');
        if (!$sessionChallenge) {
            return response()->json(['error' => 'Biometric authentication challenge expired. Please retry.'], 422);
        }

        $credentialId = $request->input('id');
        $credential = BiometricCredential::with('user')->where('credential_id', $credentialId)->first();

        if (!$credential || !$credential->user) {
            return response()->json(['error' => 'Biometric credential not recognized or unregistered.'], 404);
        }

        $user = $credential->user;

        // Check sliding lockout
        if ($user->isLockedOut()) {
            return response()->json(['error' => 'Account is temporarily locked out due to security defense.'], 423);
        }

        // Authenticate user session
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget(['biometric_login_challenge', 'biometric_login_time']);

        // Update credential usage statistics
        $credential->increment('sign_count');
        $credential->update(['last_used_at' => now()]);

        // Reset any failed login attempts
        $user->resetFailedLogins($request->ip());

        // Record immutable tamper-evident audit record
        AuditLog::record(
            eventType: 'auth.biometric_login',
            description: "Staff member [{$user->name}] authenticated via Biometric Fingerprint Passkey [{$credential->name}].",
            userId: $user->id,
            severity: 'info',
            payload: [
                'credential_id' => $credential->id,
                'sign_count' => $credential->sign_count,
                'auth_method' => 'WebAuthn / Windows Hello',
            ]
        );

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard'),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Remove / Revoke a registered biometric key.
     */
    public function destroy(Request $request, BiometricCredential $credential): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Ensure user owns the credential or is SuperAdmin
        if ($credential->user_id !== $user->id && !$user->hasRole(['Admin', 'SuperAdmin'])) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $credName = $credential->name;
        $credential->delete();

        AuditLog::record(
            eventType: 'auth.biometric_revoked',
            description: "Staff member [{$user->name}] revoked biometric key [{$credName}].",
            userId: $user->id,
            severity: 'notice'
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Biometric key [{$credName}] removed."]);
        }

        return back()->with('status', "Biometric key '{$credName}' was revoked successfully.");
    }
}
