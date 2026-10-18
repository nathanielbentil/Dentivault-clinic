<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\SystemConfiguration;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /**
     * Download the standard, comprehensive academic security audit report (PDF).
     */
    public function downloadSecurityPdf(Request $request)
    {
        $activeStaff = User::whereNull('deleted_at')->where('status', 'active')->get();
        $archivedStaff = User::onlyTrashed()->get();
        $totalPatients = Patient::count();
        $admittedPatients = Patient::where('admission_status', 'Admitted')->count();
        $icuPatients = Patient::where('admission_status', 'ICU')->count();
        $dischargedPatients = Patient::where('admission_status', 'Discharged')->count();
        $outpatients = Patient::where('admission_status', 'Outpatient')->count();
        $configRestorePoints = SystemConfiguration::all();
        $recentAuditLogs = AuditLog::with('user')->latest('id')->take(20)->get();

        $data = [
            'generationTime' => now()->toFormattedDateString() . ' ' . now()->toTimeString() . ' UTC',
            'activeStaff' => $activeStaff,
            'archivedStaff' => $archivedStaff,
            'totalPatients' => $totalPatients,
            'admittedPatients' => $admittedPatients,
            'icuPatients' => $icuPatients,
            'dischargedPatients' => $dischargedPatients,
            'outpatients' => $outpatients,
            'configRestorePoints' => $configRestorePoints,
            'recentAuditLogs' => $recentAuditLogs,
        ];

        // Audit log the PDF download event
        if ($request->user()) {
            AuditLog::record(
                eventType: 'report.pdf_downloaded',
                description: "Staff [{$request->user()->name}] downloaded the Official Information Security Defense Report (PDF).",
                userId: $request->user()->id,
                severity: 'info'
            );
        }

        $pdf = Pdf::loadView('reports.security_pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('MediVault_Enterprise_Security_Report_2026.pdf');
    }

    /**
     * View the report directly in the browser as an HTML printable page.
     */
    public function viewSecurityReport(Request $request)
    {
        $activeStaff = User::whereNull('deleted_at')->where('status', 'active')->get();
        $archivedStaff = User::onlyTrashed()->get();
        $totalPatients = Patient::count();
        $admittedPatients = Patient::where('admission_status', 'Admitted')->count();
        $icuPatients = Patient::where('admission_status', 'ICU')->count();
        $dischargedPatients = Patient::where('admission_status', 'Discharged')->count();
        $outpatients = Patient::where('admission_status', 'Outpatient')->count();
        $configRestorePoints = SystemConfiguration::all();
        $recentAuditLogs = AuditLog::with('user')->latest('id')->take(25)->get();

        return view('reports.security_pdf', [
            'generationTime' => now()->toFormattedDateString() . ' ' . now()->toTimeString() . ' UTC',
            'activeStaff' => $activeStaff,
            'archivedStaff' => $archivedStaff,
            'totalPatients' => $totalPatients,
            'admittedPatients' => $admittedPatients,
            'icuPatients' => $icuPatients,
            'dischargedPatients' => $dischargedPatients,
            'outpatients' => $outpatients,
            'configRestorePoints' => $configRestorePoints,
            'recentAuditLogs' => $recentAuditLogs,
            'isBrowserView' => true,
        ]);
    }
}
