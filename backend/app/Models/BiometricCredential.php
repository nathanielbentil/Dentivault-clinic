<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'public_key',
        'sign_count',
        'transports',
        'aaguid',
        'last_used_at',
    ];

    protected $casts = [
        'sign_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * User who owns this biometric passkey credential.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
