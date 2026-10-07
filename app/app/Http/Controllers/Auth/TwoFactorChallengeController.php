<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    /**
     * Display the two-factor authentication challenge view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Attempt to authenticate the user using a TOTP code or single-use recovery code.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $userId = $request->session()->get('login.id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || !$user->two_factor_confirmed_at) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login');
        }

        $code = $request->input('code');
        $recoveryCode = $request->input('recovery_code');

        $authenticated = false;
        $isRecoveryUsed = false;

        // 1. Verify TOTP 6-Digit Code
        if (!empty($code)) {
            $authenticated = TotpService::verifyCode(
                $user->two_factor_secret,
                $code,
                window: config('security.two_factor.window', 1)
            );
        }

        // 2. Verify Single-Use Recovery Code (if TOTP not used/failed)
        if (!$authenticated && !empty($recoveryCode)) {
            $hashedCodes = $user->two_factor_recovery_codes ?? [];
            $authenticated = TotpService::verifyAndConsumeRecoveryCode($recoveryCode, $hashedCodes);

            if ($authenticated) {
                $isRecoveryUsed = true;
                $user->forceFill([
                    'two_factor_recovery_codes' => $hashedCodes,
                ])->save();

                AuditLog::record(
                    eventType: 'auth.2fa_recovery_used',
                    description: "User [{$user->email}] authenticated using a single-use recovery code.",
                    userId: $user->id,
                    severity: 'notice',
                    payload: ['ip' => $request->ip()]
                );
            }
        }

        if (!$authenticated) {
            AuditLog::record(
                eventType: 'auth.2fa_failed',
                description: "2FA challenge verification failed for user [{$user->email}].",
                userId: $user->id,
                severity: 'warning',
                payload: ['ip' => $request->ip()]
            );

            throw ValidationException::withMessages([
                'code' => 'The provided two-factor authentication code was invalid or expired.',
            ]);
        }

        // Complete Authentication
        $remember = $request->session()->get('login.remember', false);
        $request->session()->forget(['login.id', 'login.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditLog::record(
            eventType: 'auth.2fa_success',
            description: "User [{$user->email}] successfully completed 2FA challenge.",
            userId: $user->id,
            severity: 'info',
            payload: ['is_recovery_code' => $isRecoveryUsed, 'ip' => $request->ip()]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => '2FA authentication successful.',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
