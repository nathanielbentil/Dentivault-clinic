<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds for MediVault Hospital Management System.
     */
    public function run(): void
    {
        // ------------------------------------------------------------------
        // 1. Define Granular Permissions across Hospital Modules
        // ------------------------------------------------------------------
        $permissions = [
            // Clinical Patient Management
            ['name' => 'View Patient Records', 'slug' => 'patients.view', 'module' => 'clinical', 'description' => 'View patient medical charts, admissions, and history'],
            ['name' => 'Admit New Patient', 'slug' => 'patients.create', 'module' => 'clinical', 'description' => 'Register and admit new patients to hospital wards'],
            ['name' => 'Update Medical Chart', 'slug' => 'patients.edit', 'module' => 'clinical', 'description' => 'Update diagnoses, vital signs, and treatment notes'],
            ['name' => 'Discharge Patient', 'slug' => 'patients.delete', 'module' => 'clinical', 'description' => 'Process clinical patient discharge'],

            // Medical Diagnostics & Prescriptions
            ['name' => 'Record Physician Diagnosis', 'slug' => 'medical.diagnose', 'module' => 'medical', 'description' => 'Write clinical assessments and order laboratory tests'],
            ['name' => 'Prescribe Medications', 'slug' => 'medical.prescribe', 'module' => 'medical', 'description' => 'Issue pharmaceutical prescriptions'],
            ['name' => 'Record Nurse Vitals', 'slug' => 'nursing.vitals', 'module' => 'nursing', 'description' => 'Log patient blood pressure, pulse, temperature, SpO2'],
            ['name' => 'Dispense Medications', 'slug' => 'pharmacy.dispense', 'module' => 'pharmacy', 'description' => 'Dispense prescription drugs from hospital pharmacy'],

            // Staff & Security Administration
            ['name' => 'View Staff Directory', 'slug' => 'users.view', 'module' => 'staff', 'description' => 'Inspect hospital personnel and assigned roles'],
            ['name' => 'Manage Staff & Roles', 'slug' => 'users.manage', 'module' => 'staff', 'description' => 'Create staff, assign RBAC roles, and manage access'],
            ['name' => 'Soft Delete Staff User', 'slug' => 'users.delete', 'module' => 'staff', 'description' => 'Archive staff member with audit retention'],
            ['name' => 'Restore Soft-Deleted Staff', 'slug' => 'users.restore', 'module' => 'staff', 'description' => 'Restore archived staff member from audit archive'],

            // HIPAA Audit Trail & Configuration Restore Points
            ['name' => 'View HIPAA Audit Trail', 'slug' => 'audit.view', 'module' => 'audit', 'description' => 'Read immutable cryptographic access logs and deleted archives'],
            ['name' => 'Verify Hash Chain Integrity', 'slug' => 'audit.verify', 'module' => 'audit', 'description' => 'Verify SHA-256 tamper-proof log chains'],
            ['name' => 'Manage System Config & Restore Points', 'slug' => 'config.manage', 'module' => 'system', 'description' => 'Create and rollback configuration restore points'],
        ];

        $permissionModels = [];
        foreach ($permissions as $p) {
            $permissionModels[$p['slug']] = Permission::updateOrCreate(
                ['slug' => $p['slug']],
                $p
            );
        }

        // ------------------------------------------------------------------
        // 2. Define the 6 Hospital Roles with Distinct Authorizations
        // ------------------------------------------------------------------
        // Role 1: Chief Medical Director (Super Admin)
        $adminRole = Role::updateOrCreate(
            ['slug' => 'Admin'],
            [
                'name' => 'Chief Medical Director',
                'description' => 'Full administrative access, staff role management, configuration restore points, and HIPAA audit trails',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync(array_values(array_map(fn($p) => $p->id, $permissionModels)));

        // Role 2: Hospital Operations Manager
        $managerRole = Role::updateOrCreate(
            ['slug' => 'Manager'],
            [
                'name' => 'Hospital Operations Manager',
                'description' => 'Patient admissions, ward beds, staff directory, and clinic flow oversight',
                'is_system' => true,
            ]
        );
        $managerRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['patients.create']->id,
            $permissionModels['patients.edit']->id,
            $permissionModels['users.view']->id,
        ]);

        // Role 3: Attending Doctor / Physician
        $doctorRole = Role::updateOrCreate(
            ['slug' => 'Doctor'],
            [
                'name' => 'Attending Physician / Doctor',
                'description' => 'Clinical diagnosis, patient examinations, treatment charting, and drug prescriptions',
                'is_system' => true,
            ]
        );
        $doctorRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['patients.create']->id,
            $permissionModels['patients.edit']->id,
            $permissionModels['medical.diagnose']->id,
            $permissionModels['medical.prescribe']->id,
        ]);

        // Role 4: Ward Nurse
        $nurseRole = Role::updateOrCreate(
            ['slug' => 'Nurse'],
            [
                'name' => 'Ward Nurse / Registered Nurse',
                'description' => 'Patient vital signs monitoring (BP, Temp, Pulse), medication administration, ward monitoring',
                'is_system' => true,
            ]
        );
        $nurseRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['patients.edit']->id,
            $permissionModels['nursing.vitals']->id,
        ]);

        // Role 5: Hospital Pharmacist
        $pharmacistRole = Role::updateOrCreate(
            ['slug' => 'Pharmacist'],
            [
                'name' => 'Hospital Pharmacist',
                'description' => 'Prescription review, drug dispensing, medication stock management',
                'is_system' => true,
            ]
        );
        $pharmacistRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['pharmacy.dispense']->id,
        ]);

        // Role 6: Compliance & HIPAA Auditor
        $auditorRole = Role::updateOrCreate(
            ['slug' => 'Auditor'],
            [
                'name' => 'Compliance & HIPAA Auditor',
                'description' => 'Read-only access to cryptographic audit logs, soft-deleted user archives, and forensic verification',
                'is_system' => true,
            ]
        );
        $auditorRole->permissions()->sync([
            $permissionModels['patients.view']->id,
            $permissionModels['audit.view']->id,
            $permissionModels['audit.verify']->id,
            $permissionModels['users.view']->id,
        ]);

        // ------------------------------------------------------------------
        // 3. Seed Hospital Staff Users (with Salt, Pepper, and Argon2id Hashes)
        // ------------------------------------------------------------------
        $staffMembers = [
            [
                'name' => 'Dr. Prof. Nathaniel Bentil',
                'email' => 'admin@medivault.cloud',
                'password' => 'Admin@MediVault2026!',
                'role' => 'Admin',
                'roleModel' => $adminRole,
            ],
            [
                'name' => 'Dr. Bright Ainoo',
                'email' => 'manager@medivault.cloud',
                'password' => 'Manager@MediVault2026!',
                'role' => 'Manager',
                'roleModel' => $managerRole,
            ],
            [
                'name' => 'Dr. Isaac Donkoh, MD',
                'email' => 'doctor@medivault.cloud',
                'password' => 'Doctor@MediVault2026!',
                'role' => 'Doctor',
                'roleModel' => $doctorRole,
            ],
            [
                'name' => 'Nurse Beatrice Mensah, RN',
                'email' => 'nurse@medivault.cloud',
                'password' => 'Nurse@MediVault2026!',
                'role' => 'Nurse',
                'roleModel' => $nurseRole,
            ],
            [
                'name' => 'Pharm. Kwabena Appiah',
                'email' => 'pharmacist@medivault.cloud',
                'password' => 'Pharm@MediVault2026!',
                'role' => 'Pharmacist',
                'roleModel' => $pharmacistRole,
            ],
            [
                'name' => 'Auditor Samuel Osei',
                'email' => 'auditor@medivault.cloud',
                'password' => 'Auditor@MediVault2026!',
                'role' => 'Auditor',
                'roleModel' => $auditorRole,
            ],
        ];

        foreach ($staffMembers as $s) {
            $hashResult = PasswordService::hash($s['password']);
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'role' => $s['role'],
                    'password' => $hashResult['hash'],
                    'salt' => $hashResult['salt'],
                    'pepper_version' => $hashResult['pepper_version'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'deleted_at' => null,
                ]
            );
            $user->roles()->sync([$s['roleModel']->id => ['created_at' => now()]]);
        }

        // Pre-seed an Archived / Soft-Deleted Staff Member to demonstrate Requirement #7!
        $archivedHash = PasswordService::hash('Archived@Pass2026!');
        $archivedUser = User::withTrashed()->updateOrCreate(
            ['email' => 'archived.staff@medivault.cloud'],
            [
                'name' => 'Dr. Francis Cudjoe (Former Physician)',
                'role' => 'Doctor',
                'password' => $archivedHash['hash'],
                'salt' => $archivedHash['salt'],
                'pepper_version' => $archivedHash['pepper_version'],
                'status' => 'suspended',
                'email_verified_at' => now(),
                'deleted_at' => now()->subDays(3), // Soft-deleted 3 days ago for audit inspection!
            ]
        );
        $archivedUser->roles()->sync([$doctorRole->id => ['created_at' => now()->subDays(10)]]);

        // ------------------------------------------------------------------
        // 4. Seed Configuration Restore Points (Requirement #9)
        // ------------------------------------------------------------------
        SystemConfiguration::updateOrCreate(
            ['snapshot_name' => 'Baseline Config v1.0 (Standard Clinical)'],
            [
                'description' => 'Hospital standard security profile with 30-min sessions and 5-attempt brute-force protection',
                'session_lifetime_minutes' => 30,
                'max_login_attempts' => 5,
                'lockout_duration_minutes' => 15,
                'enforce_2fa_for_all_roles' => true,
                'pepper_version' => 'v1',
                'ip_rate_limiting_enabled' => true,
                'maintenance_mode' => false,
                'created_by' => 'Dr. Prof. Nathaniel Bentil (Chief Medical Director)',
                'is_active' => true,
            ]
        );

        SystemConfiguration::updateOrCreate(
            ['snapshot_name' => 'High-Security Zero-Trust Lockdown'],
            [
                'description' => 'Hardened clinical profile with 15-min session timeout, 3-attempt lockout, and mandatory biometric auth',
                'session_lifetime_minutes' => 15,
                'max_login_attempts' => 3,
                'lockout_duration_minutes' => 30,
                'enforce_2fa_for_all_roles' => true,
                'pepper_version' => 'v1',
                'ip_rate_limiting_enabled' => true,
                'maintenance_mode' => false,
                'created_by' => 'Dr. Prof. Nathaniel Bentil (Chief Medical Director)',
                'is_active' => false,
            ]
        );

        // ------------------------------------------------------------------
        // 5. Seed 50+ Realistic Hospital Patients & Medical Records (Requirement #9)
        // ------------------------------------------------------------------
        $firstNames = ['Emily', 'Marcus', 'Sophia', 'David', 'Olivia', 'James', 'Grace', 'Daniel', 'Chloe', 'Alexander',
                       'Hannah', 'Michael', 'Ava', 'Benjamin', 'Mia', 'William', 'Charlotte', 'Lucas', 'Amelia', 'Ethan',
                       'Harper', 'Henry', 'Evelyn', 'Sebastian', 'Abigail', 'Jack', 'Ella', 'Matthew', 'Avery', 'Samuel',
                       'Scarlett', 'Joseph', 'Madison', 'Levi', 'Victoria', 'Mateo', 'Aria', 'David', 'Penelope', 'John',
                       'Riley', 'Wyatt', 'Layla', 'Carter', 'Lillian', 'Julian', 'Nora', 'Luke', 'Zoey', 'Grayson',
                       'Stella', 'Leo'];
        $lastNames  = ['Watson', 'Vance', 'Rodriguez', 'Chen', 'Taylor', 'Smith', 'Johnson', 'Brown', 'Davis', 'Miller',
                       'Wilson', 'Moore', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia',
                       'Martinez', 'Robinson', 'Clark', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen', 'Young', 'Hernandez',
                       'King', 'Wright', 'Lopez', 'Hill', 'Scott', 'Green', 'Adams', 'Baker', 'Gonzalez', 'Nelson',
                       'Carter', 'Mitchell', 'Perez', 'Roberts', 'Turner', 'Phillips', 'Campbell', 'Parker', 'Evans', 'Edwards',
                       'Collins', 'Stewart'];
        $bloodGroups = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
        $wards       = ['General Ward 1 (Bed 01)', 'General Ward 2 (Bed 04)', 'ICU - Trauma Unit (Bed 02)', 'Surgical Ward A (Bed 08)', 
                        'Pediatric Wing (Room 102)', 'Cardiology Care Unit (CCU 03)', 'Outpatient Consultation Suite 4', 'Emergency Triage Bed 06'];
        $statuses    = ['Admitted', 'Outpatient', 'ICU', 'Discharged'];
        $conditions  = [
            ['Acute Appendicitis', 'Laparoscopic appendectomy performed. Post-op recovery in progress.', 'IV Cefazolin 1g, Tramadol 50mg PRN', 'Penicillin Allergy', 'BP: 124/82 | HR: 74 | Temp: 37.1°C | SpO2: 98%'],
            ['Type 2 Diabetes with Neuropathy', 'Routine glycemic control check. HbA1c at 7.2%. Foot examination normal.', 'Metformin 850mg BID, Glimepiride 2mg', 'None Reported', 'BP: 130/84 | HR: 68 | Temp: 36.6°C | SpO2: 99%'],
            ['Hypertensive Urgency', 'Admitted for blood pressure stabilization. IV Labetalol administered.', 'Lisinopril 20mg, Amlodipine 5mg Daily', 'Sulfa Drugs', 'BP: 168/104 | HR: 88 | Temp: 36.8°C | SpO2: 97%'],
            ['Community-Acquired Pneumonia', 'Right lower lobe consolidation noted on chest X-ray. Sputum culture pending.', 'Azithromycin 500mg, Ceftriaxone 1g IV', 'None Reported', 'BP: 118/76 | HR: 84 | Temp: 38.4°C | SpO2: 94%'],
            ['Fracture of Left Tibia (Closed)', 'Closed reduction completed with fiberglass cast application. Crutch training given.', 'Ibuprofen 400mg TID, Paracetamol 1g QID', 'Latex Sensitivity', 'BP: 122/80 | HR: 76 | Temp: 36.7°C | SpO2: 99%'],
            ['Gastroesophageal Reflux Disease (GERD)', 'Endoscopy scheduled. Lifestyle dietary counseling provided.', 'Omeprazole 40mg Once Daily Before Breakfast', 'None Reported', 'BP: 120/78 | HR: 70 | Temp: 36.6°C | SpO2: 99%'],
            ['Severe Asthma Exacerbation', 'Nebulized Albuterol/Ipratropium given in emergency triage. Peak flow improved 40%.', 'Prednisone 40mg Oral, Salbutamol Inhaler PRN', 'Aspirin (AERD)', 'BP: 128/82 | HR: 92 | Temp: 37.0°C | SpO2: 95%'],
            ['Coronary Artery Disease (Post-Stent)', 'Routine cardiology follow-up. Echocardiogram shows EF 55%. Stable.', 'Aspirin 81mg, Clopidogrel 75mg, Atorvastatin 40mg', 'None Reported', 'BP: 116/74 | HR: 62 | Temp: 36.5°C | SpO2: 98%'],
        ];

        for ($i = 0; $i < 52; $i++) {
            $code = 'MED-' . (1001 + $i);
            $fullName = $firstNames[$i % count($firstNames)] . ' ' . $lastNames[$i % count($lastNames)];
            $cond = $conditions[$i % count($conditions)];
            $status = $statuses[$i % count($statuses)];
            $ward = $status === 'Outpatient' ? 'Outpatient Clinic Room ' . (($i % 6) + 1) : $wards[$i % count($wards)];

            Patient::updateOrCreate(
                ['patient_code' => $code],
                [
                    'name' => $fullName,
                    'age' => rand(18, 82),
                    'gender' => ($i % 2 === 0) ? 'Female' : 'Male',
                    'blood_group' => $bloodGroups[$i % count($bloodGroups)],
                    'phone' => '+1 (555) ' . rand(100, 999) . '-' . rand(1000, 9999),
                    'email' => strtolower(str_replace(' ', '.', $fullName)) . '@patient.medivault.cloud',
                    'ward_room' => $ward,
                    'admission_status' => $status,
                    'diagnosis' => $cond[0],
                    'treatment_plan' => $cond[1],
                    'prescription' => $cond[2],
                    'allergies' => $cond[3],
                    'medical_history' => 'Medical record verified under HIPAA privacy protocols.',
                    'vitals_summary' => $cond[4],
                    'attending_doctor' => ($i % 2 === 0) ? 'Dr. Isaac Donkoh, MD' : 'Dr. Prof. Nathaniel Bentil',
                    'assigned_nurse' => 'Nurse Beatrice Mensah, RN',
                ]
            );
        }

        // ------------------------------------------------------------------
        // 6. Record Genesis HIPAA Cryptographic Audit Log
        // ------------------------------------------------------------------
        AuditLog::record(
            eventType: 'system.hospital_seeded',
            description: 'MediVault initialized with 6 Enterprise RBAC roles, 52 admitted hospital patients, and configuration snapshot.',
            userId: 1,
            severity: 'notice'
        );

        AuditLog::record(
            eventType: 'user.soft_deleted',
            description: 'Staff member Dr. Francis Cudjoe archived with Soft Delete. Record preserved in audit vault.',
            userId: 1,
            severity: 'warning'
        );
    }
}
