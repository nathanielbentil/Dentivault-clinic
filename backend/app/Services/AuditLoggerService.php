<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLoggerService
{
    /**
     * Perform a cryptographic SHA-256 hash chain verification across all immutable audit records.
     */
    public static function verifyChainIntegrity(): array
    {
        $logs = AuditLog::orderBy('id', 'asc')->get();
        $totalLogs = $logs->count();

        if ($totalLogs === 0) {
            return [
                'intact' => true,
                'total_logs' => 0,
                'verified_logs' => 0,
                'message' => 'No audit records present in database.',
            ];
        }

        $prevHash = str_repeat('0', 64);
        $brokenRecord = null;
        $verifiedCount = 0;

        foreach ($logs as $log) {
            if ($log->prev_hash !== $prevHash) {
                $brokenRecord = [
                    'id' => $log->id,
                    'reason' => "Chain linkage broken at ID #{$log->id}. Expected previous hash [{$prevHash}], but found [{$log->prev_hash}].",
                    'expected' => $prevHash,
                    'found' => $log->prev_hash,
                ];
                break;
            }

            $chainData = json_encode([
                'prev_hash' => $prevHash,
                'event_type' => $log->event_type,
                'severity' => $log->severity,
                'description' => $log->description,
                'user_id' => $log->user_id,
                'ip' => $log->ip_address,
                'timestamp' => $log->created_at->toIso8601String(),
                'payload' => $log->payload,
            ]);

            $expectedHash = hash('sha256', $chainData);

            if (!hash_equals($log->log_hash, $expectedHash)) {
                $brokenRecord = [
                    'id' => $log->id,
                    'reason' => "Content payload tampering detected at Record #{$log->id}.",
                    'expected_hash' => $expectedHash,
                    'actual_hash' => $log->log_hash,
                ];
                break;
            }

            $prevHash = $log->log_hash;
            $verifiedCount++;
        }

        if ($brokenRecord) {
            return [
                'intact' => false,
                'total_logs' => $totalLogs,
                'verified_logs' => $verifiedCount,
                'broken_at_id' => $brokenRecord['id'],
                'reason' => $brokenRecord['reason'],
                'expected_hash' => $brokenRecord['expected_hash'] ?? $brokenRecord['expected'] ?? null,
                'actual_hash' => $brokenRecord['actual_hash'] ?? $brokenRecord['found'] ?? null,
            ];
        }

        return [
            'intact' => true,
            'total_logs' => $totalLogs,
            'verified_logs' => $verifiedCount,
            'latest_block_hash' => $prevHash,
        ];
    }
}
