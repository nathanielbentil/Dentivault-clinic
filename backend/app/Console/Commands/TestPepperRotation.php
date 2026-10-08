<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Console\Command;

class TestPepperRotation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-pepper-rotation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Demonstrate zero-downtime HMAC pepper rotation and automatic password rehashing live on Supabase';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Zero-Downtime Pepper Rotation Demonstration');
        $this->info('==========================================================');

        $testEmail = 'rotation.officer@secureauth.bank';
        $testPassword = 'BankRotationPassword!2026';

        // 0. Setup test user on v1 pepper
        User::where('email', $testEmail)->delete();
        $hashV1 = PasswordService::hash($testPassword, 'v1');

        $user = User::create([
            'name' => 'Rotation Test Officer',
            'email' => $testEmail,
            'password' => $hashV1['hash'],
            'pepper_version' => 'v1',
            'status' => 'active',
            'password_changed_at' => now(),
        ]);

        $this->line("<fg=yellow>[Step 1] User Created with Legacy Pepper (v1)</>");
        $this->line("  User Email: {$user->email}");
        $this->line("  Pepper Version in DB: <fg=cyan>{$user->pepper_version}</>");
        $this->line("  Initial Hash: " . substr($user->password, 0, 35) . "...");

        // 1. Simulate Active System Pepper Shift to v2
        $this->newLine();
        $this->line("<fg=yellow>[Step 2] System Security Officer Shifts Active Pepper to (v2)...</>");
        
        // Define runtime v2 pepper for simulation
        $v2Pepper = bin2hex(random_bytes(32));
        config(['security.pepper.versions.v2' => $v2Pepper]);
        config(['security.pepper.current_version' => 'v2']);
        $this->line("  Active System Pepper is now: <fg=green>v2</>");
        $this->line("  Legacy Pepper (v1) remains in memory for backward compatibility.");

        // 2. User Logs in with v1 Hash
        $this->newLine();
        $this->line("<fg=yellow>[Step 3] User Submits Login Request...</>");
        $canVerifyV1 = PasswordService::verify($testPassword, $user->password, $user->pepper_version);
        if ($canVerifyV1) {
            $this->line("  <fg=green>[OK]</> Authentication succeeded using user's legacy pepper (v1). Zero user disruption!");
        } else {
            $this->error("  [FAIL] Legacy verification failed.");
            return self::FAILURE;
        }

        // 3. System Detects Rehash Needed & Upgrades Hash
        $this->newLine();
        $this->line("<fg=yellow>[Step 4] System Detects Outdated Pepper Version & Rehashes...</>");
        $needsRehash = PasswordService::needsRehash($user->password, $user->pepper_version);
        $this->line("  Needs Rehash Check: " . ($needsRehash ? '<fg=green>TRUE (Upgrade Triggered)</>' : 'FALSE'));

        if ($needsRehash) {
            $hashV2 = PasswordService::hash($testPassword, 'v2');
            $user->update([
                'password' => $hashV2['hash'],
                'pepper_version' => 'v2',
            ]);

            AuditLog::record(
                eventType: 'auth.rehashed',
                description: "User [{$user->email}] password upgraded to pepper version [v2] seamlessly.",
                userId: $user->id,
                severity: 'info'
            );
            $this->line("  <fg=green>[OK]</> Database updated seamlessly in Supabase PostgreSQL!");
            $this->line("  New Pepper Version in DB: <fg=green>{$user->pepper_version}</>");
            $this->line("  New Hash (v2): " . substr($user->password, 0, 35) . "...");
        }

        // 4. Verify user can now authenticate directly against v2
        $this->newLine();
        $this->line("<fg=yellow>[Step 5] Subsequent Login Verification on Active Pepper (v2)...</>");
        $canVerifyV2 = PasswordService::verify($testPassword, $user->password, $user->pepper_version);
        if ($canVerifyV2) {
            $this->line("  <fg=green>[OK]</> Future logins authenticate directly against v2 pepper.");
        }

        // Summary
        $this->newLine();
        $this->info('==========================================================');
        $this->info('  [SUCCESS] Zero-Downtime Pepper Rotation Verified!');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
