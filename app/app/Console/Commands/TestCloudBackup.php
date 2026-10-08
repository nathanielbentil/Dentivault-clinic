<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\BackupService;
use App\Services\CloudBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestCloudBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-cloud-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify 3-2-1 Cloud Backup replication, SHA-256 integrity, zero-knowledge encryption and disaster recovery download';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: 3-2-1 Cloud Replication & Integrity Test');
        $this->info('==========================================================');

        try {
            // Step 1: Create local age-encrypted backup
            $this->info('[Step 1/5] Creating Fresh age-Encrypted Local Database Backup...');
            $localBackup = BackupService::createLocalBackup('full_database');
            $this->line("  <fg=green>[OK]</> Local Encrypted Archive: {$localBackup['filename']}");
            $this->line("  Local SHA-256: {$localBackup['sha256_checksum']}");

            // Step 2: Upload to Cloud Storage
            $this->newLine();
            $this->info('[Step 2/5] Replicating Encrypted Archive to Offsite Cloud Storage (Supabase S3)...');
            $cloudSync = CloudBackupService::uploadToCloud($localBackup['filepath']);
            $this->line("  <fg=green>[OK]</> Cloud Destination: {$cloudSync['destination_uri']}");
            $this->line("  Upload Latency: {$cloudSync['upload_duration_ms']} ms | Provider: {$cloudSync['storage_type']}");

            // Step 3: Verify Zero-Knowledge Encryption on Cloud Object
            $this->newLine();
            $this->info('[Step 3/5] Inspecting Cloud Object for Zero-Knowledge Proof...');
            $cloudList = CloudBackupService::listCloudBackups();
            $foundCloudObject = collect($cloudList)->firstWhere('filename', $localBackup['filename']);

            if (!$foundCloudObject) {
                throw new \RuntimeException("Cloud object {$localBackup['filename']} not found in cloud inventory.");
            }

            $this->line("  <fg=green>[OK]</> Cloud Object Exists in Offsite Inventory ({$foundCloudObject['size_bytes']} bytes)");

            // Check if file starts with age header
            $cloudFilePath = CloudBackupService::getCloudMirrorPath() . DIRECTORY_SEPARATOR . $localBackup['filename'];
            $handle = fopen($cloudFilePath, 'rb');
            $header = fread($handle, 30);
            fclose($handle);

            if (str_starts_with($header, 'age-encryption.org/v1')) {
                $this->line("  <fg=green>[OK]</> Zero-Knowledge Verified: Cloud only holds age X25519 ciphertext.");
            } else {
                throw new \RuntimeException("Cloud file is not encrypted with age!");
            }

            // Step 4: Simulate Disaster Recovery Pull & Integrity Match
            $this->newLine();
            $this->info('[Step 4/5] Simulating Disaster Recovery Pull & Cryptographic Hash Verification...');
            $restoreDir = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'test-restore';
            $pulledFile = CloudBackupService::fetchFromCloud($localBackup['filename'], $restoreDir);

            $pulledChecksum = hash_file('sha256', $pulledFile);
            if ($pulledChecksum !== $localBackup['sha256_checksum']) {
                throw new \RuntimeException("Checksum mismatch! Local: {$localBackup['sha256_checksum']}, Pulled: {$pulledChecksum}");
            }

            $this->line("  <fg=green>[SUCCESS]</> Downloaded archive matches 100% SHA-256 checksum: {$pulledChecksum}");
            @unlink($pulledFile);
            @rmdir($restoreDir);

            // Step 5: Verify Cloud Audit Logging in Supabase Database
            $this->newLine();
            $this->info('[Step 5/5] Verifying Supabase Database Backup & Audit Logs...');
            $dbLog = BackupLog::where('filename', $localBackup['filename'])
                ->whereIn('storage_location', ['supabase_s3', 'both'])
                ->latest()
                ->first();

            if ($dbLog && $dbLog->status === 'completed') {
                $this->line("  <fg=green>[OK]</> Confirmed in backup_logs table (ID: {$dbLog->id}, Status: {$dbLog->status})");
            } else {
                throw new \RuntimeException("Backup log not recorded in Supabase database.");
            }

            $this->newLine();
            $this->info('==========================================================');
            $this->info('  [SUCCESS] All 3-2-1 Cloud Replication & Integrity Tests Passed!');
            $this->info('==========================================================');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Cloud backup test failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
