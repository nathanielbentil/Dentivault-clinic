<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateLocalBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create-local {--type=full_database : Backup type (full_database, schema_only)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger an on-demand or scheduled age-encrypted local database backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Automated 3-2-1 Encrypted Backup Pipeline');
        $this->info('==========================================================');

        $this->line('Connecting to Supabase PostgreSQL and streaming backup...');

        try {
            $t0 = microtime(true);
            $result = BackupService::createLocalBackup($this->option('type'));
            $duration = round((microtime(true) - $t0) * 1000, 2);

            $this->info("Encrypted backup completed successfully in {$duration} ms!");
            $this->table(
                ['Parameter', 'Value'],
                [
                    ['Backup File', $result['filename']],
                    ['Storage Location', $result['filepath']],
                    ['File Size', round($result['file_size_bytes'] / 1024, 2) . ' KB (' . $result['file_size_bytes'] . ' bytes)'],
                    ['SHA-256 Checksum', $result['sha256_checksum']],
                    ['Encryption Protocol', 'age X25519 (ChaCha20-Poly1305)'],
                    ['Age Public Key', $result['age_public_key']],
                    ['Supabase Audit Status', '<fg=green>[RECORDED IN BACKUP_LOGS & AUDIT_LOGS]</>'],
                ]
            );

            $this->info('==========================================================');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
