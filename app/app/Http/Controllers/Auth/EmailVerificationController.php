<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Display the email verification notice.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : view('auth.verify-email');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            AuditLog::record(
                eventType: 'auth.email_verified',
                description: "User [{$request->user()->email}] successfully verified their email address.",
                userId: $request->user()->id,
                severity: 'info',
                payload: ['ip' => $request->ip()]
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Email verified successfully.']);
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }

    /**
     * Send a new email verification notification.
     */
    public function resend(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        AuditLog::record(
            eventType: 'auth.verification_link_sent',
            description: "Email verification link resent to [{$request->user()->email}].",
            userId: $request->user()->id,
            severity: 'info'
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Verification link sent.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
