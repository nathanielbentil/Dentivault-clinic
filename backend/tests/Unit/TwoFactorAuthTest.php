<?php

namespace Tests\Unit;

use App\Services\QrCodeService;
use App\Services\TotpService;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    public function test_generates_valid_rfc6238_base32_secret(): void
    {
        $secret = TotpService::generateSecret();

        $this->assertIsString($secret);
        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_offline_svg_qr_code_contains_valid_otpauth_uri(): void
    {
        $email = 'user@secureauth.com';
        $secret = TotpService::generateSecret();
        $uri = TotpService::getOtpAuthUri($email, $secret);

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=' . $secret, $uri);

        $svg = QrCodeService::generateSvg($uri, 200);
        $this->assertStringStartsWith('<svg', trim($svg));
        $this->assertStringEndsWith('</svg>', trim($svg));
    }

    public function test_totp_code_verification_with_clock_drift(): void
    {
        $secret = TotpService::generateSecret();
        $code = TotpService::generateCode($secret);

        $this->assertEquals(6, strlen($code));
        $this->assertTrue(TotpService::verifyCode($secret, $code, window: 1));
        $this->assertFalse(TotpService::verifyCode($secret, '000000', window: 1));
    }

    public function test_single_use_recovery_codes_consumption_and_replay_defense(): void
    {
        $recovery = TotpService::generateRecoveryCodes(8);

        $this->assertCount(8, $recovery['plain']);
        $this->assertCount(8, $recovery['hashed']);

        $testCode = $recovery['plain'][0];
        $hashedList = $recovery['hashed'];

        // 1. Consume code on first attempt
        $consumed = TotpService::verifyAndConsumeRecoveryCode($testCode, $hashedList);
        $this->assertTrue($consumed);
        $this->assertNotNull($hashedList[0]['used_at']);

        // 2. Replay attempt with same code must fail
        $replayed = TotpService::verifyAndConsumeRecoveryCode($testCode, $hashedList);
        $this->assertFalse($replayed);
    }
}
