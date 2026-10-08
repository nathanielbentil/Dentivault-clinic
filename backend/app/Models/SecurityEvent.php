<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class SecurityEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'attack_type',
        'ip_address',
        'user_agent',
        'request_method',
        'url_path',
        'payload_snippet',
        'blocked',
        'created_at',
    ];

    protected $casts = [
        'payload_snippet' => 'array',
        'blocked' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an intercepted hostile security event.
     */
    public static function logAttack(
        string $attackType,
        ?array $payloadSnippet = null,
        ?int $userId = null,
        bool $blocked = true
    ): self {
        return self::create([
            'user_id' => $userId,
            'attack_type' => $attackType,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent(),
            'request_method' => Request::method() ?? 'GET',
            'url_path' => Request::path() ?? '/',
            'payload_snippet' => $payloadSnippet,
            'blocked' => $blocked,
            'created_at' => now(),
        ]);
    }
}
