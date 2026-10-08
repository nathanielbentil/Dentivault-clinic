<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class VerifyAuditChain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:verify-audit-chain {--detail : Show per-record cryptographic hashes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a full cryptographic SHA-256 hash chain verification across all immutable audit log records in Supabase';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Audit Log Cryptographic Integrity Verifier');
        $this->info('==========================================================');

        $logs = AuditLog::orderBy('id', 'asc')->get();
        $totalLogs = $logs->count();

        if ($totalLogs === 0) {
            $this->warn('No audit log records found in database.');
            return self::SUCCESS;
        }

        $this->line("Verifying cryptographic chain across {$totalLogs} audit records in Supabase PostgreSQL...\n");

        $prevHash = str_repeat('0', 64);
        $brokenRecord = null;
        $verifiedRecords = 0;
        $tableData = [];

        foreach ($logs as $index => $log) {
            // 1. Verify prev_hash matches previous record's log_hash
            if ($log->prev_hash !== $prevHash) {
                $brokenRecord = [
                    'id' => $log->id,
                    'reason' => "Chain linkage mismatch. Expected prev_hash [{$prevHash}], found [{$log->prev_hash}].",
                    'record' => $log,
                ];
                break;
            }

            // 2. Recompute expected log_hash from raw data
            $chainData = json_encode([
                'prev_hash' => $prevHash,
                'event_type' => $log->event_type,
                'severity' => $log->severity,
                'user_id' => $log->user_id,
                'ip' => $log->ip_address,
                'timestamp' => $log->created_at->toIso8601String(),
                'payload' => $log->payload,
            ]);

            $expectedHash = hash('sha256', $chainData);

            if (!hash_equals($log->log_hash, $expectedHash)) {
                $brokenRecord = [
                    'id' => $log->id,
                    'reason' => "Payload content tampering detected. Expected SHA-256 [{$expectedHash}], stored [{$log->log_hash}].",
                    'record' => $log,
                ];
                break;
            }

            if ($this->option('detail') || $index === 0 || $index === $totalLogs - 1) {
                $tableData[] = [
                    'ID' => $log->id,
                    'Event' => $log->event_type,
                    'Severity' => $log->severity,
                    'SHA-256 Hash Preview' => substr($log->log_hash, 0, 24) . '...',
                    'Status' => '<fg=green>[VERIFIED]</>',
                ];
            }

            $prevHash = $log->log_hash;
            $verifiedRecords++;
        }

        if (!empty($tableData)) {
            $this->table(['Record ID', 'Event Type', 'Severity', 'SHA-256 Hash Preview', 'Chain Status'], $tableData);
        }

        $this->newLine();
        if ($brokenRecord) {
            $this->error('==========================================================');
            $this->error("  [ALERT] CRYPTOGRAPHIC INTEGRITY VIOLATION DETECTED!");
            $this->error("  Broken Record ID: #{$brokenRecord['id']}");
            $this->error("  Reason: {$brokenRecord['reason']}");
            $this->error('==========================================================');
            return self::FAILURE;
        }

        $this->info("  [SUCCESS] All {$verifiedRecords}/{$totalLogs} audit records verified with 100% cryptographic integrity.");
        $this->line("  <fg=cyan>Latest Chain Block Hash:</> {$prevHash}");
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
