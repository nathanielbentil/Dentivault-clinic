<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Patient;
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
        // 1. Define Granular Permissions across Dental & Security Modules
        $permissions = [
            // Dental Clinical Module
            ['name' => 'View Dental Patients', 'slug' => 'patients.view', 'module' => 'clinical', 'description' => 'View patient dental charts and medical histories'],
            ['name' => 'Create Dental Chart', 'slug' => 'patients.create', 'module' => 'clinical', 'description' => 'Register new patients and record treatment procedures'],
            ['name' => 'Edit Dental Chart', 'slug' => 'patients.edit', 'module' => 'clinical', 'description' => 'Update clinical tooth notes and treatment status'],
            ['name' => 'Delete Dental Patient', 'slug' => 'patients.delete', 'module' => 'clinical', 'description' => 'Remove patient records from the clinic directory'],

            // Staff & User Management Module
            ['name' => 'View Staff Directory', 'slug' => 'users.view', 'module' => 'staff', 'description' => 'View list of clinic dentists, hygienists, and managers'],
            ['name' => 'Manage Staff Roles', 'slug' => 'users.manage_roles', 'module' => 'staff', 'description' => 'Assign and change user permissions and roles'],

            // HIPAA Audit Trail & Security Telemetry
            ['name' => 'View HIPAA Audit Trail', 'slug' => 'audit.view', 'module' => 'audit', 'description' => 'Read immutable cryptographic access and clinical logs'],
            ['name' => 'Verify Log Integrity', 'slug' => 'audit.verify', 'module' => 'audit', 'description' => 'Run SHA-256 hash-chain verification engine'],

            // 3-2-1 Asymmetric Vault & Disaster Recovery
            ['name' => 'Trigger Age Backup', 'slug' => 'backup.create', 'module' => 'backup', 'description' => 'Create Age X25519 encrypted patient database archive'],
            ['name' => 'Sync Cloud Backup', 'slug' => 'backup.cloud_sync', 'module' => 'backup', 'description' => 'Replicate encrypted archive to offsite cloud storage'],

            // Self-Service Controls
            ['name' => 'Manage Own Profile & 2FA', 'slug' => 'account.manage', 'module' => 'account', 'description' => 'Update display name, password, and Google Authenticator 2FA'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['slug']] = Permission::updateOrCreate(
                ['slug' => $p['slug']],
                $p
            );
        }

        // 2. Define the 3 Core Roles: Admin, Manager, Dentist (and User alias)
        // Role 1: Admin (Chief Medical Officer & Compliance Director)
        $adminRole = Role::updateOrCreate(
            ['slug' => 'Admin'],
            [
                'name' => 'Chief Administrator',
                'description' => 'Full administrative access, staff role management, HIPAA audit chain verification, and 3-2-1 backup vault',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync(array_values(array_map(fn($p) => $p->id, $permissionModels)));

        // Role 2: Manager (Clinic Practice Manager)
        $managerRole = Role::updateOrCreate(
            ['slug' => 'Manager'],
            [
                'name' => 'Clinic Practice Manager',
                'description' => 'Clinic operations, patient registration, staff directory, and treatment plan oversight',
                'is_system' => true,
            ]
        );
        $managerRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['patients.create']->id,
            $permissionModels['patients.edit']->id,
            $permissionModels['patients.delete']->id,
            $permissionModels['users.view']->id,
            $permissionModels['account.manage']->id,
        ]);

        // Role 3: Dentist / Clinical Practitioner
        $dentistRole = Role::updateOrCreate(
            ['slug' => 'Dentist'],
            [
                'name' => 'Attending Dentist',
                'description' => 'Clinical dental practitioner with patient chart viewing, tooth diagnostics, and treatment charting',
                'is_system' => true,
            ]
        );
        $dentistRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['patients.create']->id,
            $permissionModels['patients.edit']->id,
            $permissionModels['account.manage']->id,
        ]);

        // Role 4: User (Standard User alias mapped to Dentist permissions for 100% test compatibility)
        $userRole = Role::updateOrCreate(
            ['slug' => 'User'],
            [
                'name' => 'Dentist / Clinical Staff',
                'description' => 'Clinical staff member with patient chart access and self-service 2FA controls',
                'is_system' => true,
            ]
        );
        $userRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['patients.create']->id,
            $permissionModels['patients.edit']->id,
            $permissionModels['account.manage']->id,
        ]);

        // 3. Seed Users across the 3 roles
        // Admin: Dr. Prof. Nathaniel Bentil (Chief Dental Director & Administrator)
        $adminPass = PasswordService::hash('Admin@Dental2026!');
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@dentivault.com'],
            [
                'name' => 'Dr. Prof. Nathaniel Bentil',
                'password' => $adminPass['hash'],
                'pepper_version' => $adminPass['pepper_version'],
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ]
        );
        $adminUser->roles()->sync([$adminRole->id => ['assigned_by' => null, 'created_at' => now()]]);

        // Secondary Admin Alias for Dr. Prof. Nathaniel Bentil
        $natinoPass = PasswordService::hash('Admin@SecureAuth2026!');
        $natinoUser = User::updateOrCreate(
            ['email' => 'natinobentil22@gmail.com'],
            [
                'name' => 'Dr. Prof. Nathaniel Bentil',
                'password' => $natinoPass['hash'],
                'pepper_version' => $natinoPass['pepper_version'],
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ]
        );
        $natinoUser->roles()->sync([$adminRole->id => ['assigned_by' => null, 'created_at' => now()]]);

        // Role 2: Clinic Practice Manager (Dr. Bright Ainoo)
        $mgrPass = PasswordService::hash('Manager@Dental2026!');
        $mgrUser = User::updateOrCreate(
            ['email' => 'manager@dentivault.com'],
            [
                'name' => 'Dr. Bright Ainoo',
                'password' => $mgrPass['hash'],
                'pepper_version' => $mgrPass['pepper_version'],
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ]
        );
        $mgrUser->roles()->sync([$managerRole->id => ['assigned_by' => $adminUser->id, 'created_at' => now()]]);

        // Role 3: Attending Dentist (Dr. Isaac Donkoh)
        $dentistPass = PasswordService::hash('Dentist@Dental2026!');
        $dentistUser = User::updateOrCreate(
            ['email' => 'dentist@dentivault.com'],
            [
                'name' => 'Dr. Isaac Donkoh',
                'password' => $dentistPass['hash'],
                'pepper_version' => $dentistPass['pepper_version'],
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ]
        );
        $dentistUser->roles()->sync([$dentistRole->id => ['assigned_by' => $adminUser->id, 'created_at' => now()]]);

        // 4. Seed 5 Realistic Dental Patients
        $demoPatients = [
            [
                'patient_code' => 'DENT-1001',
                'name' => 'Emily Watson',
                'age' => 28,
                'gender' => 'Female',
                'phone' => '+1 (555) 234-5678',
                'medical_history' => 'Penicillin allergy; No known bleeding disorders',
                'tooth_number' => '#14 (Upper Left 1st Molar)',
                'procedure' => 'Composite Resin Restoration (Class II)',
                'clinical_notes' => 'Deep occlusal-distal caries removed. 2% Lidocaine with 1:100,000 Epinephrine administered. Etched, bonded, and incremental composite placed with cured polish. Occlusion checked and verified.',
                'attending_dentist' => 'Dr. Isaac Donkoh',
                'status' => 'Completed',
            ],
            [
                'patient_code' => 'DENT-1002',
                'name' => 'Marcus Vance',
                'age' => 45,
                'gender' => 'Male',
                'phone' => '+1 (555) 876-5432',
                'medical_history' => 'Hypertension (Controlled on Lisinopril)',
                'tooth_number' => '#19 (Lower Left 1st Molar)',
                'procedure' => 'Endodontic Root Canal Therapy (Phase 1)',
                'clinical_notes' => 'Irreversible pulpitis diagnosed. Access cavity prepared. Working lengths established via apex locator: MB 21mm, ML 21.5mm, D 22mm. Biomechanical debridement with NaOCl irrigation. Calcium hydroxide paste placed with Cavit temporary seal.',
                'attending_dentist' => 'Dr. Prof. Nathaniel Bentil',
                'status' => 'In Treatment',
            ],
            [
                'patient_code' => 'DENT-1003',
                'name' => 'Sophia Rodriguez',
                'age' => 34,
                'gender' => 'Female',
                'phone' => '+1 (555) 345-6789',
                'medical_history' => 'Latex sensitivity; Asthmatic (inhaler on-hand)',
                'tooth_number' => 'Full Arch (Maxilla & Mandible)',
                'procedure' => 'Comprehensive Ultrasonic Periodontal Scaling',
                'clinical_notes' => 'Full periodontal probing depths recorded (2-3mm generalized). Moderate subgingival calculus removed with ultrasonic scaler and Gracey curettes. Prophy paste polishing and 5% sodium fluoride varnish applied.',
                'attending_dentist' => 'Dr. Bright Ainoo',
                'status' => 'Completed',
            ],
            [
                'patient_code' => 'DENT-1004',
                'name' => 'David Chen',
                'age' => 52,
                'gender' => 'Male',
                'phone' => '+1 (555) 987-6543',
                'medical_history' => 'Type 2 Diabetes (HbA1c 6.4%)',
                'tooth_number' => '#8 (Upper Right Central Incisor)',
                'procedure' => 'Porcelain-Fused-to-Zirconia Crown Prep',
                'clinical_notes' => 'Old chipped veneer removed. Tooth prepared with 1.0mm circumferential chamfer margin. Retraction cord placed. Digital intraoral scan sent to dental lab (Shade Vita A2). Acrylic provisional crown seated with TempBond.',
                'attending_dentist' => 'Dr. Prof. Nathaniel Bentil',
                'status' => 'In Treatment',
            ],
            [
                'patient_code' => 'DENT-1005',
                'name' => 'Olivia Taylor',
                'age' => 22,
                'gender' => 'Female',
                'phone' => '+1 (555) 456-7890',
                'medical_history' => 'No medical contraindications',
                'tooth_number' => '#1, #16, #17, #32 (Third Molars)',
                'procedure' => 'Wisdom Teeth Extraction Assessment & OPG',
                'clinical_notes' => 'Panoramic radiograph (OPG) taken. Bilateral lower wisdom teeth (#17, #32) mesioangularly impacted with recurrent pericoronitis. Treatment plan options discussed. Scheduled for oral surgery under IV sedation.',
                'attending_dentist' => 'Dr. Isaac Donkoh',
                'status' => 'Scheduled',
            ],
        ];

        foreach ($demoPatients as $p) {
            Patient::updateOrCreate(['patient_code' => $p['patient_code']], $p);
        }

        // 5. Record Genesis/Seed Audit Log
        AuditLog::record(
            eventType: 'system.clinic_seeded',
            description: 'Seeded DentiVault 3-Tier RBAC roles (Admin, Manager, Dentist) and 5 patient clinical records.',
            severity: 'notice',
            payload: [
                'roles' => ['Admin', 'Manager', 'Dentist'],
                'patients_seeded' => count($demoPatients),
            ]
        );
    }
}
