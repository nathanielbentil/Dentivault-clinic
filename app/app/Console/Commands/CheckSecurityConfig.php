<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckSecurityConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:check-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a comprehensive security pre-flight check of the environment and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth Banking & Security System: Config Pre-Flight');
        $this->info('==========================================================');

        $checks = [];

        // 1. App Key Check
        $appKey = config('app.key');
        $checks[] = [
            'Check' => 'Application Encryption Key (APP_KEY)',
            'Status' => !empty($appKey) && str_starts_with($appKey, 'base64:'),
            'Value' => !empty($appKey) ? 'Configured (AES-256-CBC)' : 'MISSING',
            'Severity' => 'CRITICAL',
        ];

        // 2. Pepper Check
        $currentVersion = config('security.pepper.current_version', 'v1');
        $pepper = config("security.pepper.versions.{$currentVersion}");
        $checks[] = [
            'Check' => "HMAC Password Pepper ({$currentVersion})",
            'Status' => !empty($pepper) && strlen($pepper) >= 32,
            'Value' => !empty($pepper) ? (strlen($pepper) * 4) . '-bit entropy active' : 'MISSING / EMPTY',
            'Severity' => 'CRITICAL',
        ];

        // 3. Database Connection Check
        $dbDriver = config('database.default');
        $dbHost = config("database.connections.{$dbDriver}.host");
        $checks[] = [
            'Check' => 'Database Driver (PostgreSQL)',
            'Status' => $dbDriver === 'pgsql',
            'Value' => "Driver: {$dbDriver} (Host: " . ($dbHost ?: 'not set') . ')',
            'Severity' => 'HIGH',
        ];

        // 4. Session Encryption Check
        $sessionEncrypt = config('session.encrypt');
        $checks[] = [
            'Check' => 'Session Payload Encryption',
            'Status' => $sessionEncrypt === true,
            'Value' => $sessionEncrypt ? 'Enabled (Encrypted Cookies)' : 'Disabled (Plain Text)',
            'Severity' => 'MEDIUM',
        ];

        // 5. Argon2id Configuration Check
        $argonMemory = config('security.argon2id.memory', 65536);
        $checks[] = [
            'Check' => 'Argon2id Memory Cost (OWASP Rec >= 64MB)',
            'Status' => $argonMemory >= 65536,
            'Value' => ($argonMemory / 1024) . ' MB (' . $argonMemory . ' KB)',
            'Severity' => 'MEDIUM',
        ];

        // 6. Mail Driver Check
        $mailer = config('mail.default');
        $checks[] = [
            'Check' => 'Mail Transport (Mailpit / Sandbox)',
            'Status' => in_array($mailer, ['smtp', 'log']),
            'Value' => "Mailer: {$mailer} (Host: " . config('mail.mailers.smtp.host') . ')',
            'Severity' => 'LOW',
        ];

        $allOk = true;
        $tableData = [];

        foreach ($checks as $c) {
            $statusStr = $c['Status'] ? '<fg=green>[PASS]</>' : "<fg=red>[FAIL - {$c['Severity']}]</>";
            if (!$c['Status'] && in_array($c['Severity'], ['CRITICAL', 'HIGH'])) {
                $allOk = false;
            }
            $tableData[] = [
                'Check' => $c['Check'],
                'Status' => $statusStr,
                'Details' => $c['Value'],
            ];
        }

        $this->table(['Security Parameter', 'Status', 'Configuration Details'], $tableData);

        $this->newLine();
        if ($allOk) {
            $this->info('  [OK] Security pre-flight checks passed!');
        } else {
            $this->warn('  [WARNING] Some critical security parameters require configuration in .env');
        }
        $this->info('==========================================================');

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
