<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define Granular Permissions across 6 security modules
        $permissions = [
            // User Management Module
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'description' => 'View list of user accounts and profile details'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'description' => 'Create new user profiles'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'users', 'description' => 'Modify existing user accounts'],
            ['name' => 'Lock / Unlock Users', 'slug' => 'users.status', 'module' => 'users', 'description' => 'Suspend or unlock locked user accounts'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users', 'description' => 'Permanently remove user accounts'],

            // Role & Permission Management Module
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

            // Customer Banking Module
            ['name' => 'View Own Account', 'slug' => 'account.view_own', 'module' => 'customer', 'description' => 'View personal banking dashboard'],
            ['name' => 'Manage Own 2FA', 'slug' => 'account.manage_2fa', 'module' => 'customer', 'description' => 'Enable, disable, or regenerate TOTP 2FA'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['slug']] = Permission::updateOrCreate(
                ['slug' => $p['slug']],
                $p
            );
        }

        // 2. Define Enterprise Tiered Roles
        $roles = [
            'SuperAdmin' => [
                'name' => 'Super Administrator',
                'slug' => 'SuperAdmin',
                'description' => 'Full unrestricted system access, role administration, and disaster recovery orchestration',
                'is_system' => true,
                'permissions' => array_keys($permissionModels), // All permissions
            ],
            'SecurityAuditor' => [
                'name' => 'Security Auditor',
                'slug' => 'SecurityAuditor',
                'description' => 'Read-only forensics officer with audit log inspection, cryptographic hash verification, and attack analysis',
                'is_system' => true,
                'permissions' => [
                    'users.view',
                    'roles.view',
                    'audit.view',
                    'audit.verify',
                    'security.view_attacks',
                    'account.view_own',
                    'account.manage_2fa',
                ],
            ],
            'BranchManager' => [
                'name' => 'Branch Manager',
                'slug' => 'BranchManager',
                'description' => 'Operational manager responsible for user onboarding, customer support, and account unlocking',
                'is_system' => true,
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.edit',
                    'users.status',
                    'account.view_own',
                    'account.manage_2fa',
                ],
            ],
            'Customer' => [
                'name' => 'Customer',
                'slug' => 'Customer',
                'description' => 'Standard banking customer with self-service dashboard and two-factor authentication controls',
                'is_system' => true,
                'permissions' => [
                    'account.view_own',
                    'account.manage_2fa',
                ],
            ],
        ];

        foreach ($roles as $r) {
            $permSlugs = $r['permissions'];
            unset($r['permissions']);

            $role = Role::updateOrCreate(['slug' => $r['slug']], $r);

            $permIds = collect($permSlugs)->map(fn($slug) => $permissionModels[$slug]->id ?? null)->filter();
            $role->permissions()->sync($permIds);
        }

        // Record Initial Genesis Audit Log
        if (AuditLog::count() === 0) {
            AuditLog::record(
                eventType: 'system.genesis',
                description: 'Database schema initialized and RBAC role-permission matrix seeded.',
                severity: 'info',
                payload: ['roles_count' => count($roles), 'permissions_count' => count($permissions)]
            );
        }
    }
}
