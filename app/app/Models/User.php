<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pepper_version',
        'status',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'failed_login_attempts',
        'lockout_until',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'require_password_change',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'lockout_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'require_password_change' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RBAC Relationships & Helpers
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('assigned_by', 'created_at');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roleList = is_array($roles) ? $roles : [$roles];
        return $this->roles->contains(function ($role) use ($roleList) {
            return in_array($role->slug, $roleList) || in_array($role->name, $roleList);
        });
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->hasRole('SuperAdmin')) {
            return true; // SuperAdmin automatically inherits all capabilities
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Account Lockout & Security Telemetry
    |--------------------------------------------------------------------------
    */

    public function isLockedOut(): bool
    {
        if ($this->status === 'locked' || ($this->lockout_until && $this->lockout_until->isFuture())) {
            return true;
        }

        // If lockout time expired, auto-unlock
        if ($this->lockout_until && $this->lockout_until->isPast() && $this->status === 'locked') {
            $this->update([
                'status' => 'active',
                'failed_login_attempts' => 0,
                'lockout_until' => null,
            ]);
            return false;
        }

        return false;
    }

    public function recordFailedLogin(int $maxAttempts = 5, int $lockoutMinutes = 15): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= $maxAttempts) {
            $this->update([
                'status' => 'locked',
                'lockout_until' => now()->addMinutes($lockoutMinutes),
            ]);

            AuditLog::record(
                eventType: 'auth.lockout',
                description: "Account locked out due to {$this->failed_login_attempts} consecutive failed login attempts.",
                userId: $this->id,
                severity: 'warning'
            );
        }
    }

    public function resetFailedLogins(string $ip): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'lockout_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
