<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Implicit SuperAdmin authorization: Grants all abilities
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('SuperAdmin')) {
                return true;
            }
        });

        // 2. Define Granular Security Gates
        Gate::define('manage-users', function (User $user) {
            return $user->hasPermission('users.manage') || $user->hasPermission('users.create') || $user->hasPermission('users.edit');
        });

        Gate::define('unlock-accounts', function (User $user) {
            return $user->hasPermission('users.status');
        });

        Gate::define('view-audit-logs', function (User $user) {
            return $user->hasPermission('audit.view');
        });

        Gate::define('verify-log-integrity', function (User $user) {
            return $user->hasPermission('audit.verify');
        });

        Gate::define('trigger-backup', function (User $user) {
            return $user->hasPermission('backup.create');
        });

        Gate::define('restore-drill', function (User $user) {
            return $user->hasPermission('backup.restore_drill');
        });

        Gate::define('view-attacks', function (User $user) {
            return $user->hasPermission('security.view_attacks');
        });
    }
}
