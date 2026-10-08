<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class NormalizeAuditChain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:normalize-audit-chain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-compute and normalize the cryptographic SHA-256 hash chain from genesis block';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logs = AuditLog::orderBy('id', 'asc')->get();
        $prev = str_repeat('0', 64);
        $count = 0;

        foreach ($logs as $log) {
            $chainData = json_encode([
                'prev_hash' => $prev,
                'event_type' => $log->event_type,
                'severity' => $log->severity,
                'description' => $log->description,
                'user_id' => $log->user_id,
                'ip' => $log->ip_address,
                'timestamp' => $log->created_at->toIso8601String(),
                'payload' => $log->payload,
            ]);

            $hash = hash('sha256', $chainData);
            $log->updateQuietly([
                'prev_hash' => $prev,
                'log_hash' => $hash,
            ]);

            $prev = $hash;
            $count++;
        }

        $this->info("Normalized {$count} audit log records. Chain root hash: {$prev}");
        return self::SUCCESS;
    }
}
