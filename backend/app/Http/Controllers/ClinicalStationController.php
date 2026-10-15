<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicalStationController extends Controller
{
    /**
     * Dedicated Doctor Electronic Prescriptions & Orders Page
     */
    public function prescriptions(Request $request): View
    {
        $user = $request->user();
        $patients = Patient::latest()->take(52)->get();
        $totalOrders = 38;
        $pendingFulfillment = 19;
        $dispensedToday = 34;
        $criticalIcu = Patient::where('admission_status', 'ICU')->count();

        AuditLog::record(
            eventType: 'clinical.prescriptions_access',
            description: "Physician [{$user->name}] accessed the dedicated Electronic Prescriptions terminal.",
            userId: $user->id,
            severity: 'info'
        );

        return view('doctor.prescriptions', compact('user', 'patients', 'totalOrders', 'pendingFulfillment', 'dispensedToday', 'criticalIcu'));
    }

    /**
     * Dedicated Nurse Shift Vitals & Bed Station Page
     */
    public function vitals(Request $request): View
    {
        $user = $request->user();
        $patients = Patient::whereIn('admission_status', ['Admitted', 'ICU'])->get();
        $icuPatients = Patient::where('admission_status', 'ICU')->get();
        $admittedCount = Patient::where('admission_status', 'Admitted')->count();

        AuditLog::record(
            eventType: 'clinical.vitals_access',
            description: "Staff [{$user->name}] accessed the dedicated Shift Nursing Vitals & Telemetry Station.",
            userId: $user->id,
            severity: 'info'
        );

        return view('nurse.vitals', compact('user', 'patients', 'icuPatients', 'admittedCount'));
    }

    /**
     * Dedicated Pharmacy & Dispensary Fulfillment Queue Page
     */
    public function dispensary(Request $request): View
    {
        $user = $request->user();
        $patients = Patient::whereNotNull('treatment_plan')->take(25)->get();
        $pendingCount = 19;
        $dispensedCount = 34;

        AuditLog::record(
            eventType: 'pharmacy.dispensary_access',
            description: "Pharmacist [{$user->name}] accessed the dedicated Dispensary Fulfillment Queue.",
            userId: $user->id,
            severity: 'info'
        );

        return view('pharmacy.dispensary', compact('user', 'patients', 'pendingCount', 'dispensedCount'));
    }

    /**
     * Dedicated Security Telemetry & SQL Defense Laboratory Page
     */
    public function telemetry(Request $request): View
    {
        $user = $request->user();
        $totalAuditLogs = AuditLog::count();
        $recentLogs = AuditLog::latest('id')->take(20)->get();

        AuditLog::record(
            eventType: 'security.telemetry_access',
            description: "User [{$user->name}] opened the dedicated Security Telemetry & SQL Defense Lab.",
            userId: $user->id,
            severity: 'info'
        );

        return view('security.telemetry', compact('user', 'totalAuditLogs', 'recentLogs'));
    }
}
