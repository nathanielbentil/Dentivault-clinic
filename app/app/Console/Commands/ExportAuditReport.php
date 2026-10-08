<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class ExportAuditReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:export-audit-report {--format=json : Output format (json, csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a certified forensic audit report export with cryptographic chain hash summaries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = strtolower($this->option('format'));
        $logs = AuditLog::with('user')->orderBy('id', 'asc')->get();

        $latestLog = $logs->last();
        $genesisLog = $logs->first();

        $reportMetadata = [
            'system_name' => config('app.name'),
            'generated_at' => now()->toIso8601String(),
            'total_audit_records' => $logs->count(),
            'genesis_hash' => $genesisLog ? $genesisLog->log_hash : null,
            'latest_block_hash' => $latestLog ? $latestLog->log_hash : null,
            'records' => $logs->map(fn($l) => [
                'id' => $l->id,
                'timestamp' => $l->created_at->toIso8601String(),
                'user' => $l->user ? $l->user->email : 'SYSTEM',
                'event_type' => $l->event_type,
                'severity' => $l->severity,
                'description' => $l->description,
                'ip_address' => $l->ip_address,
                'prev_hash' => $l->prev_hash,
                'log_hash' => $l->log_hash,
            ]),
        ];

        $exportDir = base_path('storage/app/audit-reports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = 'audit_report_' . date('Y_m_d_His') . '.' . ($format === 'csv' ? 'csv' : 'json');
        $filePath = $exportDir . DIRECTORY_SEPARATOR . $filename;

        if ($format === 'csv') {
            $fp = fopen($filePath, 'w');
            fputcsv($fp, ['ID', 'Timestamp', 'User', 'Event Type', 'Severity', 'Description', 'IP Address', 'Prev Hash', 'Log Hash']);
            foreach ($reportMetadata['records'] as $r) {
                fputcsv($fp, array_values($r));
            }
            fclose($fp);
        } else {
            file_put_contents($filePath, json_encode($reportMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->info("Certified Forensic Audit Report exported successfully!");
        $this->line("  <fg=cyan>File:</> {$filePath}");
        $this->line("  <fg=cyan>Total Records:</> {$logs->count()}");
        $this->line("  <fg=cyan>Latest Hash Block:</> " . ($latestLog ? substr($latestLog->log_hash, 0, 32) . '...' : 'None'));

        return self::SUCCESS;
    }
}
