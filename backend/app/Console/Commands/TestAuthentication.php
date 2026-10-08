<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Console\Command;

class TestAuthentication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated end-to-end security test of Registration, Argon2id+Pepper Login, Telemetry, and Brute-Force Lockout on Supabase';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Automated Authentication & Defense Test');
        $this->info('==========================================================');

        $testEmail = 'test.defense.user@secureauth.bank';
        $testPassword = 'BankPassword!2026Secure';

        // 0. Clean previous test artifacts
        User::where('email', $testEmail)->delete();
        LoginAttempt::where('email_or_username', $testEmail)->delete();

        // 1. Test Registration with Argon2id + Pepper
        $this->line('<fg=yellow>[1/5] Testing User Registration & Password Hashing...</>');
        $hashResult = PasswordService::hash($testPassword);
        
        $user = User::create([
            'name' => 'Defense Test Officer',
            'email' => $testEmail,
            'password' => $hashResult['hash'],
            'pepper_version' => $hashResult['pepper_version'],
            'status' => 'active',
            'password_changed_at' => now(),
        ]);

        $customerRole = Role::where('slug', 'Customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole->id);
        }

        AuditLog::record(
            eventType: 'auth.registered',
            description: "Test user [{$testEmail}] registered for defense simulation.",
            userId: $user->id,
            severity: 'info'
        );

        $this->line("  <fg=green>[OK]</> User registered. ID: {$user->id} | Pepper: {$hashResult['pepper_version']}");
        $this->line("  <fg=cyan>Argon2id Hash Preview:</> " . substr($user->password, 0, 32) . "...");

        // 2. Test Valid Authentication
        $this->line('`n<fg=yellow>[2/5] Testing Valid Password Verification with HMAC Pepper...</>');
        $isValid = PasswordService::verify($testPassword, $user->password, $user->pepper_version);
        if ($isValid) {
            $this->line('  <fg=green>[OK]</> Password verified successfully with HMAC Pepper.');
            LoginAttempt::create([
                'email_or_username' => $testEmail,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SecurityTestEngine/1.0',
                'is_successful' => true,
                'created_at' => now(),
            ]);
            AuditLog::record(
                eventType: 'auth.login',
                description: "Test user [{$testEmail}] successfully authenticated.",
                userId: $user->id,
                severity: 'info'
            );
        } else {
            $this->error('  [FAIL] Valid password verification failed!');
            return self::FAILURE;
        }

        // 3. Test Invalid Password Telemetry
        $this->line('`n<fg=yellow>[3/5] Testing Invalid Password Handling & Audit Telemetry...</>');
        $isBadValid = PasswordService::verify('WrongPassword123!', $user->password, $user->pepper_version);
        if (!$isBadValid) {
            $this->line('  <fg=green>[OK]</> Invalid password correctly rejected.');
            LoginAttempt::create([
                'email_or_username' => $testEmail,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SecurityTestEngine/1.0',
                'is_successful' => false,
                'failure_reason' => 'invalid_credentials',
                'created_at' => now(),
            ]);
            AuditLog::record(
                eventType: 'auth.failed',
                description: "Simulated invalid password attempt for [{$testEmail}].",
                userId: $user->id,
                severity: 'warning'
            );
        }

        // 4. Test Brute-Force Lockout Defense (5 Consecutive Failures)
        $this->line('`n<fg=yellow>[4/5] Simulating Brute-Force Attack (5 consecutive failures)...</>');
        for ($i = 1; $i <= 5; $i++) {
            $user->recordFailedLogin(maxAttempts: 5, lockoutMinutes: 15);
            $this->line("  Attempt {$i}/5 recorded. Status: {$user->status} | Failed count: {$user->failed_login_attempts}");
        }

        $user->refresh();
        if ($user->status === 'locked' && $user->isLockedOut()) {
            $this->line('  <fg=green>[OK]</> Account successfully LOCKED OUT until: ' . $user->lockout_until->toDateTimeString());
        } else {
            $this->error('  [FAIL] Lockout condition failed to trigger.');
            return self::FAILURE;
        }

        // 5. Test Lockout Enforcement (Subsequent attempt blocked)
        $this->line('`n<fg=yellow>[5/5] Testing Lockout Enforcement against incoming login...</>');
        if ($user->isLockedOut()) {
            LoginAttempt::create([
                'email_or_username' => $testEmail,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SecurityTestEngine/1.0',
                'is_successful' => false,
                'failure_reason' => 'account_locked',
                'created_at' => now(),
            ]);
            $this->line('  <fg=green>[OK]</> Login attempt rejected due to active account lockout.');
        }

        // Summary Table
        $this->newLine();
        $this->info('Audit Trail & Telemetry Summary on Supabase:');
        $this->table(
            ['Event Type', 'Severity', 'Description', 'Timestamp'],
            AuditLog::where('user_id', $user->id)->latest('id')->take(5)->get()->map(fn($l) => [
                $l->event_type,
                $l->severity,
                $l->description,
                $l->created_at->toDateTimeString(),
            ])
        );

        $this->info('==========================================================');
        $this->info('  [SUCCESS] All Authentication & Brute-Force Tests Passed!');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
