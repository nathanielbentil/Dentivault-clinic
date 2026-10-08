<?php

namespace Tests\Unit;

use App\Services\PasswordService;
use Tests\TestCase;

class PasswordSecurityTest extends TestCase
{
    public function test_argon2id_hashes_with_configured_memory_and_threads(): void
    {
        $password = 'DefendSecret@2026!';
        $result = PasswordService::hash($password);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hash', $result);
        $this->assertArrayHasKey('pepper_version', $result);
        $this->assertStringStartsWith('$argon2id$', $result['hash']);
        $this->assertStringContainsString('m=65536', $result['hash']); // 64MB RAM
        $this->assertStringContainsString('t=4', $result['hash']);     // 4 time cost
        $this->assertStringContainsString('p=2', $result['hash']);     // 2 parallelism
    }

    public function test_password_verifies_with_active_pepper(): void
    {
        $password = 'CorrectPassword@2026!';
        $result = PasswordService::hash($password);

        $isValid = PasswordService::verify($password, $result['hash'], $result['pepper_version']);
        $this->assertTrue($isValid);

        $isInvalid = PasswordService::verify('WrongPassword@2026!', $result['hash'], $result['pepper_version']);
        $this->assertFalse($isInvalid);
    }

    public function test_pepper_rotation_detects_outdated_version(): void
    {
        $password = 'LegacyPassword@2026!';
        $result = PasswordService::hash($password);

        // Current active version should not need rehash
        $this->assertFalse(PasswordService::needsRehash($result['hash'], $result['pepper_version']));

        // Outdated pepper version should trigger needsRehash
        $this->assertTrue(PasswordService::needsRehash($result['hash'], 'v0_deprecated'));
    }
}
