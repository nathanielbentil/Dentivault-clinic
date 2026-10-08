<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display the Branch Manager customer operations view.
     */
    public function index(): View|JsonResponse
    {
        $customers = User::whereHas('roles', function ($q) {
            $q->where('slug', 'Customer');
        })->latest('id')->paginate(15);

        if (request()->wantsJson()) {
            return response()->json(['customers' => $customers]);
        }

        return view('branch.dashboard', compact('customers'));
    }

    /**
     * Unlock a locked customer account after identity verification.
     */
    public function unlockCustomer(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $user->update([
            'status' => 'active',
            'failed_login_attempts' => 0,
            'lockout_until' => null,
        ]);

        AuditLog::record(
            eventType: 'users.unlocked',
            description: "Branch Manager [{$request->user()->email}] unlocked customer account [{$user->email}] after identity verification.",
            userId: $request->user()->id,
            severity: 'notice',
            payload: ['customer_email' => $user->email]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => "Customer [{$user->email}] successfully unlocked."]);
        }

        return back()->with('status', "Account {$user->email} has been unlocked.");
    }
}
