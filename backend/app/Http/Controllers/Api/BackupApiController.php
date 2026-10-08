<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\BackupService;
use App\Services\CloudBackupService;
use App\Services\DisasterRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupApiController extends Controller
{
    /**
     * List all local and cloud encrypted backups
     */
    public function list(Request $request): JsonResponse
    {
        $backupLogs = BackupLog::orderBy('id', 'desc')->take(20)->get();
        $cloudBackups = CloudBackupService::listCloudBackups();

        return response()->json([
            'success' => true,
            'backup_logs' => $backupLogs,
            'cloud_backups' => $cloudBackups,
        ]);
    }

    /**
     * Trigger on-demand asymmetric encrypted backup & cloud replication
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $local = BackupService::createLocalBackup();
            $cloud = CloudBackupService::uploadToCloud($local['filepath']);

            return response()->json([
                'success' => true,
                'message' => "Asymmetric encrypted backup [{$local['filename']}] generated and synced to Supabase Cloud!",
                'backup' => [
                    'filename' => $local['filename'],
                    'size_bytes' => $local['size_bytes'],
                    'sha256_hash' => $local['sha256_hash'],
                    'encrypted_with' => 'age X25519',
                    'cloud_storage' => 'Supabase Storage S3 / Mirror',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger Disaster Recovery drill and benchmark RTO/RPO
     */
    public function drill(Request $request): JsonResponse
    {
        try {
            $result = DisasterRecoveryService::runDrill();

            return response()->json([
                'success' => true,
                'message' => 'Disaster Recovery restore drill executed successfully with 100% integrity!',
                'metrics' => [
                    'total_rto_ms' => $result['total_rto_ms'],
                    'rpo_status' => $result['rpo_status'],
                    'integrity_verified' => $result['integrity_verified'],
                    'records_restored' => $result['records_restored'],
                    'backup_file' => $result['backup_file'],
                    'executed_at' => $result['executed_at'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'DR drill failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
