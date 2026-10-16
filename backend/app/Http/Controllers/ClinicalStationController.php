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

    /**
     * Admit a new hospital patient (Doctor, Admin, Manager)
     */
    public function admitPatient(Request $request)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['Admin', 'SuperAdmin', 'Manager', 'Doctor']) && !in_array($user->role, ['Admin', 'Manager', 'Doctor', 'SuperAdmin'])) {
            abort(403, 'Unauthorized. Only Attending Doctors, Operations Managers, and Administrators can admit patients.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'gender' => ['required', 'string', 'in:Female,Male,Other'],
            'blood_group' => ['required', 'string', 'in:O+,A+,B+,AB+,O-,A-,B-,AB-'],
            'ward_room' => ['required', 'string', 'max:255'],
            'diagnosis' => ['required', 'string', 'max:500'],
            'admission_status' => ['nullable', 'string', 'in:Admitted,ICU,Outpatient,Discharged'],
            'treatment_plan' => ['nullable', 'string', 'max:1000'],
        ]);

        // Generate next sequential MED-XXXX patient code
        $maxId = Patient::withTrashed()->max('id') ?? 52;
        $patientCode = 'MED-' . (1000 + $maxId + 1);

        $patient = Patient::create([
            'patient_code' => $patientCode,
            'name' => $validated['name'],
            'age' => $validated['age'],
            'gender' => $validated['gender'],
            'blood_group' => $validated['blood_group'],
            'ward_room' => $validated['ward_room'],
            'admission_status' => $validated['admission_status'] ?? 'Admitted',
            'diagnosis' => $validated['diagnosis'],
            'treatment_plan' => $validated['treatment_plan'] ?? 'Initial clinical observation & vital monitoring protocol initiated.',
            'prescription' => 'Pending attending physician formulation.',
            'vitals_summary' => 'BP 120/80 mmHg, HR 72 bpm, SpO2 99%, Temp 36.8°C',
            'attending_doctor' => ($user->role === 'Doctor' || $user->hasRole('Doctor')) ? $user->name : 'Dr. Attending Physician, MD',
            'assigned_nurse' => 'Nurse Shift Station',
        ]);

        AuditLog::record(
            eventType: 'hipaa.patient_admitted',
            description: "Patient [{$patient->name}] ({$patient->patient_code}) successfully admitted to [{$patient->ward_room}] by {$user->name} ({$user->role}).",
            userId: $user->id,
            severity: 'info',
            payload: [
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
                'ward_room' => $patient->ward_room,
                'diagnosis' => $patient->diagnosis,
                'admission_status' => $patient->admission_status,
                'ip' => $request->ip(),
            ]
        );

        return redirect()->back()->with('status', "Patient {$patient->name} ({$patient->patient_code}) successfully admitted to {$patient->ward_room}!");
    }
}
