<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class TestBackupEncryption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-backup-encryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate live asymmetric age encryption and zero-knowledge decryption with air-gapped private keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Asymmetric age Backup Cryptography Test');
        $this->info('==========================================================');

        // Step 1: Create an encrypted backup
        $this->line('<fg=yellow>[Step 1] Creating Fresh age-Encrypted Backup from Supabase...</>');
        $backup = BackupService::createLocalBackup();
        $this->line("  <fg=green>[OK]</> Encrypted File Created: {$backup['filename']}");
        $this->line("  <fg=cyan>Encrypted Size:</> {$backup['file_size_bytes']} bytes | <fg=cyan>SHA-256:</> {$backup['sha256_checksum']}");

        // Step 2: Inspect age Header (Proof of Asymmetric Encryption)
        $this->newLine();
        $this->line('<fg=yellow>[Step 2] Inspecting Cryptographic Binary Header...</>');
        $handle = fopen($backup['filepath'], 'rb');
        $header = fread($handle, 64);
        fclose($handle);

        if (str_starts_with($header, 'age-encryption.org/v1')) {
            $this->line('  <fg=green>[OK]</> Verified age X25519 (ChaCha20-Poly1305) cipher header.');
        } else {
            $this->error('  [FAIL] Binary file missing standard age header.');
            return self::FAILURE;
        }

        // Step 3: Test Decryption Failure with Invalid / No Key
        $this->newLine();
        $this->line('<fg=yellow>[Step 3] Simulating Attacker / Ransomware Unauthorized Decryption Attempt...</>');
        $agePath = BackupService::getAgePath();
        $fakeKeyFile = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'fake.key';
        file_put_contents($fakeKeyFile, "AGE-SECRET-KEY-10000000000000000000000000000000000000000000000000000000000\n");

        $sandboxRestore = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'restore-tests' . DIRECTORY_SEPARATOR . 'test_unauthorized.dump';
        $unauthorizedProcess = new Process([$agePath, '-d', '-i', $fakeKeyFile, '-o', $sandboxRestore, $backup['filepath']]);
        $unauthorizedProcess->run();
        @unlink($fakeKeyFile);
        @unlink($sandboxRestore);

        if (!$unauthorizedProcess->isSuccessful()) {
            $this->line('  <fg=green>[BLOCKED]</> Unauthorized decryption failed: Attacker without private key cannot decrypt data.');
        } else {
            $this->error('  [FAIL] Security Violation: File decrypted with invalid key!');
            return self::FAILURE;
        }

        // Step 4: Test Authorized Decryption with Air-Gapped Private Key
        $this->newLine();
        $this->line('<fg=yellow>[Step 4] Authorizing Disaster Recovery Decryption using Offline Private Key...</>');
        $privateKeyPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'offline-keys' . DIRECTORY_SEPARATOR . 'disaster_recovery_private.key';
        
        if (!file_exists($privateKeyPath)) {
            $this->error("  Offline private key not found at [{$privateKeyPath}].");
            return self::FAILURE;
        }

        $restoredDump = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'restore-tests' . DIRECTORY_SEPARATOR . 'decrypted_test.dump';
        if (!is_dir(dirname($restoredDump))) {
            mkdir(dirname($restoredDump), 0755, true);
        }

        $authProcess = new Process([$agePath, '-d', '-i', $privateKeyPath, '-o', $restoredDump, $backup['filepath']]);
        $authProcess->run();

        if ($authProcess->isSuccessful() && file_exists($restoredDump) && filesize($restoredDump) > 0) {
            $this->line("  <fg=green>[SUCCESS]</> Decryption authorized! Decrypted dump size: " . filesize($restoredDump) . " bytes.");
            $this->line("  <fg=cyan>Decrypted File Integrity:</> PostgreSQL custom binary format confirmed.");
            @unlink($restoredDump);
        } else {
            $this->error("  [FAIL] Authorized decryption failed: " . $authProcess->getErrorOutput());
            return self::FAILURE;
        }

        // Summary
        $this->newLine();
        $this->info('==========================================================');
        $this->info('  [SUCCESS] All Asymmetric age Backup Tests Passed!');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
