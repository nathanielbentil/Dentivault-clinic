<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\BackupService;
use App\Services\CloudBackupService;
use App\Services\DisasterRecoveryService;
use Illuminate\Console\Command;

class TestDisasterRecovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-disaster-recovery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive test suite for Disaster Recovery, RTO/RPO benchmarking, key authorization, and corrupted archive defense';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Disaster Recovery & Integrity Defense Suite');
        $this->info('==========================================================');

        try {
            // Test 1: Generate Fresh Backup & Cloud Sync
            $this->info('[Test 1/4] Generating Fresh Backup & Replicating to Cloud...');
            $local = BackupService::createLocalBackup('full_database');
            CloudBackupService::uploadToCloud($local['filepath']);
            $this->line("  <fg=green>[OK]</> Encrypted Archive: {$local['filename']}");

            // Test 2: Execute Official DR Drill
            $this->newLine();
            $this->info('[Test 2/4] Executing Disaster Recovery Drill with Authorized Air-Gapped Key...');
            $drill = DisasterRecoveryService::runDrill($local['filename']);
            $this->line("  <fg=green>[OK]</> RTO Achieved: {$drill['total_rto_ms']} ms | RPO: {$drill['rpo_status']}");
            $this->line("  <fg=green>[OK]</> Statements Verified: {$drill['statement_count']} rows");

            // Test 3: Verify Status Updated to verified_restorable in Supabase Database
            $this->newLine();
            $this->info('[Test 3/4] Verifying Supabase BackupLog Registry Updated to verified_restorable...');
            $log = BackupLog::where('filename', $local['filename'])->latest()->first();
            if ($log && $log->status === 'verified_restorable' && !empty($log->restoration_tested_at)) {
                $this->line("  <fg=green>[OK]</> Database Status: {$log->status} (Tested At: {$log->restoration_tested_at})");
            } else {
                throw new \RuntimeException("Supabase backup registry was not updated to verified_restorable.");
            }

            // Test 4: Simulate Corrupted Key / Tampered Archive Attack Defense
            $this->newLine();
            $this->info('[Test 4/4] Simulating Corrupted Key / Attacker Intrusion Defense...');
            $fakeKeyFile = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'offline-keys' . DIRECTORY_SEPARATOR . 'fake_attacker_key.key';
            file_put_contents($fakeKeyFile, "AGE-SECRET-KEY-10000000000000000000000000000000000000000000000000000000000\n");

            $attackBlocked = false;
            try {
                DisasterRecoveryService::runDrill($local['filename'], $fakeKeyFile);
            } catch (\Throwable $e) {
                $attackBlocked = true;
                $this->line("  <fg=green>[BLOCKED]</> Unauthorized / Corrupted key recovery blocked: " . substr($e->getMessage(), 0, 80) . '...');
            } finally {
                @unlink($fakeKeyFile);
            }

            if (!$attackBlocked) {
                throw new \RuntimeException("Security failure: Drill should not succeed with invalid attacker key!");
            }

            $this->newLine();
            $this->info('==========================================================');
            $this->info('  [SUCCESS] All Disaster Recovery & Restore Drill Tests Passed!');
            $this->info('==========================================================');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Disaster recovery test failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
