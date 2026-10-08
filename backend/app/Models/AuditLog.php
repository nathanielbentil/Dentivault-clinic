<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // Append-only (uses created_at)

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'description',
        'ip_address',
        'user_agent',
        'payload',
        'prev_hash',
        'log_hash',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Prevent updates or deletes on immutable audit logs.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Security Violation: Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Security Violation: Audit logs are append-only and cannot be deleted.');
        });
    }

    /**
     * User associated with the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an append-only audit log entry with cryptographic SHA-256 integrity chaining.
     */
    public static function record(
        string $eventType,
        string $description,
        ?int $userId = null,
        string $severity = 'info',
        ?array $payload = null
    ): self {
        $lastLog = self::query()->latest('id')->first();
        $prevHash = $lastLog ? $lastLog->log_hash : str_repeat('0', 64);

        $now = now();
        $ipAddress = Request::ip();
        $userAgent = Request::userAgent();
        $timestamp = $now->toIso8601String();

        // Cryptographic Hash Chaining: H_i = SHA256(H_{i-1} || event_type || severity || description || user_id || ip || timestamp || payload)
        $chainData = json_encode([
            'prev_hash' => $prevHash,
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'user_id' => $userId,
            'ip' => $ipAddress,
            'timestamp' => $timestamp,
            'payload' => $payload,
        ]);

        $logHash = hash('sha256', $chainData);

        return self::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'payload' => $payload,
            'prev_hash' => $prevHash,
            'log_hash' => $logHash,
            'created_at' => $now,
        ]);
    }
}
