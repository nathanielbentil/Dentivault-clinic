<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\CloudBackupService;
use App\Services\QrCodeService;
use App\Services\TotpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestFrontendViews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:test-frontend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Render and validate all Blade views, layouts, and interactive UI components';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==========================================================');
        $this->info('  SecureAuth: Frontend & Blade View Validation Suite');
        $this->info('==========================================================');

        $user = User::first() ?? User::factory()->make(['name' => 'Demo User', 'email' => 'test@secureauth.bank']);
        \Illuminate\Support\Facades\Auth::login($user);
        $otpUri = TotpService::getOtpAuthUri($user->email, 'JBSWY3DPEHPK3PXP');
        $sampleQrSvg = QrCodeService::generateSvg($otpUri, 200);

        $viewsToTest = [
            'Landing Showcase' => fn() => View::make('welcome')->render(),
            'Login View (Lockout Notice)' => fn() => View::make('auth.login')->render(),
            'Register View (NIST Meter)' => fn() => View::make('auth.register')->render(),
            '2FA Challenge (TOTP/Emergency)' => fn() => View::make('auth.two-factor-challenge')->render(),
            'Forgot Password' => fn() => View::make('auth.forgot-password')->render(),
            'Reset Password' => fn() => View::make('auth.reset-password', ['token' => 'test-token', 'email' => 'test@bank.com'])->render(),
            'Verify Email' => fn() => View::make('auth.verify-email')->render(),
            'Customer Dashboard' => fn() => View::make('dashboard', [
                'user' => $user,
                'accountData' => [
                    'account_number' => 'SA-00000001',
                    'account_type' => 'Checking',
                    'available_balance' => '$10,000.00',
                    'security_level' => 'Tier 3',
                    'recent_logins' => AuditLog::take(3)->get(),
                ],
            ])->render(),
            '2FA Security Profile (SVG QR)' => fn() => View::make('profile.two-factor-authentication', [
                'user' => $user,
                'qrCodeSvg' => $sampleQrSvg,
                'secretKey' => 'JBSWY3DPEHPK3PXP',
                'enabled' => false,
            ])->render(),
            'SuperAdmin Command Center' => fn() => View::make('admin.dashboard', [
                'users' => User::paginate(10),
                'roles' => Role::all(),
                'stats' => ['total_users' => 4, 'active_users' => 4, 'locked_users' => 0, 'audit_events_count' => 15],
            ])->render(),
            '3-2-1 Backups & DR Hub' => fn() => View::make('admin.backups', [
                'backupLogs' => BackupLog::paginate(10),
                'localBackups' => CloudBackupService::listCloudBackups(),
            ])->render(),
            'Auditor Forensics Hub' => fn() => View::make('audit.dashboard', [
                'logs' => AuditLog::paginate(10),
                'securityEvents' => SecurityEvent::take(5)->get(),
            ])->render(),
            'Branch Customer Directory' => fn() => View::make('branch.dashboard', [
                'customers' => User::paginate(10),
            ])->render(),
        ];

        $passed = 0;
        $failed = 0;

        foreach ($viewsToTest as $viewName => $renderCallback) {
            try {
                $html = $renderCallback();
                $sizeKb = round(strlen($html) / 1024, 2);
                $this->line("  <fg=green>[RENDER OK]</> {$viewName} ({$sizeKb} KB rendered)");
                $passed++;
            } catch (\Throwable $e) {
                $this->error("  [RENDER FAILED] {$viewName}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("==========================================================");
        if ($failed === 0) {
            $this->info("  [SUCCESS] All {$passed}/{$passed} Blade Views & Portals Rendered 100%!");
            $this->info("==========================================================");
            return self::SUCCESS;
        }

        $this->error("  [FAILURE] {$failed} views failed rendering.");
        $this->info("==========================================================");
        return self::FAILURE;
    }
}
