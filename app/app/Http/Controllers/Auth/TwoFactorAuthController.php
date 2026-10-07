<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\PasswordService;
use App\Services\QrCodeService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorAuthController extends Controller
{
    /**
     * Display 2FA settings and management view.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $qrCodeSvg = null;
        $secretKey = null;

        if (!$user->two_factor_confirmed_at && $user->two_factor_secret) {
            $otpUri = TotpService::getOtpAuthUri($user->email, $user->two_factor_secret);
            $qrCodeSvg = QrCodeService::generateSvg($otpUri, 220);
            $secretKey = $user->two_factor_secret;
        }

        return view('profile.two-factor-authentication', [
            'user' => $user,
            'qrCodeSvg' => $qrCodeSvg,
            'secretKey' => $secretKey,
            'enabled' => !is_null($user->two_factor_confirmed_at),
        ]);
    }

    /**
     * Enable 2FA: Generate secret and 8 recovery codes.
     */
    public function enable(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return back()->with('status', '2FA is already enabled and active.');
        }

        $secret = TotpService::generateSecret();
        $recovery = TotpService::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery['hashed'],
            'two_factor_confirmed_at' => null, // Pending confirmation
        ])->save();

        $otpUri = TotpService::getOtpAuthUri($user->email, $secret);
        $qrCodeSvg = QrCodeService::generateSvg($otpUri, 220);

        AuditLog::record(
            eventType: 'auth.2fa_setup_initiated',
            description: "User [{$user->email}] initiated 2FA setup.",
            userId: $user->id,
            severity: 'info'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'secret' => $secret,
                'otpauth_url' => $otpUri,
                'qr_code_svg' => $qrCodeSvg,
                'recovery_codes' => $recovery['plain'], // Shown ONCE to user
            ]);
        }

        return back()->with([
            'status' => 'two-factor-authentication-enabled',
            'recoveryCodes' => $recovery['plain'],
        ]);
    }

    /**
     * Confirm 2FA setup by validating initial 6-digit TOTP code.
     */
    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            throw ValidationException::withMessages([
                'code' => 'Please initiate 2FA setup before attempting confirmation.',
            ]);
        }

        $isValid = TotpService::verifyCode($user->two_factor_secret, $request->code);

        if (!$isValid) {
            AuditLog::record(
                eventType: 'auth.2fa_confirm_failed',
                description: "Invalid 2FA confirmation code submitted by [{$user->email}].",
                userId: $user->id,
                severity: 'warning'
            );

            throw ValidationException::withMessages([
                'code' => 'The provided 2FA code is invalid or expired. Please check your authenticator app time.',
            ]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        AuditLog::record(
            eventType: 'auth.2fa_enabled',
            description: "Two-Factor Authentication successfully confirmed and activated for [{$user->email}].",
            userId: $user->id,
            severity: 'notice'
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Two-Factor Authentication confirmed and activated.']);
        }

        return back()->with('status', 'two-factor-authentication-confirmed');
    }

    /**
     * Disable 2FA: Requires password re-authentication.
     */
    public function disable(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Re-authenticate user password with pepper verification
        $isPasswordValid = PasswordService::verify($request->password, $user->password, $user->pepper_version);

        if (!$isPasswordValid) {
            throw ValidationException::withMessages([
                'password' => 'Incorrect password. Password confirmation is required to disable 2FA.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        AuditLog::record(
            eventType: 'auth.2fa_disabled',
            description: "Two-Factor Authentication was disabled by user [{$user->email}].",
            userId: $user->id,
            severity: 'warning',
            payload: ['ip' => $request->ip()]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Two-Factor Authentication disabled.']);
        }

        return back()->with('status', 'two-factor-authentication-disabled');
    }

    /**
     * Regenerate 8 single-use recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user->two_factor_confirmed_at) {
            throw ValidationException::withMessages([
                'password' => '2FA must be active to regenerate recovery codes.',
            ]);
        }

        $isPasswordValid = PasswordService::verify($request->password, $user->password, $user->pepper_version);
        if (!$isPasswordValid) {
            throw ValidationException::withMessages([
                'password' => 'Incorrect password.',
            ]);
        }

        $recovery = TotpService::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_recovery_codes' => $recovery['hashed'],
        ])->save();

        AuditLog::record(
            eventType: 'auth.2fa_recovery_regenerated',
            description: "User [{$user->email}] regenerated 8 single-use 2FA recovery codes.",
            userId: $user->id,
            severity: 'notice'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'recovery_codes' => $recovery['plain'],
            ]);
        }

        return back()->with([
            'status' => 'recovery-codes-regenerated',
            'recoveryCodes' => $recovery['plain'],
        ]);
    }
}
