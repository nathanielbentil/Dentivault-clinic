<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    /**
     * List all dental patient records (Accessible by Dentist, Manager, Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $patients = Patient::orderBy('updated_at', 'desc')->get();

        // HIPAA Access Audit Log
        AuditLog::record(
            eventType: 'hipaa.patient_records_viewed',
            description: "User [{$user->email}] accessed patient dental records directory.",
            userId: $user->id,
            severity: 'info',
            payload: ['ip' => $request->ip(), 'records_count' => $patients->count()]
        );

        return response()->json([
            'success' => true,
            'patients' => $patients,
        ]);
    }

    /**
     * Store or update patient dental treatment chart
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'gender' => ['required', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:30'],
            'medical_history' => ['nullable', 'string', 'max:500'],
            'tooth_number' => ['nullable', 'string', 'max:50'],
            'procedure' => ['nullable', 'string', 'max:255'],
            'clinical_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', 'in:Scheduled,In Treatment,Completed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!empty($request->id)) {
            $patient = Patient::findOrFail($request->id);
            $patient->update([
                'name' => $request->name,
                'age' => $request->age,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'medical_history' => $request->medical_history,
                'tooth_number' => $request->tooth_number,
                'procedure' => $request->procedure,
                'clinical_notes' => $request->clinical_notes,
                'attending_dentist' => $user->name,
                'status' => $request->status,
            ]);

            $action = 'updated';
        } else {
            $code = 'DENT-' . random_int(1000, 9999);
            $patient = Patient::create([
                'patient_code' => $code,
                'name' => $request->name,
                'age' => $request->age,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'medical_history' => $request->medical_history,
                'tooth_number' => $request->tooth_number,
                'procedure' => $request->procedure,
                'clinical_notes' => $request->clinical_notes,
                'attending_dentist' => $user->name,
                'status' => $request->status,
            ]);

            $action = 'created';
        }

        // HIPAA Clinical Audit Log
        AuditLog::record(
            eventType: 'hipaa.dental_chart_' . $action,
            description: "User [{$user->email}] {$action} dental record for patient [{$patient->name}] (Code: {$patient->patient_code}, Tooth: {$patient->tooth_number}).",
            userId: $user->id,
            severity: 'notice',
            payload: [
                'ip' => $request->ip(),
                'patient_id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'procedure' => $patient->procedure,
                'tooth_number' => $patient->tooth_number,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Patient dental record {$action} successfully.",
            'patient' => $patient,
        ]);
    }

    /**
     * Delete patient record (Restricted to Manager and Admin)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // RBAC Check: Dentists cannot delete patient records
        if (!$user->hasRole('Admin') && !$user->hasRole('Manager')) {
            AuditLog::record(
                eventType: 'security.unauthorized_delete_attempt',
                description: "Unauthorized patient record deletion attempted by user [{$user->email}].",
                userId: $user->id,
                severity: 'warning',
                payload: ['ip' => $request->ip(), 'target_patient_id' => $id]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Practice Managers and Administrators can delete patient records.',
            ], 403);
        }

        $patient = Patient::find($id);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient record not found.',
            ], 404);
        }

        $patientName = $patient->name;
        $patientCode = $patient->patient_code;
        $patient->delete();

        AuditLog::record(
            eventType: 'hipaa.patient_record_deleted',
            description: "User [{$user->email}] permanently deleted patient record [{$patientName}] ({$patientCode}).",
            userId: $user->id,
            severity: 'warning',
            payload: ['ip' => $request->ip(), 'deleted_patient_id' => $id, 'deleted_code' => $patientCode]
        );

        return response()->json([
            'success' => true,
            'message' => "Patient record {$patientCode} deleted successfully.",
        ]);
    }
}
