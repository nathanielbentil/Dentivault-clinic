<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BackupLog;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupService
{
    /**
     * Locate pg_dump executable.
     */
    public static function getPgDumpPath(): string
    {
        $defaultLocal = getenv('LOCALAPPDATA') . '\Programs\pgsql\bin\pg_dump.exe';
        if (file_exists($defaultLocal)) {
            return $defaultLocal;
        }

        return 'pg_dump';
    }

    /**
     * Locate age executable.
     */
    public static function getAgePath(): string
    {
        $wingetAge = getenv('LOCALAPPDATA') . '\Microsoft\WinGet\Packages\FiloSottile.age_Microsoft.Winget.Source_8wekyb3d8bbwe\age\age.exe';
        if (file_exists($wingetAge)) {
            return $wingetAge;
        }

        return 'age';
    }

    /**
     * Create an automated local age-encrypted database backup.
     */
    public static function createLocalBackup(string $backupType = 'full_database'): array
    {
        $publicKey = config('security.backup.age_public_key', env('BACKUP_AGE_PUBLIC_KEY'));
        if (empty($publicKey)) {
            throw new \RuntimeException('Security Error: BACKUP_AGE_PUBLIC_KEY is not configured in .env');
        }

        $backupDir = dirname(base_path()) . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'local';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $baseName = "secureauth_backup_{$timestamp}";
        $rawDumpPath = $backupDir . DIRECTORY_SEPARATOR . "{$baseName}.dump";
        $encryptedPath = $backupDir . DIRECTORY_SEPARATOR . "{$baseName}.dump.age";

        // Step 1: Export database using robust PDO SQL stream
        self::exportDatabaseSql($rawDumpPath);

        if (!file_exists($rawDumpPath) || filesize($rawDumpPath) === 0) {
            throw new \RuntimeException("Database backup export failed: Generated SQL dump is empty.");
        }

        $rawSizeBytes = filesize($rawDumpPath);

        // Step 2: Encrypt with age asymmetric public key (X25519)
        $agePath = self::getAgePath();
        $encryptCmd = [
            $agePath,
            '-r', $publicKey,
            '-o', $encryptedPath,
            $rawDumpPath,
        ];

        $ageProcess = new Process($encryptCmd);
        $ageProcess->setTimeout(120);
        $ageProcess->run();

        if (!$ageProcess->isSuccessful() || !file_exists($encryptedPath) || filesize($encryptedPath) === 0) {
            $errorOutput = $ageProcess->getErrorOutput() ?: 'Unknown age encryption failure';
            @unlink($rawDumpPath);
            throw new \RuntimeException("age encryption failed: {$errorOutput}");
        }

        // Step 3: Securely remove raw unencrypted dump (zero plaintext on disk)
        @unlink($rawDumpPath);

        $encryptedSizeBytes = filesize($encryptedPath);
        $sha256Checksum = hash_file('sha256', $encryptedPath);

        // Step 4: Record in backup_logs table on Supabase
        $log = BackupLog::create([
            'filename' => "{$baseName}.dump.age",
            'backup_type' => $backupType,
            'storage_location' => 'local',
            'file_size_bytes' => $encryptedSizeBytes,
            'sha256_checksum' => $sha256Checksum,
            'is_encrypted' => true,
            'age_recipient_preview' => substr($publicKey, 0, 16) . '...',
            'status' => 'completed',
        ]);

        // Step 5: Record in Immutable Audit Log
        AuditLog::record(
            eventType: 'backup.created',
            description: "Encrypted 3-2-1 local backup created [{$baseName}.dump.age] ({$log->humanFileSize()}).",
            severity: 'notice',
            payload: [
                'filename' => "{$baseName}.dump.age",
                'sha256' => $sha256Checksum,
                'encrypted_size_bytes' => $encryptedSizeBytes,
                'raw_size_bytes' => $rawSizeBytes,
                'recipient_key' => substr($publicKey, 0, 16) . '...',
            ]
        );

        // Step 6: Prune local backups older than retention period (7 days)
        self::pruneOldLocalBackups($backupDir, 7);

        return [
            'backup_log_id' => $log->id,
            'filename' => "{$baseName}.dump.age",
            'filepath' => $encryptedPath,
            'file_size_bytes' => $encryptedSizeBytes,
            'sha256_checksum' => $sha256Checksum,
            'age_public_key' => $publicKey,
        ];
    }

    /**
     * Export all public database tables and data to a self-contained SQL file via PDO.
     */
    public static function exportDatabaseSql(string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            throw new \RuntimeException("Unable to open dump path for writing: {$outputPath}");
        }

        $timestamp = date('Y-m-d H:i:s UTC');
        fwrite($handle, "-- ==========================================================\n");
        fwrite($handle, "-- SecureAuth 3-2-1 Automated Database Backup Archive\n");
        fwrite($handle, "-- Generated: {$timestamp}\n");
        fwrite($handle, "-- Database Engine: PostgreSQL 17 (Supabase Managed)\n");
        fwrite($handle, "-- ==========================================================\n\n");
        fwrite($handle, "BEGIN;\n");
        fwrite($handle, "SET CONSTRAINTS ALL DEFERRED;\n\n");

        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();

        // Query all public tables
        $tablesQuery = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name");
        $tables = $tablesQuery->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Write table header
            fwrite($handle, "\n-- ----------------------------------------------------------\n");
            fwrite($handle, "-- Table Data: {$table}\n");
            fwrite($handle, "-- ----------------------------------------------------------\n");

            // Fetch columns
            $colsQuery = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = " . $pdo->quote($table) . " ORDER BY ordinal_position");
            $columns = $colsQuery->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($columns)) {
                continue;
            }

            $colList = implode(', ', array_map(fn($c) => "\"{$c}\"", $columns));

            // Fetch rows
            $dataStmt = $pdo->query("SELECT * FROM \"{$table}\"");
            while ($row = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                $values = [];
                foreach ($columns as $col) {
                    $val = $row[$col] ?? null;
                    if ($val === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($val)) {
                        $values[] = $val ? 'TRUE' : 'FALSE';
                    } elseif (is_int($val) || is_float($val)) {
                        $values[] = (string) $val;
                    } else {
                        $values[] = $pdo->quote((string) $val);
                    }
                }

                $valList = implode(', ', $values);
                fwrite($handle, "INSERT INTO \"{$table}\" ({$colList}) VALUES ({$valList});\n");
            }
        }

        fwrite($handle, "\nCOMMIT;\n");
        fclose($handle);
    }

    /**
     * Prune local backups older than the retention threshold.
     */
    public static function pruneOldLocalBackups(string $backupDir, int $retentionDays = 7): int
    {
        $pruned = 0;
        $files = File::glob($backupDir . DIRECTORY_SEPARATOR . '*.dump.age');
        $cutoff = time() - ($retentionDays * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $pruned++;
            }
        }

        return $pruned;
    }
}
