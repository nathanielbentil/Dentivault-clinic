<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display the authenticated customer banking overview.
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $accountData = [
            'account_number' => 'SA-' . str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'account_type' => 'High-Yield Security Checking',
            'available_balance' => '$25,480.00',
            'security_level' => $user->two_factor_confirmed_at ? 'Tier 3 (2FA Protected)' : 'Tier 1 (Password Only)',
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

        return view('dashboard', compact('user', 'accountData'));
    }
}
