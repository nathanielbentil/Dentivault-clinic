<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Services\PasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->authenticate();

        // Check if password needs rehash (e.g. pepper version was rotated)
        if (PasswordService::needsRehash($user->password, $user->pepper_version)) {
            $rehashed = PasswordService::hash($request->password);
            $user->update([
                'password' => $rehashed['hash'],
                'pepper_version' => $rehashed['pepper_version'],
            ]);

            AuditLog::record(
                eventType: 'auth.rehashed',
                description: "Password automatically rehashed to active pepper version [{$rehashed['pepper_version']}].",
                userId: $user->id,
                severity: 'info'
            );
        }

        // Handle 2FA Challenge if 2FA is active
        if ($user->two_factor_confirmed_at) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            if ($request->wantsJson()) {
                return response()->json([
                    'two_factor_required' => true,
                    'message' => 'Please provide your 2FA TOTP code.',
                ]);
            }

            return redirect()->route('two-factor.challenge');
        }

        // Standard Login
        Auth::login($user, $request->boolean('remember'));

        // Prevent Session Fixation attacks
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Authentication successful.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ],
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::record(
                eventType: 'auth.logout',
                description: "User [{$user->email}] logged out.",
                userId: $user->id,
                severity: 'info',
                payload: ['ip' => $request->ip()]
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken(); // Regenerates CSRF token to prevent token reuse

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Logged out successfully.']);
        }

        return redirect('/');
    }
}
