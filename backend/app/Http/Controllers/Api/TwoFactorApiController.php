<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\PasswordService;
use App\Services\QrCodeService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TwoFactorApiController extends Controller
{
    /**
     * Get 2FA Status for current user
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'enabled' => !is_null($user->two_factor_confirmed_at),
            'confirmed_at' => $user->two_factor_confirmed_at,
        ]);
    }

    /**
     * Initiate 2FA Setup: Generate secret, QR code SVG, and recovery codes
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Two-Factor Authentication is already active on this account.',
            ], 400);
        }

        $secret = TotpService::generateSecret();
        $recovery = TotpService::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery['hashed'],
            'two_factor_confirmed_at' => null,
        ])->save();

        $otpUri = TotpService::getOtpAuthUri($user->email, $secret);
        $qrCodeSvg = QrCodeService::generateSvg($otpUri, 220);

        AuditLog::record(
            eventType: 'auth.2fa_setup_initiated',
            description: "User [{$user->email}] generated new 2FA TOTP secret key.",
            userId: $user->id,
            severity: 'info'
        );

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'otpauth_url' => $otpUri,
            'qr_code_svg' => $qrCodeSvg,
            'recovery_codes' => $recovery['plain'],
        ]);
    }

    /**
     * Confirm 2FA Setup by verifying the first 6-digit TOTP code
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Please initiate 2FA setup first before confirming.',
            ], 400);
        }

        $isValid = TotpService::verifyCode($user->two_factor_secret, $request->code);

        if (!$isValid) {
            AuditLog::record(
                eventType: 'auth.2fa_confirm_failed',
                description: "Invalid 2FA confirmation code submitted by [{$user->email}].",
                userId: $user->id,
                severity: 'warning'
            );

            return response()->json([
                'success' => false,
                'message' => 'The provided 6-digit code is invalid or expired. Check your device clock time.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        AuditLog::record(
            eventType: 'auth.2fa_enabled',
            description: "Two-Factor Authentication activated and verified for [{$user->email}].",
            userId: $user->id,
            severity: 'notice'
        );

        return response()->json([
            'success' => true,
            'message' => 'Two-Factor Authentication successfully activated and verified.',
        ]);
    }

    /**
     * Disable 2FA: Requires password verification
     */
    public function disable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required to disable 2FA.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $isPasswordValid = PasswordService::verify($request->password, $user->password, $user->pepper_version);

        if (!$isPasswordValid) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password. Verification failed.',
            ], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Two-Factor Authentication disabled successfully.',
        ]);
    }

    /**
     * Regenerate 8 Single-Use Recovery Codes
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!$user->two_factor_confirmed_at) {
            return response()->json([
                'success' => false,
                'message' => '2FA must be active to regenerate recovery codes.',
            ], 400);
        }

        $isPasswordValid = PasswordService::verify($request->password, $user->password, $user->pepper_version);
        if (!$isPasswordValid) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password.',
            ], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Recovery codes regenerated successfully.',
            'recovery_codes' => $recovery['plain'],
        ]);
    }
}
