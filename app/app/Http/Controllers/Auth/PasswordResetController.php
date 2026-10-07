<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function sendResetLink(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        AuditLog::record(
            eventType: 'auth.password_reset_requested',
            description: "Password reset link requested for [{$request->email}].",
            severity: 'notice',
            payload: ['ip' => $request->ip()]
        );

        if ($request->wantsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json(['message' => __($status)])
                : response()->json(['message' => __($status)], 422);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Display the password reset view.
     */
    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['request' => $request, 'token' => $token]);
    }

    /**
     * Handle an incoming new password request with HMAC Pepper and Argon2id hashing.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised(3),
            ],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                // Hash with current Pepper version and Argon2id
                $hashResult = PasswordService::hash($request->password);

                $user->forceFill([
                    'password' => $hashResult['hash'],
                    'pepper_version' => $hashResult['pepper_version'],
                    'password_changed_at' => now(),
                    'require_password_change' => false,
                    'failed_login_attempts' => 0,
                    'lockout_until' => null,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                AuditLog::record(
                    eventType: 'auth.password_reset_completed',
                    description: "Password successfully reset for [{$user->email}].",
                    userId: $user->id,
                    severity: 'notice',
                    payload: ['ip' => $request->ip()]
                );
            }
        );

        if ($request->wantsJson()) {
            return $status === Password::PASSWORD_RESET
                ? response()->json(['message' => __($status)])
                : response()->json(['message' => __($status)], 422);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
