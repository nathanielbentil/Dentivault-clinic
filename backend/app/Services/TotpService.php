<?php

namespace App\Services;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a cryptographically secure Base32 secret (160-bit entropy / 20 bytes).
     */
    public static function generateSecret(int $bytes = 20): string
    {
        $random = random_bytes($bytes);
        return self::base32Encode($random);
    }

    /**
     * Generate the standard otpauth:// URI for authenticator apps.
     */
    public static function getOtpAuthUri(string $email, string $secret, ?string $issuer = null): string
    {
        $issuerName = $issuer ?? config('security.two_factor.issuer', 'SecureAuth Banking');
        $encodedIssuer = rawurlencode($issuerName);
        $encodedEmail = rawurlencode($email);

        return "otpauth://totp/{$encodedIssuer}:{$encodedEmail}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Calculate the 6-digit TOTP code for a given timestamp.
     * Implements RFC 6238 & RFC 4226 (HOTP/TOTP algorithm).
     */
    public static function generateCode(string $secret, ?int $timestamp = null, int $period = 30): string
    {
        $time = $timestamp ?? time();
        $counter = floor($time / $period);

        // Pack counter into 8-byte big-endian binary string
        $binaryCounter = pack('J', $counter);
        if (PHP_INT_SIZE < 8) {
            $binaryCounter = str_pad(pack('N', $counter), 8, "\0", STR_PAD_LEFT);
        }

        $binaryKey = self::base32Decode($secret);

        // HMAC-SHA1 calculation
        $hash = hash_hmac('sha1', $binaryCounter, $binaryKey, true);

        // Dynamic Truncation (RFC 4226 Section 5.3)
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binaryCode = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binaryCode % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a submitted 6-digit TOTP code allowing for clock drift window.
     */
    public static function verifyCode(string $secret, string $submittedCode, int $window = 1, int $period = 30): bool
    {
        $submittedCode = trim($submittedCode);
        if (strlen($submittedCode) !== 6 || !ctype_digit($submittedCode)) {
            return false;
        }

        $currentTime = time();

        // Check current window and ±$window adjacent windows (clock drift tolerance)
        for ($i = -$window; $i <= $window; $i++) {
            $testTime = $currentTime + ($i * $period);
            $calculatedCode = self::generateCode($secret, $testTime, $period);

            if (hash_equals($calculatedCode, $submittedCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate 8 cryptographically secure single-use recovery codes.
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < $count; $i++) {
            // Format: XXXXX-XXXXX (e.g. A3F9B-7C2E1)
            $part1 = strtoupper(bin2hex(random_bytes(3))); // 6 hex
            $part2 = strtoupper(bin2hex(random_bytes(3)));
            $code = substr($part1, 0, 5) . '-' . substr($part2, 0, 5);

            $plainCodes[] = $code;
            $hashedCodes[] = [
                'code_hash' => hash('sha256', $code),
                'used_at' => null,
            ];
        }

        return [
            'plain' => $plainCodes,
            'hashed' => $hashedCodes,
        ];
    }

    /**
     * Verify and consume a single-use recovery code.
     */
    public static function verifyAndConsumeRecoveryCode(string $submittedCode, array &$hashedCodes): bool
    {
        $submittedCode = strtoupper(trim($submittedCode));
        $submittedHash = hash('sha256', $submittedCode);

        foreach ($hashedCodes as &$entry) {
            if ($entry['used_at'] === null && hash_equals($entry['code_hash'], $submittedHash)) {
                $entry['used_at'] = now()->toIso8601String();
                return true; // Single-use consumed!
            }
        }

        return false;
    }

    /**
     * Base32 encoding helper.
     */
    private static function base32Encode(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $fiveBitChunks = str_split($binaryString, 5);
        $encoded = '';

        foreach ($fiveBitChunks as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    /**
     * Base32 decoding helper.
     */
    private static function base32Decode(string $base32): string
    {
        $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32));
        if (empty($base32)) {
            return '';
        }

        $binaryString = '';
        foreach (str_split($base32) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $eightBitChunks = str_split($binaryString, 8);
        $decoded = '';

        foreach ($eightBitChunks as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}
