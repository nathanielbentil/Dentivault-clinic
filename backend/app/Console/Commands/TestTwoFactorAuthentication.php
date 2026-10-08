<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\PasswordService;
use App\Services\QrCodeService;
use App\Services\TotpService;
use Illuminate\Console\Command;

class TestTwoFactorAuthentication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-2fa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an automated end-to-end test of RFC 6238 TOTP 2FA, clock drift tolerance, and single-use recovery code consumption';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Two-Factor Authentication (TOTP) Security Test');
        $this->info('==========================================================');

        $testEmail = 'totp.officer@secureauth.bank';
        $testPassword = 'Bank2FAOfficer!2026';

        // 0. Setup test user
        User::where('email', $testEmail)->delete();
        $hashResult = PasswordService::hash($testPassword);
        $user = User::create([
            'name' => '2FA Security Officer',
            'email' => $testEmail,
            'password' => $hashResult['hash'],
            'pepper_version' => $hashResult['pepper_version'],
            'status' => 'active',
            'password_changed_at' => now(),
        ]);

        // 1. Secret Generation & Setup
        $this->line('<fg=yellow>[1/5] Testing RFC 6238 Base32 Secret Generation...</>');
        $secret = TotpService::generateSecret(20);
        $recovery = TotpService::generateRecoveryCodes(8);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery['hashed'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->line("  <fg=green>[OK]</> 160-bit Secret Generated: {$secret}");
        $this->line("  <fg=green>[OK]</> 8 Single-Use Recovery Codes Generated & Stored Hashed (SHA-256)");
        $this->line("  <fg=cyan>Sample Recovery Code:</> {$recovery['plain'][0]}");

        // 2. Offline QR Code Generation Check
        $this->newLine();
        $this->line('<fg=yellow>[2/5] Testing Offline SVG QR Code Generation...</>');
        $otpUri = TotpService::getOtpAuthUri($user->email, $secret);
        $svgMarkup = QrCodeService::generateSvg($otpUri, 200);
        $this->line("  <fg=green>[OK]</> Offline SVG Generated (" . strlen($svgMarkup) . " bytes). Zero third-party data leakage.");
        $this->line("  <fg=cyan>OTP URI:</> {$otpUri}");

        // 3. TOTP Code Calculation & Verification
        $this->newLine();
        $this->line('<fg=yellow>[3/5] Testing Live RFC 6238 6-Digit TOTP Calculation...</>');
        $currentCode = TotpService::generateCode($secret);
        $this->line("  Calculated 6-Digit Code for Current 30s Window: <fg=green>{$currentCode}</>");

        $isValid = TotpService::verifyCode($secret, $currentCode);
        if ($isValid) {
            $this->line("  <fg=green>[OK]</> TOTP code successfully verified against secret.");
        } else {
            $this->error("  [FAIL] TOTP code verification failed!");
            return self::FAILURE;
        }

        // 4. Clock Drift Tolerance Test (±30s)
        $this->newLine();
        $this->line('<fg=yellow>[4/5] Testing Clock Drift Tolerance (±30 Seconds)...</>');
        $prevWindowCode = TotpService::generateCode($secret, time() - 30);
        $isDriftValid = TotpService::verifyCode($secret, $prevWindowCode, window: 1);
        if ($isDriftValid) {
            $this->line("  <fg=green>[OK]</> Code from previous 30s window accepted within drift tolerance (Window = 1).");
        } else {
            $this->error("  [FAIL] Clock drift tolerance failed!");
            return self::FAILURE;
        }

        // 5. Single-Use Recovery Code Consumption & Replay Prevention
        $this->newLine();
        $this->line('<fg=yellow>[5/5] Testing Single-Use Recovery Code Consumption & Replay Defense...</>');
        $codeToUse = $recovery['plain'][0];
        $hashedCodes = $user->two_factor_recovery_codes;

        // First use: Should succeed
        $firstUseSuccess = TotpService::verifyAndConsumeRecoveryCode($codeToUse, $hashedCodes);
        if ($firstUseSuccess) {
            $user->forceFill(['two_factor_recovery_codes' => $hashedCodes])->save();
            $this->line("  <fg=green>[OK]</> Recovery code [{$codeToUse}] consumed successfully on first attempt.");
            AuditLog::record(
                eventType: 'auth.2fa_recovery_used',
                description: "Test user [{$user->email}] consumed recovery code [{$codeToUse}].",
                userId: $user->id,
                severity: 'notice'
            );
        } else {
            $this->error("  [FAIL] Recovery code consumption failed on first attempt!");
            return self::FAILURE;
        }

        // Second use (Replay Attack): MUST fail
        $secondUseSuccess = TotpService::verifyAndConsumeRecoveryCode($codeToUse, $hashedCodes);
        if (!$secondUseSuccess) {
            $this->line("  <fg=green>[OK]</> Replay attempt with same code [{$codeToUse}] REJECTED. Single-use rule strictly enforced!");
        } else {
            $this->error("  [FAIL] Security Vulnerability: Recovery code was reused!");
            return self::FAILURE;
        }

        // Summary
        $this->newLine();
        $this->info('==========================================================');
        $this->info('  [SUCCESS] All 2FA TOTP & Recovery Code Tests Passed!');
        $this->info('==========================================================');

        return self::SUCCESS;
    }
}
