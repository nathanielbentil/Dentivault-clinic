<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SecurityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditorController extends Controller
{
    /**
     * Display the Security Auditor log explorer.
     */
    public function index(): View|JsonResponse
    {
        $logs = AuditLog::with('user')->latest('id')->paginate(20);
        $securityEvents = SecurityEvent::with('user')->latest('id')->take(10)->get();

        if (request()->wantsJson()) {
            return response()->json([
                'audit_logs' => $logs,
                'recent_attacks' => $securityEvents,
            ]);
        }

        return view('audit.dashboard', compact('logs', 'securityEvents'));
    }

    /**
     * Verify cryptographic SHA-256 integrity chain across all audit logs.
     */
    public function verifyIntegrity(): JsonResponse
    {
        $logs = AuditLog::orderBy('id', 'asc')->get();

        $brokenIndex = null;
        $prevHash = str_repeat('0', 64);
        $verifiedCount = 0;

        foreach ($logs as $log) {
            // Recompute expected hash
            $chainData = json_encode([
                'prev_hash' => $prevHash,
                'event_type' => $log->event_type,
                'severity' => $log->severity,
                'user_id' => $log->user_id,
                'ip' => $log->ip_address,
                'timestamp' => $log->created_at->toIso8601String(),
                'payload' => $log->payload,
            ]);

            $calculatedHash = hash('sha256', $chainData);

            if (!hash_equals($log->log_hash, $calculatedHash)) {
                $brokenIndex = $log->id;
                break;
            }

            $prevHash = $log->log_hash;
            $verifiedCount++;
        }

        $isChainValid = is_null($brokenIndex);

        return response()->json([
            'is_valid' => $isChainValid,
            'verified_records_count' => $verifiedCount,
            'broken_at_record_id' => $brokenIndex,
            'message' => $isChainValid
                ? "Cryptographic verification successful: All {$verifiedCount} audit records validated with zero tampering detected."
                : "SECURITY ALERT: Log tampering detected at record ID #{$brokenIndex}!",
        ]);
    }
}
