<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_name',
        'description',
        'session_lifetime_minutes',
        'max_login_attempts',
        'lockout_duration_minutes',
        'enforce_2fa_for_all_roles',
        'pepper_version',
        'ip_rate_limiting_enabled',
        'maintenance_mode',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'enforce_2fa_for_all_roles' => 'boolean',
        'ip_rate_limiting_enabled' => 'boolean',
        'maintenance_mode' => 'boolean',
        'is_active' => 'boolean',
    ];
}
