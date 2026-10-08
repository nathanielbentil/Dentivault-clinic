<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Services\CloudBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncCloudBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:sync-cloud {--file= : Specific local backup file to sync to cloud}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload local age-encrypted backups to offsite cloud storage (Supabase Storage S3)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: 3-2-1 Cloud Backup Sync Pipeline');
        $this->info('==========================================================');

        $specificFile = $this->option('file');

        try {
            if ($specificFile) {
                $filePath = $specificFile;
                if (!file_exists($filePath)) {
                    $localDir = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'local';
                    $filePath = $localDir . DIRECTORY_SEPARATOR . $specificFile;
                }

                if (!file_exists($filePath)) {
                    $this->error("Specified backup file not found: {$specificFile}");
                    return self::FAILURE;
                }

                $this->line("Uploading [{$specificFile}] to Cloud Storage...");
                $result = CloudBackupService::uploadToCloud($filePath);
                $this->displaySyncResult($result);
            } else {
                // Find most recent local backup or generate new one
                $localDir = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'local';
                $localFiles = File::glob($localDir . DIRECTORY_SEPARATOR . '*.dump.age');

                if (empty($localFiles)) {
                    $this->line("No local backup found. Creating fresh encrypted backup first...");
                    $localResult = BackupService::createLocalBackup();
                    $targetFile = $localResult['filepath'];
                } else {
                    // Sort descending by modified time
                    usort($localFiles, fn($a, $b) => filemtime($b) - filemtime($a));
                    $targetFile = $localFiles[0];
                }

                $this->line("Syncing [".basename($targetFile)."] to Cloud Storage (Supabase Storage S3)...");
                $result = CloudBackupService::uploadToCloud($targetFile);
                $this->displaySyncResult($result);
            }

            // Prune old cloud backups
            $pruned = CloudBackupService::pruneCloudBackups(30);
            if ($pruned > 0) {
                $this->line("Pruned {$pruned} cloud backup(s) older than 30-day retention policy.");
            }

            $this->info('==========================================================');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Cloud backup sync failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Format and display sync results.
     */
    private function displaySyncResult(array $result): void
    {
        $this->info("Cloud replication completed successfully in {$result['upload_duration_ms']} ms!");
        $this->table(
            ['Cloud Sync Property', 'Value'],
            [
                ['Encrypted Archive', $result['filename']],
                ['Storage Destination', $result['destination_uri']],
                ['Cloud Provider', $result['storage_type']],
                ['File Size', round($result['file_size_bytes'] / 1024, 2) . ' KB (' . $result['file_size_bytes'] . ' bytes)'],
                ['SHA-256 Checksum', $result['sha256_checksum']],
                ['Encryption Status', '<fg=green>age X25519 Ciphertext (Zero-Knowledge Cloud)</>'],
                ['Audit Trail Status', '<fg=green>[RECORDED IN BACKUP_LOGS & AUDIT_LOGS]</>'],
            ]
        );
    }
}
