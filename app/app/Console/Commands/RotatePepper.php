<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class RotatePepper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:rotate-pepper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a zero-downtime HMAC pepper rotation by incrementing the active version in .env';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Zero-Downtime HMAC Pepper Rotation');
        $this->info('==========================================================');

        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            $this->error('.env file not found.');
            return self::FAILURE;
        }

        $envContent = file_get_contents($envFile);

        // Determine current version
        $currentVersion = config('security.pepper.current_version', 'v1');
        $currentNum = (int) str_replace('v', '', $currentVersion);
        $nextNum = $currentNum + 1;
        $nextVersion = 'v' . $nextNum;
        $nextVarName = 'PEPPER_V' . $nextNum;

        $newPepper = bin2hex(random_bytes(32)); // 256-bit entropy

        // Append new pepper version and switch CURRENT_PEPPER_VERSION
        if (str_contains($envContent, "{$nextVarName}=")) {
            $envContent = preg_replace("/^{$nextVarName}=.*$/m", "{$nextVarName}=" . $newPepper, $envContent);
        } else {
            $envContent .= "\n{$nextVarName}=" . $newPepper;
        }

        $envContent = preg_replace('/^CURRENT_PEPPER_VERSION=.*$/m', "CURRENT_PEPPER_VERSION={$nextVersion}", $envContent);

        file_put_contents($envFile, $envContent);

        AuditLog::record(
            eventType: 'system.pepper_rotated',
            description: "Zero-downtime pepper rotation initiated: Active version shifted from [{$currentVersion}] to [{$nextVersion}].",
            severity: 'notice',
            payload: ['old_version' => $currentVersion, 'new_version' => $nextVersion]
        );

        $this->info("Successfully rotated active pepper version: <fg=cyan>{$currentVersion}</> -> <fg=green>{$nextVersion}</>");
        $this->line("  <fg=yellow>New Key:</> {$nextVarName} configured with 256-bit CSPRNG entropy.");
        $this->line("  <fg=yellow>Zero-Downtime Guarantee:</> Existing users on {$currentVersion} can still log in; their hashes will automatically upgrade to {$nextVersion} upon authentication.");
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
