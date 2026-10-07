<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'email_or_username',
        'ip_address',
        'user_agent',
        'is_successful',
        'failure_reason',
        'created_at',
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get recent failure count for sliding window rate limiting.
     */
    public static function recentFailuresCount(string $emailOrIp, int $windowMinutes = 15): int
    {
        return self::query()
            ->where(function ($q) use ($emailOrIp) {
                $q->where('email_or_username', $emailOrIp)
                  ->orWhere('ip_address', $emailOrIp);
            })
            ->where('is_successful', false)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();
    }
}
