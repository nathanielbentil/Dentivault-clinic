<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Security & Pepper Configuration
    |--------------------------------------------------------------------------
    | The HMAC pepper is a server-side secret applied before Argon2id hashing.
    | Even if the database is leaked via SQL injection or backup theft,
    | passwords cannot be cracked without this server pepper.
    |
    | Peppers are versioned to support seamless zero-downtime rotation.
    */
    'pepper' => [
        'current_version' => env('CURRENT_PEPPER_VERSION', 'v1'),
        'versions' => [
            'v1' => env('PEPPER_V1', ''),
            'v2' => env('PEPPER_V2', ''),
            'v3' => env('PEPPER_V3', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon2id Cryptographic Parameters
    |--------------------------------------------------------------------------
    | OWASP & NIST recommended minimums for high-security banking systems.
    | Memory: 64MB (65536 KB), Time/Iterations: 4, Parallelism: 2 threads
    */
    'argon2id' => [
        'memory' => (int) env('ARGON2ID_MEMORY_COST', 65536),
        'time' => (int) env('ARGON2ID_TIME_COST', 4),
        'threads' => (int) env('ARGON2ID_THREADS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication (TOTP & Recovery)
    |--------------------------------------------------------------------------
    */
    'two_factor' => [
        'issuer' => env('TWO_FACTOR_ISSUER', 'SecureAuth Banking'),
        'window' => (int) env('TWO_FACTOR_WINDOW', 1), // ±30s clock drift tolerance
        'recovery_codes_count' => (int) env('TWO_FACTOR_RECOVERY_CODES_COUNT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Backup & Age Encryption
    |--------------------------------------------------------------------------
    | Only the public key resides on the application server.
    | The private key is offline (air-gapped) for defense against ransomware.
    */
    'backup' => [
        'age_public_key' => env('BACKUP_AGE_PUBLIC_KEY', ''),
        'retention_days_local' => 7,
        'retention_days_cloud' => 30,
        's3' => [
            'key' => env('BACKUP_S3_KEY', ''),
            'secret' => env('BACKUP_S3_SECRET', ''),
            'region' => env('BACKUP_S3_REGION', 'eu-west-2'),
            'bucket' => env('BACKUP_S3_BUCKET', 'secureauth-backups'),
            'endpoint' => env('BACKUP_S3_ENDPOINT', ''),
        ],
    ],

];
