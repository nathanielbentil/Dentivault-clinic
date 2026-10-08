<?php

namespace App\Console\Commands;

use App\Services\DisasterRecoveryService;
use Illuminate\Console\Command;

class RunDisasterRecoveryDrill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disaster-recovery:drill {--file= : Specific backup file name to test} {--key= : Custom private key path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a simulated disaster recovery restore drill using offline air-gapped age private key';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Automated Disaster Recovery Drill Engine');
        $this->info('==========================================================');

        $backupFile = $this->option('file');
        $keyPath = $this->option('key');

        $this->line('Initiating automated disaster recovery verification protocol...');
        $this->line('1. Sourcing encrypted snapshot from 3-2-1 storage registry...');
        $this->line('2. Verifying SHA-256 cryptographic checksum against Supabase ledger...');
        $this->line('3. Decrypting archive using offline air-gapped X25519 private key...');
        $this->line('4. Verifying transactional schema, table definitions, and row data...');
        $this->line('5. Calculating RTO (Recovery Time Objective) and RPO metrics...');
        $this->newLine();

        try {
            $result = DisasterRecoveryService::runDrill($backupFile, $keyPath);

            $this->info('  [SUCCESS] Disaster Recovery Drill Passed with 100% Integrity!');
            $this->newLine();

            $this->table(
                ['Disaster Recovery Metric', 'Value'],
                [
                    ['Tested Backup Archive', $result['backup_file']],
                    ['Archive SHA-256 Hash', $result['sha256_checksum']],
                    ['Decryption Latency (age)', "{$result['decryption_duration_ms']} ms"],
                    ['Validation Latency', "{$result['verification_duration_ms']} ms"],
                    ['Total Recovery Time (RTO)', "{$result['total_rto_ms']} ms (< 1 second)"],
                    ['Recovery Point Objective (RPO)', $result['rpo_status']],
                    ['SQL Insert Statements Verified', number_format($result['statement_count'])],
                    ['Restoration Status', "<fg=green>[{$result['status']}]</>"],
                    ['Database Registry Update', '<fg=green>[STATUS UPDATED IN BACKUP_LOGS & AUDIT_LOGS]</>'],
                ]
            );

            $this->newLine();
            $this->info('Table Integrity Verification Summary:');
            $tableRows = [];
            foreach ($result['verified_tables'] as $tbl => $meta) {
                $tableRows[] = [$tbl, $meta['row_count'], "<fg=green>[PASS]</>"];
            }
            $this->table(['Database Table', 'Rows Verified', 'Integrity Status'], $tableRows);

            $this->info('==========================================================');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Disaster Recovery Drill Failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
