<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Granular Permissions across core modules
        $permissions = [
            // User Management Module
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'description' => 'View list of user accounts and profile details'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'description' => 'Create new user profiles'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'users', 'description' => 'Modify existing user accounts'],
            ['name' => 'Lock / Unlock Users', 'slug' => 'users.status', 'module' => 'users', 'description' => 'Suspend or unlock locked user accounts'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users', 'description' => 'Permanently remove user accounts'],

            // Role Management Module
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'roles', 'description' => 'View roles and their permission matrix'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'roles', 'description' => 'Assign and revoke user roles'],

            // Audit Trail & Telemetry Module
            ['name' => 'View Audit Logs', 'slug' => 'audit.view', 'module' => 'audit', 'description' => 'Read immutable system and security audit logs'],
            ['name' => 'Verify Log Integrity', 'slug' => 'audit.verify', 'module' => 'audit', 'description' => 'Run cryptographic SHA-256 chain verification on logs'],

            // Backup & Disaster Recovery Module
            ['name' => 'Trigger Backup', 'slug' => 'backup.create', 'module' => 'backup', 'description' => 'Initiate on-demand age-encrypted database backups'],
            ['name' => 'Download Backup', 'slug' => 'backup.download', 'module' => 'backup', 'description' => 'Download encrypted backup packages'],
            ['name' => 'Run Disaster Recovery Drill', 'slug' => 'backup.restore_drill', 'module' => 'backup', 'description' => 'Execute automated restoration drills'],

            // Security Forensics Module
            ['name' => 'View Security Events', 'slug' => 'security.view_attacks', 'module' => 'security', 'description' => 'Inspect blocked SQLi, XSS, and Brute Force incidents'],

            // User Self-Service Module
            ['name' => 'View Own Profile', 'slug' => 'account.view_own', 'module' => 'user', 'description' => 'View personal user dashboard'],
            ['name' => 'Manage Own 2FA', 'slug' => 'account.manage_2fa', 'module' => 'user', 'description' => 'Enable, disable, or regenerate TOTP 2FA'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['slug']] = Permission::updateOrCreate(
                ['slug' => $p['slug']],
                $p
            );
        }

        // 2. Define the 2 Core Enterprise Roles: Admin and User
        $adminRole = Role::updateOrCreate(
            ['slug' => 'Admin'],
            [
                'name' => 'Administrator',
                'description' => 'Full system access, user management, audit verification, and 3-2-1 backup/DR management',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync(array_values(array_map(fn($p) => $p->id, $permissionModels)));

        $userRole = Role::updateOrCreate(
            ['slug' => 'User'],
            [
                'name' => 'Standard User',
                'description' => 'Standard user account with self-service profile and two-factor authentication controls',
                'is_system' => true,
            ]
        );
        $userRole->permissions()->sync([
            $permissionModels['account.view_own']->id,
            $permissionModels['account.manage_2fa']->id,
        ]);

        // Clean up any legacy roles if present
        Role::whereNotIn('slug', ['Admin', 'User'])->delete();

        // 3. Seed Exactly 2 Users: 1 Admin and 1 Standard User
        // User 1: Administrator
        $adminPass = PasswordService::hash('Admin@SecureAuth2026!');
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@secureauth.com'],
            [
                'name' => 'System Administrator',
                'password' => $adminPass['hash'],
                'pepper_version' => $adminPass['pepper_version'],
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $adminUser->roles()->sync([$adminRole->id => ['assigned_by' => null, 'created_at' => now()]]);

        // User 2: Standard User
        $userPass = PasswordService::hash('User@SecureAuth2026!');
        $standardUser = User::updateOrCreate(
            ['email' => 'user@secureauth.com'],
            [
                'name' => 'Standard User',
                'password' => $userPass['hash'],
                'pepper_version' => $userPass['pepper_version'],
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $standardUser->roles()->sync([$userRole->id => ['assigned_by' => $adminUser->id, 'created_at' => now()]]);

        // Clean up any other test users so database has exactly the 2 defined users
        User::whereNotIn('email', ['admin@secureauth.com', 'user@secureauth.com'])->delete();

        // 4. Record Genesis/Seed Audit Log
        AuditLog::record(
            eventType: 'system.roles_seeded',
            description: 'Seeded 2 core roles (Admin, User) and 2 system user accounts.',
            severity: 'notice',
            payload: [
                'roles' => ['Admin', 'User'],
                'users' => ['admin@secureauth.com', 'user@secureauth.com'],
            ]
        );
    }
}
