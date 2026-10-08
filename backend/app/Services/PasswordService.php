<?php

namespace App\Services;

class PasswordService
{
    /**
     * Hash a raw password using HMAC Pepper + Argon2id.
     */
    public static function hash(string $plainPassword, ?string $pepperVersion = null): array
    {
        $version = $pepperVersion ?? config('security.pepper.current_version', 'v1');
        $pepper = config("security.pepper.versions.{$version}", '');

        if (empty($pepper)) {
            throw new \RuntimeException("Security Error: Pepper version [{$version}] is not configured in .env");
        }

        // Layer 1: Apply server-side HMAC Pepper (256-bit entropy)
        $peppered = hash_hmac('sha256', $plainPassword, $pepper);

        // Layer 2: Argon2id Memory-Hard Hashing with OWASP Parameters
        $options = [
            'memory_cost' => config('security.argon2id.memory', 65536),
            'time_cost' => config('security.argon2id.time', 4),
            'threads' => config('security.argon2id.threads', 2),
        ];

        $hash = password_hash($peppered, PASSWORD_ARGON2ID, $options);

        return [
            'hash' => $hash,
            'pepper_version' => $version,
        ];
    }

    /**
     * Verify a plain password against an existing hash and pepper version.
     */
    public static function verify(string $plainPassword, string $hash, string $pepperVersion = 'v1'): bool
    {
        $pepper = config("security.pepper.versions.{$pepperVersion}", '');

        if (empty($pepper)) {
            return false;
        }

        $peppered = hash_hmac('sha256', $plainPassword, $pepper);

        return password_verify($peppered, $hash);
    }

    /**
     * Check if a password hash needs rehashing (e.g. if algorithm parameters or pepper version changed).
     */
    public static function needsRehash(string $hash, string $userPepperVersion): bool
    {
        $currentVersion = config('security.pepper.current_version', 'v1');
        if ($userPepperVersion !== $currentVersion) {
            return true; // Pepper was rotated; rehash needed on next login
        }

        $options = [
            'memory_cost' => config('security.argon2id.memory', 65536),
            'time_cost' => config('security.argon2id.time', 4),
            'threads' => config('security.argon2id.threads', 2),
        ];

        return password_needs_rehash($hash, PASSWORD_ARGON2ID, $options);
    }
}
