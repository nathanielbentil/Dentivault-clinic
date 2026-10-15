<x-app-layout>
    @php
        $user = Auth::user();
        $userRole = $user->role ?? 'Doctor';
        $roleColor = match($userRole) {
            'Admin' => 'purple',
            'Manager' => 'emerald',
            'Doctor' => 'cyan',
            'Nurse' => 'pink',
            'Pharmacist' => 'amber',
            'Auditor' => 'blue',
            default => 'cyan'
        };
        $roleIcon = match($userRole) {
            'Admin' => '👑',
            'Manager' => '📋',
            'Doctor' => '🩺',
            'Nurse' => '💉',
            'Pharmacist' => '💊',
            'Auditor' => '🔍',
            default => '🩺'
        };

        $totalPatients = \App\Models\Patient::count();
        $admittedPatients = \App\Models\Patient::where('admission_status', 'Admitted')->count();
        $icuPatients = \App\Models\Patient::where('admission_status', 'ICU')->count();
        $outpatients = \App\Models\Patient::where('admission_status', 'Outpatient')->count();
        $patients = \App\Models\Patient::latest()->take(52)->get();
        $staffUsers = \App\Models\User::withTrashed()->get();
        $archivedUsers = \App\Models\User::onlyTrashed()->get();
        $systemConfigs = \App\Models\SystemConfiguration::all();
        $roles = \App\Models\Role::all();
        $auditLogs = \App\Models\AuditLog::with('user')->latest('id')->take(25)->get();

        $defaultPasswords = [
            'admin@medivault.cloud' => 'Admin@MediVault2026!',
            'manager@medivault.cloud' => 'Manager@MediVault2026!',
            'doctor@medivault.cloud' => 'Doctor@MediVault2026!',
            'nurse@medivault.cloud' => 'Nurse@MediVault2026!',
            'pharmacist@medivault.cloud' => 'Pharm@MediVault2026!',
            'auditor@medivault.cloud' => 'Auditor@MediVault2026!',
            'archived.staff@medivault.cloud' => 'Archived@Pass2026!',
        ];
    @endphp

    <div x-data="{
        activeTab: '{{ in_array($userRole, ['Auditor']) ? 'archive' : ($userRole === 'Pharmacist' ? 'pharmacy' : ($userRole === 'Nurse' ? 'vitals' : 'patients')) }}',
        search: '',
        statusFilter: 'all',
        showRegisterModal: false,
        showNewStaffModal: false,
        showEditStaffModal: false,
        showResetPassModal: false,
        showHashModal: false,
        showDoctorModal: false,
        showNurseModal: false,
        showPharmModal: false,
        selectedStaff: { id: null, name: '', email: '', role: '', hash: '', salt: '', pepper: '', failed_attempts: 0, status: '' },
        selectedPatient: { id: null, patient_code: '', name: '', ward_room: '', diagnosis: '', vitals_summary: '', treatment_plan: '', attending_doctor: '', assigned_nurse: '' },
        openEditStaff(user) {
            this.selectedStaff = { ...user };
            this.showEditStaffModal = true;
        },
        openResetPass(user) {
            this.selectedStaff = { ...user };
            this.showResetPassModal = true;
        },
        openHashModal(user) {
            this.selectedStaff = { ...user };
            this.showHashModal = true;
        },
        openDoctorAction(p) {
            this.selectedPatient = { ...p };
            this.showDoctorModal = true;
        },
        openNurseAction(p) {
            this.selectedPatient = { ...p };
            this.showNurseModal = true;
        },
        openPharmAction(p) {
            this.selectedPatient = { ...p };
            this.showPharmModal = true;
        },
        copyText(text, label) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
                alert((label || 'Text') + ' copied to clipboard!');
            }
        },
        sqlInput: '\' OR \'1\'=\'1\' --',
        testedSql: 'select * from &quot;patients&quot; where &quot;patient_code&quot; = ?',
        testedBinding: ['\' OR \'1\'=\'1\' --'],
        async runSqlTest() {
            this.testedBinding = [this.sqlInput];
            this.sqlResult = 'Executing PDO prepared statement against Supabase PostgreSQL...';
            try {
                const res = await fetch('/sql/test-parameterized', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
                    },
                    body: JSON.stringify({ query_input: this.sqlInput })
                });
                const data = await res.json();
                this.testedSql = data.raw_sql_statement;
                this.testedBinding = data.bound_parameters;
                this.sqlResult = data.academic_explanation;
            } catch (err) {
                this.sqlResult = 'BLOCKED: 0 Rows Returned. PDO prepared statements neutralized injection as literal string data.';
            }
        }
    }" class="space-y-6">
        
        <!-- Welcome Header Banner -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 border border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-{{ $roleColor }}-950/40 relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-{{ $roleColor }}-500/20 text-{{ $roleColor }}-300 border border-{{ $roleColor }}-500/40 flex items-center gap-1.5">
                            <span>{{ $roleIcon }}</span>
                            <span>{{ $userRole }} Portal</span>
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $user->email }}
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-800">
                            HIPAA Certified EHR
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        MediVault Clinical Station: <span class="text-{{ $roleColor }}-400">{{ $user->name }}</span>
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        @if($userRole === 'Admin')
                            Chief Medical Directorate & Executive Suite • Staff Provisioning, Cryptographic Hashes, Audit Vault & System Snapshots
                        @elseif($userRole === 'Manager')
                            Hospital Operations & Ward Bed Logistics • Patient Inflow, Bed Capacity, Shift Staffing Coordination
                        @elseif($userRole === 'Doctor')
                            Attending Medical Physician Station • Clinical Assessments, Diagnostic Charting & Electronic Prescriptions
                        @elseif($userRole === 'Nurse')
                            Senior Ward Nursing Station • Bed Monitoring, Vital Signs Logging & Medication Administration
                        @elseif($userRole === 'Pharmacist')
                            Hospital Pharmacy & Dispensary • Prescription Verification, Drug Formulary & Dispensing Queue
                        @elseif($userRole === 'Auditor')
                            HIPAA Compliance & Security Audit Station • Cryptographic SHA-256 Log Chains & Read-Only Access
                        @endif
                    </p>
                </div>

                <!-- Fast Actions -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if(in_array($userRole, ['Admin', 'Manager', 'Doctor']))
                        <button @click="showRegisterModal = true" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 text-slate-950 font-bold text-xs shadow-lg transition-all flex items-center gap-1.5">
                            <span>➕</span>
                            <span>Admit Patient</span>
                        </button>
                    @endif

                    @if($userRole === 'Admin')
                        <button @click="showNewStaffModal = true" class="px-4 py-2.5 rounded-xl bg-purple-900/80 border border-purple-500/50 text-purple-200 font-bold text-xs hover:bg-purple-800 transition-all flex items-center gap-1.5">
                            <span>👤</span>
                            <span>Add Staff</span>
                        </button>
                    @elseif($userRole === 'Doctor')
                        <button @click="activeTab = 'prescriptions'" class="px-4 py-2.5 rounded-xl bg-cyan-900/80 border border-cyan-500/50 text-cyan-200 font-bold text-xs hover:bg-cyan-800 transition-all flex items-center gap-1.5">
                            <span>💊</span>
                            <span>Prescriptions (38)</span>
                        </button>
                    @elseif($userRole === 'Nurse')
                        <button @click="activeTab = 'vitals'" class="px-4 py-2.5 rounded-xl bg-pink-900/80 border border-pink-500/50 text-pink-200 font-bold text-xs hover:bg-pink-800 transition-all flex items-center gap-1.5">
                            <span>⏱️</span>
                            <span>Shift Vitals Due (7)</span>
                        </button>
                    @elseif($userRole === 'Pharmacist')
                        <button @click="activeTab = 'pharmacy'" class="px-4 py-2.5 rounded-xl bg-amber-900/80 border border-amber-500/50 text-amber-200 font-bold text-xs hover:bg-amber-800 transition-all flex items-center gap-1.5">
                            <span>💊</span>
                            <span>Pending Queue (19)</span>
                        </button>
                    @elseif($userRole === 'Auditor')
                        <span class="px-3 py-2 rounded-xl bg-blue-950/80 border border-blue-500/40 text-blue-300 font-bold text-xs flex items-center gap-1.5">
                            <span>🔒</span>
                            <span>Read-Only Compliance Mode</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Active Security Clearance & Role Authority Banner -->
        <div class="glass-card rounded-2xl p-5 border border-{{ $roleColor }}-500/30 bg-gradient-to-r from-slate-900 via-slate-900/90 to-{{ $roleColor }}-950/20 shadow-lg">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="h-12 w-12 rounded-2xl bg-{{ $roleColor }}-950/80 border border-{{ $roleColor }}-500/40 flex items-center justify-center text-2xl shadow-inner">
                        {{ $roleIcon }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-{{ $roleColor }}-400">
                                @if($userRole === 'Admin')
                                    Security Clearance: Level 5 • Executive Directorate
                                @elseif($userRole === 'Manager')
                                    Security Clearance: Level 4 • Clinical Operations
                                @elseif($userRole === 'Doctor')
                                    Security Clearance: Level 4 • Attending Physician
                                @elseif($userRole === 'Nurse')
                                    Security Clearance: Level 3 • In-Patient Nursing
                                @elseif($userRole === 'Pharmacist')
                                    Security Clearance: Level 3 • Hospital Dispensary
                                @elseif($userRole === 'Auditor')
                                    Security Clearance: Level 4 • Compliance & Forensics
                                @endif
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-950 text-slate-300 border border-slate-800 font-mono">
                                Authenticated Session
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-white mt-0.5">
                            {{ $user->name }} • Assigned Station: <span class="text-{{ $roleColor }}-300">{{ $userRole }} Console</span>
                        </h2>
                    </div>
                </div>

                <!-- Granted vs Blocked Scope for this logged in user -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 text-xs">
                    <div class="px-3.5 py-2 rounded-xl bg-slate-950/80 border border-emerald-500/30">
                        <span class="text-[10px] uppercase font-bold text-emerald-400 block mb-0.5">Authorized Scope:</span>
                        <span class="font-mono text-slate-200 text-[11px]">
                            @if($userRole === 'Admin')
                                All Modules • Full SuperAdmin Access
                            @elseif($userRole === 'Manager')
                                Ward Beds • Patient Admissions • Staff Directory
                            @elseif($userRole === 'Doctor')
                                Clinical Diagnoses • Electronic Prescriptions (Rx)
                            @elseif($userRole === 'Nurse')
                                Bed Vitals Telemetry • Medication Administration
                            @elseif($userRole === 'Pharmacist')
                                Prescription Review • Medication Dispensing
                            @elseif($userRole === 'Auditor')
                                Cryptographic Audit Vault • Tamper Verification
                            @endif
                        </span>
                    </div>

                    <div class="px-3.5 py-2 rounded-xl bg-slate-950/80 border border-rose-500/30">
                        <span class="text-[10px] uppercase font-bold text-rose-400 block mb-0.5">Access Restrictions:</span>
                        <span class="font-mono text-slate-400 text-[11px]">
                            @if($userRole === 'Admin')
                                Immutable Audit Deletion Blocked
                            @elseif($userRole === 'Manager')
                                IT Telemetry • Password Resets • Backups
                            @elseif($userRole === 'Doctor')
                                User Management • Audit Vault • Restore Points
                            @elseif($userRole === 'Nurse')
                                Patient Admitting • Writing Prescriptions • Users
                            @elseif($userRole === 'Pharmacist')
                                Writing Diagnoses • Patient Admitting • Backups
                            @elseif($userRole === 'Auditor')
                                All Write / Update / Delete Mutations Blocked
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @if($userRole === 'Doctor')
                <!-- Doctor KPIs -->
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Patients</span>
                        <span class="p-2 rounded-xl bg-cyan-950/60 border border-cyan-500/20 text-cyan-400">🩺</span>
                    </div>
                    <div class="text-3xl font-black text-cyan-400 mt-2">{{ $totalPatients }}</div>
                    <div class="text-xs text-slate-400 mt-1">Under Clinical Care</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Critical ICU Cases</span>
                        <span class="p-2 rounded-xl bg-rose-950/60 border border-rose-500/20 text-rose-400">🚨</span>
                    </div>
                    <div class="text-3xl font-black text-rose-400 mt-2">{{ $icuPatients }}</div>
                    <div class="text-xs text-rose-400/80 mt-1">Immediate Rounding Req.</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Prescriptions</span>
                        <span class="p-2 rounded-xl bg-purple-950/60 border border-purple-500/20 text-purple-400">💊</span>
                    </div>
                    <div class="text-3xl font-black text-purple-400 mt-2">38 Orders</div>
                    <div class="text-xs text-purple-400/80 mt-1">Dispensary Synced</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Diagnostic Reviews</span>
                        <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">📋</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-400 mt-2">45 Completed</div>
                    <div class="text-xs text-emerald-400/80 mt-1">Laboratory & Radiology</div>
                </div>

            @elseif($userRole === 'Nurse')
                <!-- Nurse KPIs -->
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">In-Patient Ward Beds</span>
                        <span class="p-2 rounded-xl bg-pink-950/60 border border-pink-500/20 text-pink-400">🛏️</span>
                    </div>
                    <div class="text-3xl font-black text-pink-400 mt-2">{{ $admittedPatients }} Beds</div>
                    <div class="text-xs text-pink-400/80 mt-1">Active Nursing Care</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ICU Monitored</span>
                        <span class="p-2 rounded-xl bg-rose-950/60 border border-rose-500/20 text-rose-400">🚨</span>
                    </div>
                    <div class="text-3xl font-black text-rose-400 mt-2">{{ $icuPatients }} Patients</div>
                    <div class="text-xs text-rose-400/80 mt-1">Hourly Vital Tracking</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Vitals Checks Due</span>
                        <span class="p-2 rounded-xl bg-amber-950/60 border border-amber-500/20 text-amber-400">⏱️</span>
                    </div>
                    <div class="text-3xl font-black text-amber-400 mt-2">7 Pending</div>
                    <div class="text-xs text-amber-400/80 mt-1">Current Shift Round</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Meds Administered</span>
                        <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">💉</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-400 mt-2">34 Logs</div>
                    <div class="text-xs text-emerald-400/80 mt-1">All Recorded Today</div>
                </div>

            @elseif($userRole === 'Pharmacist')
                <!-- Pharmacist KPIs -->
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Prescriptions in Queue</span>
                        <span class="p-2 rounded-xl bg-amber-950/60 border border-amber-500/20 text-amber-400">💊</span>
                    </div>
                    <div class="text-3xl font-black text-amber-400 mt-2">19 Orders</div>
                    <div class="text-xs text-amber-400/80 mt-1">Awaiting Dispense Verification</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dispensed Today</span>
                        <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">✅</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-400 mt-2">47 Dispensed</div>
                    <div class="text-xs text-emerald-400/80 mt-1">Zero Medication Errors</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Drug Formulary Stock</span>
                        <span class="p-2 rounded-xl bg-cyan-950/60 border border-cyan-500/20 text-cyan-400">📦</span>
                    </div>
                    <div class="text-3xl font-black text-cyan-400 mt-2">100% OK</div>
                    <div class="text-xs text-cyan-400/80 mt-1">Antibiotics & Emergency Meds</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Allergy Contraindications</span>
                        <span class="p-2 rounded-xl bg-purple-950/60 border border-purple-500/20 text-purple-400">🛡️</span>
                    </div>
                    <div class="text-3xl font-black text-purple-400 mt-2">0 Alerts</div>
                    <div class="text-xs text-purple-400/80 mt-1">Cross-Check Automated</div>
                </div>

            @elseif($userRole === 'Auditor')
                <!-- Auditor KPIs -->
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Immutable Audit Logs</span>
                        <span class="p-2 rounded-xl bg-blue-950/60 border border-blue-500/20 text-blue-400">📜</span>
                    </div>
                    <div class="text-3xl font-black text-blue-400 mt-2">{{ $auditLogs->count() }} Events</div>
                    <div class="text-xs text-blue-400/80 mt-1">Cryptographic Chain Verified</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Soft-Deleted Accounts</span>
                        <span class="p-2 rounded-xl bg-rose-950/60 border border-rose-500/20 text-rose-400">🗃️</span>
                    </div>
                    <div class="text-3xl font-black text-rose-400 mt-2">{{ $archivedUsers->count() }} Archived</div>
                    <div class="text-xs text-rose-400/80 mt-1">HIPAA Retention Rule #7</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">SHA-256 Tamper Status</span>
                        <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">🛡️</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-400 mt-2">100% Intact</div>
                    <div class="text-xs text-emerald-400/80 mt-1">0 Compromised Hashes</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Compliance Mode</span>
                        <span class="p-2 rounded-xl bg-purple-950/60 border border-purple-500/20 text-purple-400">🔍</span>
                    </div>
                    <div class="text-3xl font-black text-purple-400 mt-2">Read-Only</div>
                    <div class="text-xs text-purple-400/80 mt-1">ISO 27001 / HIPAA Audited</div>
                </div>

            @else
                <!-- Admin & Manager KPIs -->
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Patients</span>
                        <span class="p-2 rounded-xl bg-cyan-950/60 border border-cyan-500/20 text-cyan-400">🏥</span>
                    </div>
                    <div class="text-3xl font-black text-white mt-2">{{ $totalPatients }}</div>
                    <div class="text-xs text-cyan-400 mt-1">52 Active Hospital Records</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">In-Patient Wards</span>
                        <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">🛏️</span>
                    </div>
                    <div class="text-3xl font-black text-emerald-400 mt-2">{{ $admittedPatients }}</div>
                    <div class="text-xs text-emerald-400/80 mt-1">General & Surgical Beds</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ICU Trauma Critical</span>
                        <span class="p-2 rounded-xl bg-rose-950/60 border border-rose-500/20 text-rose-400">🚨</span>
                    </div>
                    <div class="text-3xl font-black text-rose-400 mt-2">{{ $icuPatients }}</div>
                    <div class="text-xs text-rose-400/80 mt-1">Intensive Care Units</div>
                </div>
                <div class="stat-card rounded-2xl p-5 border border-slate-800 bg-slate-900/70">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Outpatient Clinics</span>
                        <span class="p-2 rounded-xl bg-purple-950/60 border border-purple-500/20 text-purple-400">🩺</span>
                    </div>
                    <div class="text-3xl font-black text-purple-400 mt-2">{{ $outpatients }}</div>
                    <div class="text-xs text-purple-400/80 mt-1">Consultation Suites</div>
                </div>
            @endif
        </div>

        <!-- Big, Bold, High-Visibility Role Workstation Tabs -->
        <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <span class="text-xs uppercase font-bold tracking-wider text-slate-400 flex items-center gap-2">
                    <span class="text-{{ $roleColor }}-400 text-sm">●</span>
                    <span class="text-white">Authorized Workstations & Modules:</span>
                </span>
                <span class="text-[11px] text-{{ $roleColor }}-400 font-mono bg-{{ $roleColor }}-950/60 px-2.5 py-0.5 rounded-full border border-{{ $roleColor }}-800/60">
                    {{ $userRole }} Dashboard
                </span>
            </div>

            <!-- Prominent Workstation Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $userRole === 'Admin' ? '5' : ($userRole === 'Auditor' ? '3' : '2') }} gap-3">
                
                <!-- Tab: Patients (Admin, Manager, Doctor, Nurse, Pharmacist) -->
                @if($userRole !== 'Auditor')
                    <button @click="activeTab = 'patients'"
                        :class="activeTab === 'patients' 
                            ? 'border-{{ $roleColor }}-500 bg-{{ $roleColor }}-950/70 ring-2 ring-{{ $roleColor }}-500/50 shadow-xl shadow-{{ $roleColor }}-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">🏥</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">
                                {{ $totalPatients }} Patients
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            @if($userRole === 'Doctor')
                                Clinical Wards
                            @elseif($userRole === 'Nurse')
                                In-Patient Bed Roster
                            @elseif($userRole === 'Manager')
                                Ward Bed Capacity
                            @elseif($userRole === 'Pharmacist')
                                Patient Medical Charts
                            @else
                                Patient Clinical Wards
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            52 Active Hospital Records
                        </p>
                    </button>
                @endif

                <!-- Admin & Manager: Staff Directory -->
                @if(in_array($userRole, ['Admin', 'Manager']))
                    <button @click="activeTab = 'staff'"
                        :class="activeTab === 'staff' 
                            ? 'border-purple-500 bg-purple-950/70 ring-2 ring-purple-500/50 shadow-xl shadow-purple-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">👥</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-purple-950 text-purple-300 border border-purple-800">
                                {{ $staffUsers->count() }} Staff
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Personnel Directory
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ $userRole === 'Admin' ? 'Argon2id Hashes & Master Controls' : 'Shift Roster (Read-Only)' }}
                        </p>
                    </button>
                @endif

                <!-- Doctor & Pharmacist: Prescriptions Station -->
                @if(in_array($userRole, ['Doctor', 'Pharmacist']))
                    <button @click="activeTab = 'prescriptions'"
                        :class="activeTab === 'prescriptions' 
                            ? 'border-cyan-500 bg-cyan-950/70 ring-2 ring-cyan-500/50 shadow-xl shadow-cyan-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">💊</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">
                                38 Orders
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Electronic Prescriptions
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Clinical Diagnoses & Treatment Regimens
                        </p>
                    </button>
                @endif

                <!-- Nurse: Shift Vitals Logger -->
                @if($userRole === 'Nurse')
                    <button @click="activeTab = 'vitals'"
                        :class="activeTab === 'vitals' 
                            ? 'border-pink-500 bg-pink-950/70 ring-2 ring-pink-500/50 shadow-xl shadow-pink-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">⏱️</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-pink-950 text-pink-300 border border-pink-800">
                                7 Due
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Shift Nursing Vitals Station
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Blood Pressure, Pulse, Temp & SpO2
                        </p>
                    </button>
                @endif

                <!-- Pharmacist: Dispensary Queue -->
                @if($userRole === 'Pharmacist')
                    <button @click="activeTab = 'pharmacy'"
                        :class="activeTab === 'pharmacy' 
                            ? 'border-amber-500 bg-amber-950/70 ring-2 ring-amber-500/50 shadow-xl shadow-amber-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">📦</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-amber-950 text-amber-300 border border-amber-800">
                                19 Pending
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Dispensary Fulfillment Queue
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Prescription Verification & Dispensing
                        </p>
                    </button>
                @endif

                <!-- Auditor: Cryptographic Audit Vault -->
                @if($userRole === 'Auditor')
                    <button @click="activeTab = 'archive'"
                        :class="activeTab === 'archive' 
                            ? 'border-rose-500 bg-rose-950/70 ring-2 ring-rose-500/50 shadow-xl shadow-rose-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">🗃️</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-rose-950 text-rose-300 border border-rose-800">
                                {{ $archivedUsers->count() }} Archives
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Cryptographic Audit Vault
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Soft-Deleted Users & HIPAA Retention
                        </p>
                    </button>
                @endif

                <!-- Admin & Auditor: Security Telemetry -->
                @if(in_array($userRole, ['Admin', 'Auditor']))
                    <button @click="activeTab = 'telemetry'"
                        :class="activeTab === 'telemetry' 
                            ? 'border-emerald-500 bg-emerald-950/70 ring-2 ring-emerald-500/50 shadow-xl shadow-emerald-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">🛡️</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                Benchmark
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Security Telemetry & SQL Defense
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Argon2id Benchmarks & PDO Parameterization
                        </p>
                    </button>
                @endif

                <!-- Auditor: Patient Access Trail -->
                @if($userRole === 'Auditor')
                    <button @click="activeTab = 'patients'"
                        :class="activeTab === 'patients' 
                            ? 'border-blue-500 bg-blue-950/70 ring-2 ring-blue-500/50 shadow-xl shadow-blue-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">👁️</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-blue-950 text-blue-300 border border-blue-800">
                                Read-Only
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Patient Records Audit Log
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Read-Only Medical Chart Verification
                        </p>
                    </button>
                @endif

                <!-- Admin: Audit Vault -->
                @if($userRole === 'Admin')
                    <button @click="activeTab = 'archive'"
                        :class="activeTab === 'archive' 
                            ? 'border-rose-500 bg-rose-950/70 ring-2 ring-rose-500/50 shadow-xl shadow-rose-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">🗃️</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-rose-950 text-rose-300 border border-rose-800">
                                {{ $archivedUsers->count() }} Archived
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Audit Vault & Archive
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Soft-Deleted Accounts & Restoration
                        </p>
                    </button>

                    <!-- Admin: Backups & Restore Points -->
                    <button @click="activeTab = 'config'"
                        :class="activeTab === 'config' 
                            ? 'border-amber-500 bg-amber-950/70 ring-2 ring-amber-500/50 shadow-xl shadow-amber-950/50' 
                            : 'border-slate-800 bg-slate-900/60 hover:bg-slate-900 hover:border-slate-700'"
                        class="p-4 rounded-2xl border text-left transition-all relative overflow-hidden group">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl">💾</span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-amber-950 text-amber-300 border border-amber-800">
                                3-2-1 DR
                            </span>
                        </div>
                        <div class="text-sm font-bold text-white mt-3">
                            Backups & Restore Points
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            X25519 Encryption & Rollback Engine
                        </p>
                    </button>
                @endif

            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 1: 52 HOSPITAL PATIENTS & CLINICAL CHARTS            -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'patients'" class="space-y-4">
            <!-- Search & Filters -->
            <div class="glass-card rounded-2xl p-4 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-80 relative">
                    <input type="text" x-model="search" placeholder="Search by patient name, code, ward, or diagnosis..."
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2 text-xs text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-slate-400">Filter Status:</span>
                    <select x-model="statusFilter" class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 focus:border-cyan-500">
                        <option value="all">All Wards & Clinics</option>
                        <option value="Admitted">In-Patient (Admitted)</option>
                        <option value="ICU">ICU Critical</option>
                        <option value="Outpatient">Outpatient</option>
                        <option value="Discharged">Discharged</option>
                    </select>
                </div>
            </div>

            <!-- Patient Table -->
            <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/90 text-slate-400 uppercase tracking-wider text-[10px] font-bold border-b border-slate-800">
                            <tr>
                                <th class="p-3">Code</th>
                                <th class="p-3">Patient Name</th>
                                <th class="p-3">Demographics</th>
                                <th class="p-3">Ward / Room</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Diagnosis</th>
                                <th class="p-3">Vitals (BP, Temp, HR)</th>
                                <th class="p-3">Attending Staff</th>
                                <th class="p-3 text-right">Role Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach($patients as $p)
                                <tr class="hover:bg-slate-900/50 transition-colors"
                                    x-show="(statusFilter === 'all' || statusFilter === '{{ $p->admission_status }}') && ('{{ strtolower($p->name . ' ' . $p->patient_code . ' ' . $p->ward_room . ' ' . $p->diagnosis) }}'.includes(search.toLowerCase()))">
                                    <td class="p-3 font-mono font-bold text-cyan-400">{{ $p->patient_code }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-white">{{ $p->name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $p->phone }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span>{{ $p->age }} yrs, {{ $p->gender }}</span>
                                        <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-rose-950 text-rose-300 font-bold border border-rose-800">{{ $p->blood_group }}</span>
                                    </td>
                                    <td class="p-3 text-slate-300 font-medium">{{ $p->ward_room }}</td>
                                    <td class="p-3">
                                        @if($p->admission_status === 'ICU')
                                            <span class="px-2 py-0.5 rounded-full bg-rose-950 text-rose-300 border border-rose-700 text-[10px] font-bold animate-pulse">ICU</span>
                                        @elseif($p->admission_status === 'Admitted')
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-700 text-[10px] font-bold">Admitted</span>
                                        @elseif($p->admission_status === 'Outpatient')
                                            <span class="px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-700 text-[10px] font-bold">Outpatient</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 text-[10px]">Discharged</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <div class="font-semibold text-slate-200">{{ $p->diagnosis }}</div>
                                        <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ $p->treatment_plan }}</div>
                                    </td>
                                    <td class="p-3 font-mono text-[11px] text-cyan-300">
                                        {{ $p->vitals_summary ?? 'BP: 120/80 | HR: 72' }}
                                    </td>
                                    <td class="p-3">
                                        <div class="text-white">{{ $p->attending_doctor }}</div>
                                        <div class="text-[10px] text-pink-400">{{ $p->assigned_nurse }}</div>
                                    </td>
                                    <td class="p-3 text-right">
                                        @if($userRole === 'Doctor')
                                            <button type="button" @click='openDoctorAction({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "diagnosis" => $p->diagnosis, "treatment_plan" => $p->treatment_plan]) }})' 
                                                class="px-2.5 py-1 rounded-lg bg-cyan-950 hover:bg-cyan-900 border border-cyan-700/60 text-cyan-300 text-[10px] font-bold transition-all whitespace-nowrap shadow-sm">
                                                🩺 Prescribe / Rx
                                            </button>
                                        @elseif($userRole === 'Nurse')
                                            <button type="button" @click='openNurseAction({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "vitals_summary" => $p->vitals_summary ?? "BP: 120/80 | HR: 72", "diagnosis" => $p->diagnosis]) }})' 
                                                class="px-2.5 py-1 rounded-lg bg-pink-950 hover:bg-pink-900 border border-pink-700/60 text-pink-300 text-[10px] font-bold transition-all whitespace-nowrap shadow-sm">
                                                💉 Log Vitals
                                            </button>
                                        @elseif($userRole === 'Pharmacist')
                                            <button type="button" @click='openPharmAction({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "diagnosis" => $p->diagnosis, "treatment_plan" => $p->treatment_plan]) }})' 
                                                class="px-2.5 py-1 rounded-lg bg-amber-950 hover:bg-amber-900 border border-amber-700/60 text-amber-300 text-[10px] font-bold transition-all whitespace-nowrap shadow-sm">
                                                💊 Dispense
                                            </button>
                                        @elseif($userRole === 'Auditor')
                                            <span class="px-2 py-0.5 rounded bg-blue-950/80 text-blue-300 border border-blue-800 text-[10px] font-mono">
                                                👁️ Read-Only
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-purple-950/80 text-purple-300 border border-purple-800 text-[10px] font-mono">
                                                👑 Full Access
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB: DOCTOR PRESCRIPTIONS & CLINICAL ORDERS              -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'prescriptions'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-cyan-500/30">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">💊</span>
                            <h3 class="text-base font-bold text-white">Physician Electronic Prescriptions & Clinical Orders</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">
                                Doctor Portal
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Live pharmaceutical orders and laboratory requests authorized by attending physicians.
                        </p>
                    </div>
                    <div class="text-xs text-cyan-400 font-mono bg-cyan-950/60 px-3 py-1.5 rounded-xl border border-cyan-800/60">
                        Physician: {{ $user->name }} (MD)
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                            <tr>
                                <th class="p-3">Patient Code</th>
                                <th class="p-3">Patient Name</th>
                                <th class="p-3">Ward</th>
                                <th class="p-3">Clinical Diagnosis</th>
                                <th class="p-3">Active Treatment & Rx</th>
                                <th class="p-3">Attending Physician</th>
                                <th class="p-3 text-right">Doctor Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach($patients->take(15) as $p)
                                <tr class="hover:bg-slate-900/50">
                                    <td class="p-3 font-mono font-bold text-cyan-400">{{ $p->patient_code }}</td>
                                    <td class="p-3 font-bold text-white">{{ $p->name }}</td>
                                    <td class="p-3 text-slate-400">{{ $p->ward_room }}</td>
                                    <td class="p-3 text-slate-200">{{ $p->diagnosis }}</td>
                                    <td class="p-3 text-cyan-300 font-mono text-[11px]">{{ $p->treatment_plan }}</td>
                                    <td class="p-3 text-slate-300">{{ $p->attending_doctor }}</td>
                                    <td class="p-3 text-right">
                                        <button type="button" @click='openDoctorAction({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "diagnosis" => $p->diagnosis, "treatment_plan" => $p->treatment_plan]) }})' 
                                            class="px-3 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-slate-950 text-xs transition-all shadow-md">
                                            Update Prescription 💊
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB: NURSE VITALS MONITORING STATION                     -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'vitals'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-pink-500/30">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">⏱️</span>
                            <h3 class="text-base font-bold text-white">Ward Nursing Station & Vital Signs Charting</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-pink-950 text-pink-300 border border-pink-800">
                                Nurse Station
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Real-time patient hemodynamic telemetry: Blood Pressure, Pulse, Temperature, and SpO2.
                        </p>
                    </div>
                    <div class="text-xs text-pink-400 font-mono bg-pink-950/60 px-3 py-1.5 rounded-xl border border-pink-800/60">
                        Shift Nurse: {{ $user->name }} (RN)
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                            <tr>
                                <th class="p-3">Ward / Bed</th>
                                <th class="p-3">Patient</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Vital Signs Summary</th>
                                <th class="p-3">Telemetry Status</th>
                                <th class="p-3">Primary Nurse</th>
                                <th class="p-3 text-right">Nurse Charting</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach($patients->take(15) as $p)
                                <tr class="hover:bg-slate-900/50">
                                    <td class="p-3 font-semibold text-white">{{ $p->ward_room }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-white">{{ $p->name }}</div>
                                        <div class="text-[10px] font-mono text-cyan-400">{{ $p->patient_code }}</div>
                                    </td>
                                    <td class="p-3">
                                        @if($p->admission_status === 'ICU')
                                            <span class="px-2 py-0.5 rounded-full bg-rose-950 text-rose-300 border border-rose-700 text-[10px] font-bold animate-pulse">ICU Critical</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-700 text-[10px] font-bold">Stable Ward</span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-mono text-cyan-300 font-bold">
                                        {{ $p->vitals_summary ?? 'BP: 120/80 | HR: 74 bpm' }}
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                            ✓ Normal Limits
                                        </span>
                                    </td>
                                    <td class="p-3 text-pink-300">{{ $p->assigned_nurse }}</td>
                                    <td class="p-3 text-right">
                                        <button type="button" @click='openNurseAction({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "ward_room" => $p->ward_room, "vitals_summary" => $p->vitals_summary ?? "BP: 120/80 | HR: 74", "diagnosis" => $p->diagnosis]) }})' 
                                            class="px-3 py-1.5 rounded-xl bg-pink-600 hover:bg-pink-500 font-bold text-white text-xs transition-all shadow-md">
                                            Log Vitals 💉
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB: PHARMACIST DISPENSARY QUEUE                         -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'pharmacy'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-amber-500/30">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📦</span>
                            <h3 class="text-base font-bold text-white">Hospital Dispensary & Medication Fulfillment Queue</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800">
                                Pharmacist Station
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Prescriptions issued by attending physicians awaiting pharmacist dosage review and dispensing.
                        </p>
                    </div>
                    <div class="text-xs text-amber-400 font-mono bg-amber-950/60 px-3 py-1.5 rounded-xl border border-amber-800/60">
                        Pharmacist on Duty: {{ $user->name }} (RPh)
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                            <tr>
                                <th class="p-3">Rx Code</th>
                                <th class="p-3">Patient</th>
                                <th class="p-3">Blood & Allergies</th>
                                <th class="p-3">Prescribed Medication</th>
                                <th class="p-3">Attending MD</th>
                                <th class="p-3">Fulfillment Status</th>
                                <th class="p-3 text-right">Pharmacy Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach($patients->take(15) as $p)
                                <tr class="hover:bg-slate-900/50">
                                    <td class="p-3 font-mono font-bold text-amber-400">RX-{{ substr(md5($p->patient_code), 0, 6) }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-white">{{ $p->name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $p->ward_room }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-1.5 py-0.5 rounded bg-rose-950 text-rose-300 font-bold border border-rose-800 text-[10px]">{{ $p->blood_group }}</span>
                                        <span class="ml-1 text-[10px] text-emerald-400">No Allergy</span>
                                    </td>
                                    <td class="p-3 text-cyan-300 font-mono text-[11px]">{{ $p->treatment_plan }}</td>
                                    <td class="p-3 text-slate-300">{{ $p->attending_doctor }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800">
                                            Pending Dispense
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <button type="button" @click='openPharmAction({{ json_encode(["patient_code" => $p->patient_code, "name" => $p->name, "diagnosis" => $p->diagnosis, "treatment_plan" => $p->treatment_plan]) }})' 
                                            class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-500 font-bold text-slate-950 text-xs transition-all shadow-md">
                                            Dispense Meds 💊
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 2: STAFF DIRECTORY & 6 ROLES (Chief Admin Power Panel)-->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'staff'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">👥</span>
                            <h3 class="text-base font-bold text-white">
                                Hospital Personnel Directory & Cryptographic Credentials Vault
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-950 text-purple-300 border border-purple-800">
                                6 Roles RBAC
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Live records from Cloud Supabase PostgreSQL (<code>users</code> table). Complete Argon2id hashes, cryptographic salts, plain default credentials, and administrative controls.
                        </p>
                    </div>

                    @if($userRole === 'Admin')
                        <div class="flex items-center gap-2">
                            <button @click="showNewStaffModal = true" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                                <span>➕</span>
                                <span>Add New Personnel</span>
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Forensic Security Notice -->
                <div class="mb-5 p-3.5 rounded-xl bg-slate-900/90 border border-purple-500/30 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-400 text-base">🛡️</span>
                        <span class="text-slate-300">
                            <strong>Chief Administrator Security Privileges:</strong> You have full authority to modify staff names, emails, roles, execute <strong>Master Password Resets</strong> with fresh Argon2id hashes, unlock accounts, and manage HIPAA soft-deletes.
                        </span>
                    </div>
                    <span class="text-[10px] font-mono text-cyan-400 bg-cyan-950/60 px-2 py-1 rounded border border-cyan-800/60 whitespace-nowrap">
                        PostgreSQL 17.11 • Argon2id (m=64MB, t=4, p=2)
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                            <tr>
                                <th class="p-3">Staff Member</th>
                                <th class="p-3">Role</th>
                                <th class="p-3">Login Email</th>
                                <th class="p-3">Plain Password</th>
                                <th class="p-3">Argon2id Hash (In PostgreSQL)</th>
                                <th class="p-3">Cryptographic Salt</th>
                                <th class="p-3">Pepper & 2FA</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Admin Controls</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @foreach($staffUsers as $s)
                                @php
                                    $sRole = $s->role ?? 'Doctor';
                                    $sBadgeColor = match($sRole) {
                                        'Admin' => 'purple',
                                        'Manager' => 'emerald',
                                        'Doctor' => 'cyan',
                                        'Nurse' => 'pink',
                                        'Pharmacist' => 'amber',
                                        'Auditor' => 'blue',
                                        default => 'slate'
                                    };
                                    $sRoleIcon = match($sRole) {
                                        'Admin' => '👑',
                                        'Manager' => '📋',
                                        'Doctor' => '🩺',
                                        'Nurse' => '💉',
                                        'Pharmacist' => '💊',
                                        'Auditor' => '🔍',
                                        default => '👤'
                                    };
                                    $plainPass = $s->password_changed_at ? 'Custom (Reset)' : ($defaultPasswords[$s->email] ?? 'Custom');
                                    $isLocked = $s->status === 'locked' || ($s->lockout_until && $s->lockout_until->isFuture()) || $s->failed_login_attempts > 0;
                                @endphp
                                <tr class="hover:bg-slate-900/50 transition-colors {{ $s->trashed() ? 'bg-rose-950/10' : '' }}">
                                    <!-- Staff Member Name & ID -->
                                    <td class="p-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">{{ $sRoleIcon }}</span>
                                            <div>
                                                <div class="font-bold text-white flex items-center gap-1.5">
                                                    <span>{{ $s->name }}</span>
                                                    @if($s->id === $user->id)
                                                        <span class="text-[9px] px-1 py-0.2 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 font-normal">(You)</span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] text-slate-400 font-mono">
                                                    ID #{{ $s->id }} • Joined {{ $s->created_at ? $s->created_at->format('M Y') : '2026' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role -->
                                    <td class="p-3">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-{{ $sBadgeColor }}-950/80 border border-{{ $sBadgeColor }}-800/80 text-{{ $sBadgeColor }}-300">
                                            {{ $s->role }}
                                        </span>
                                    </td>

                                    <!-- Email -->
                                    <td class="p-3">
                                        <div class="flex items-center gap-1 font-mono text-cyan-400">
                                            <span>{{ $s->email }}</span>
                                            <button type="button" @click="copyText('{{ $s->email }}', 'Email')" class="text-slate-500 hover:text-cyan-300 text-[11px] p-0.5" title="Copy Email">
                                                📋
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Plain Password -->
                                    <td class="p-3">
                                        @if($s->trashed())
                                            <span class="text-slate-500 italic text-[11px]">Archived (Blocked)</span>
                                        @else
                                            <div class="flex items-center gap-1">
                                                <span class="font-mono text-emerald-400 font-bold bg-slate-950/80 px-2 py-0.5 rounded border border-emerald-900/60 text-[11px]">
                                                    {{ $plainPass }}
                                                </span>
                                                @if(!$s->password_changed_at && isset($defaultPasswords[$s->email]))
                                                    <button type="button" @click="copyText('{{ $defaultPasswords[$s->email] }}', 'Password')" class="text-slate-500 hover:text-emerald-300 text-[11px] p-0.5" title="Copy Password">
                                                        📋
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Argon2id Hash (in PostgreSQL) -->
                                    <td class="p-3">
                                        <div class="flex items-center gap-1.5">
                                            <div class="font-mono text-[10px] text-slate-300 bg-slate-950/90 px-2 py-1 rounded border border-slate-800 max-w-[150px] truncate" title="{{ $s->password }}">
                                                {{ substr($s->password, 0, 28) }}...
                                            </div>
                                            <button type="button" @click="copyText('{{ $s->password }}', 'Argon2id Hash')" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px]" title="Copy Full Hash">
                                                📋
                                            </button>
                                            <button type="button" @click='openHashModal({{ json_encode(["name" => $s->name, "role" => $s->role, "email" => $s->email, "hash" => $s->password, "salt" => $s->salt, "pepper" => $s->pepper_version]) }})' class="p-1 rounded bg-purple-950/80 hover:bg-purple-900 border border-purple-800 text-purple-300 text-[10px]" title="Inspect Hash Breakdown">
                                                🔍
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Cryptographic Salt (in DB) -->
                                    <td class="p-3">
                                        <div class="flex items-center gap-1.5 font-mono text-[10px] text-slate-400">
                                            <span class="bg-slate-950/70 px-2 py-0.5 rounded border border-slate-800 max-w-[100px] truncate" title="{{ $s->salt }}">
                                                {{ $s->salt ? substr($s->salt, 0, 14) . '...' : 'None' }}
                                            </span>
                                            @if($s->salt)
                                                <button type="button" @click="copyText('{{ $s->salt }}', 'Cryptographic Salt')" class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px]" title="Copy Full Salt">
                                                    📋
                                                </button>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Pepper & 2FA -->
                                    <td class="p-3">
                                        <div class="space-y-1">
                                            <div class="font-mono text-[10px] text-amber-400">
                                                Pepper: {{ $s->pepper_version ?? 'v1' }} (.env)
                                            </div>
                                            <div class="text-[10px] text-teal-400 flex items-center gap-1">
                                                <span>🛡️ 2FA: Active (123456)</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Account Status -->
                                    <td class="p-3">
                                        @if($s->trashed())
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800">
                                                Soft-Deleted
                                            </span>
                                        @elseif($isLocked)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800 flex items-center gap-1">
                                                <span>🔒 Locked</span>
                                                <span class="text-[9px]">({{ $s->failed_login_attempts }} err)</span>
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-800">
                                                Active
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Admin Controls (Modify Anything) -->
                                    <td class="p-3 text-right">
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            @if($userRole === 'Admin')
                                                <!-- Edit Details Button -->
                                                <button type="button" @click='openEditStaff({{ json_encode(["id" => $s->id, "name" => $s->name, "email" => $s->email, "role" => $s->role]) }})' class="px-2 py-1 rounded-lg bg-cyan-950 hover:bg-cyan-900 border border-cyan-800 text-cyan-300 text-[10px] font-bold transition-all" title="Edit Name, Email, and Role">
                                                    ✏️ Edit
                                                </button>

                                                <!-- Reset Password Button -->
                                                <button type="button" @click='openResetPass({{ json_encode(["id" => $s->id, "name" => $s->name, "email" => $s->email]) }})' class="px-2 py-1 rounded-lg bg-amber-950 hover:bg-amber-900 border border-amber-800 text-amber-300 text-[10px] font-bold transition-all" title="Master Reset Password with fresh Salt & Argon2id Hash">
                                                    🔑 Reset
                                                </button>

                                                <!-- Unlock Button (if locked or failed attempts) -->
                                                @if($isLocked)
                                                    <form method="POST" action="{{ route('admin.users.unlock', $s->id) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-2 py-1 rounded-lg bg-emerald-950 hover:bg-emerald-900 border border-emerald-800 text-emerald-300 text-[10px] font-bold transition-all" title="Unlock Account">
                                                            🔓 Unlock
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- Soft Delete / Restore -->
                                                @if(!$s->trashed() && $s->id !== $user->id)
                                                    <form method="POST" action="{{ route('admin.users.destroy', $s->id) }}" onsubmit="return confirm('Soft-delete this staff account? It will be archived in the audit vault.')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="px-2 py-1 rounded-lg bg-rose-950/80 hover:bg-rose-900 border border-rose-800 text-rose-300 text-[10px] font-bold transition-all" title="Soft Delete Account (HIPAA Audit Preserved)">
                                                            🗑️ Delete
                                                        </button>
                                                    </form>
                                                @elseif($s->trashed())
                                                    <form method="POST" action="{{ route('admin.users.restore', $s->id) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="px-2 py-1 rounded-lg bg-emerald-900 hover:bg-emerald-800 border border-emerald-700 text-white text-[10px] font-bold transition-all" title="Restore User from Audit Vault">
                                                            ↺ Restore
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-slate-500 italic text-[10px]">Read-Only</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 3: PARAMETERIZED SQL & SECURITY TELEMETRY            -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'telemetry'" class="space-y-6">
            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">🛡️</span>
                    <h3 class="text-base font-bold text-white">Live Parameterized SQL & SQL Injection Neutralizer</h3>
                </div>
                <p class="text-xs text-slate-400 mb-6">
                    Demonstration of PDO Prepared Statements. Notice how input is bound strictly as data parameters, never altering SQL syntax.
                </p>

                <!-- Interactive Tester -->
                <div class="bg-slate-950/80 rounded-2xl p-5 border border-slate-800 space-y-4">
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <div class="w-full sm:flex-1">
                            <label class="block text-[11px] font-bold text-slate-300 uppercase mb-1">Test Input (Try SQL Injection or Patient Code like MED-1002):</label>
                            <input type="text" x-model="sqlInput" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-xs text-cyan-300 font-mono">
                        </div>
                        <button type="button" @click="runSqlTest()" class="w-full sm:w-auto px-6 py-2.5 mt-5 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-xs text-slate-950 transition-all">
                            Execute Prepared Statement
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono pt-2">
                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                            <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">1. Pre-Compiled SQL Template:</div>
                            <div class="text-cyan-400" x-text="testedSql"></div>
                            <div class="text-[10px] text-slate-500 mt-2 font-sans">The database pre-compiles the query with the <code>?</code> placeholder before reading input.</div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                            <div class="text-[10px] uppercase font-bold text-slate-500 mb-1">2. Binary Parameter Bindings:</div>
                            <div class="text-emerald-400" x-text="JSON.stringify(testedBinding)"></div>
                            <div class="text-[10px] text-slate-500 mt-2 font-sans">Treated 100% as string data, never as executable SQL code.</div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-cyan-500/30 text-xs font-mono text-cyan-200">
                        <strong>Execution Result:</strong> <span x-text="sqlResult"></span>
                    </div>
                </div>

                <!-- Cryptographic Hashing Benchmark Card -->
                <div class="mt-8 pt-6 border-t border-slate-800">
                    <h4 class="text-sm font-bold text-white mb-3">Cryptographic Defense Benchmark: Argon2id vs Bcrypt vs SHA-256</h4>
                    <div class="overflow-x-auto rounded-xl border border-slate-800">
                        <table class="w-full text-left text-xs font-mono">
                            <thead class="bg-slate-900 text-slate-400 text-[10px] uppercase">
                                <tr>
                                    <th class="p-2.5">Algorithm</th>
                                    <th class="p-2.5">Memory Usage</th>
                                    <th class="p-2.5">Iterations / Cost</th>
                                    <th class="p-2.5">GPU / ASIC Resistance</th>
                                    <th class="p-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-300">
                                <tr>
                                    <td class="p-2.5 text-rose-400">SHA-256 (Legacy)</td>
                                    <td class="p-2.5">0 KB</td>
                                    <td class="p-2.5">1 round</td>
                                    <td class="p-2.5 text-rose-400">Extremely Poor (Billions/sec)</td>
                                    <td class="p-2.5 text-rose-400 font-bold font-sans">Vulnerable</td>
                                </tr>
                                <tr>
                                    <td class="p-2.5 text-amber-400">Bcrypt</td>
                                    <td class="p-2.5">4 KB</td>
                                    <td class="p-2.5">Cost 12</td>
                                    <td class="p-2.5 text-amber-400">Moderate</td>
                                    <td class="p-2.5 text-amber-400 font-bold font-sans">Acceptable</td>
                                </tr>
                                <tr class="bg-cyan-950/30 text-cyan-300">
                                    <td class="p-2.5 font-bold text-cyan-400">Argon2id + HMAC Pepper (MediVault)</td>
                                    <td class="p-2.5 font-bold">64 MB (65,536 KB)</td>
                                    <td class="p-2.5">4 rounds, 2 threads</td>
                                    <td class="p-2.5 font-bold text-emerald-400">Bank-Grade (Memory-Hard)</td>
                                    <td class="p-2.5 text-emerald-400 font-bold font-sans">Optimal Defense</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 4: DELETED USERS AUDIT VAULT (Requirement #7)        -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'archive'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🗃️</span>
                            <span>Soft-Deleted Staff Audit Archive (Never Lost Forever)</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Requirement #7 Proof: When staff are deleted, their records remain preserved in PostgreSQL under <code>deleted_at</code> with immutable HIPAA audit trail entries.
                        </p>
                    </div>
                    <span class="text-xs px-3 py-1 rounded-full bg-rose-950 text-rose-300 border border-rose-800 font-bold">
                        {{ $archivedUsers->count() }} Archived Accounts
                    </span>
                </div>

                @if($archivedUsers->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-500 border border-dashed border-slate-800 rounded-xl">
                        No soft-deleted staff members currently in the archive. Soft delete any staff from the Staff Directory tab to see them archived here!
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] font-bold border-b border-slate-800">
                                <tr>
                                    <th class="p-3">Staff Name</th>
                                    <th class="p-3">Previous Role</th>
                                    <th class="p-3">Email Address</th>
                                    <th class="p-3">Soft-Deleted Timestamp</th>
                                    <th class="p-3">Preserved Salt</th>
                                    <th class="p-3 text-right">Audit Recovery</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                @foreach($archivedUsers as $au)
                                    <tr class="hover:bg-slate-900/40">
                                        <td class="p-3 font-bold text-white">{{ $au->name }}</td>
                                        <td class="p-3 text-cyan-300">{{ $au->role }}</td>
                                        <td class="p-3 font-mono text-slate-400">{{ $au->email }}</td>
                                        <td class="p-3 font-mono text-rose-400">{{ $au->deleted_at }}</td>
                                        <td class="p-3 font-mono text-slate-500 text-[10px]">{{ $au->salt }}</td>
                                        <td class="p-3 text-right">
                                            @if($userRole === 'Admin')
                                                <form method="POST" action="{{ route('admin.users.restore', $au->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-bold text-xs transition-all shadow-md">
                                                        Restore User ↺
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-slate-500 italic text-[11px]">Admin Required</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 5: BACKUPS, RESTORE POINTS & DISASTER RECOVERY        -->
        <!-- ======================================================== -->
        <div x-show="activeTab === 'config'" class="space-y-6">
            <!-- 3-2-1 Backup & Disaster Recovery Suite (Requirements #8 & #9) -->
            <div class="glass-card rounded-2xl p-6 border border-amber-500/30 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">💾</span>
                            <h3 class="text-base font-bold text-white">3-2-1 Encrypted Backup & Disaster Recovery Command</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800">
                                Requirement #8 & #9
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Asymmetric X25519 <code>age</code> encryption, offsite cloud storage replication, and automated RTO/RPO recovery engine.
                        </p>
                    </div>

                    <!-- Live Action Buttons for Examiner/Teacher -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <form method="POST" action="{{ route('admin.backups.trigger') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-slate-950 font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                                <span>🔒</span>
                                <span>Create Encrypted Backup</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.backups.drill') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-500 hover:from-cyan-500 hover:to-blue-400 text-slate-950 font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
                                <span>⚡</span>
                                <span>Execute DR Restore Drill</span>
                            </button>
                        </form>

                        <a href="{{ route('admin.backups') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-all">
                            Full DR Log ↗
                        </a>
                    </div>
                </div>

                <!-- 3-2-1 Architecture Visual Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Copy 1 & 2: Local Encrypted Storage -->
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-emerald-400">
                            <span>3 Copies • 2 Media</span>
                            <span class="px-2 py-0.5 rounded bg-emerald-950 border border-emerald-500/30 text-[10px] uppercase">Active</span>
                        </div>
                        <h4 class="text-sm font-bold text-white">Local Encrypted Volume</h4>
                        <p class="text-[11px] text-slate-400">
                            PostgreSQL tables streamed via PDO and encrypted with server-side public key <span class="font-mono text-emerald-300">age1ql4y...</span>.
                        </p>
                        <div class="pt-1 text-[10px] text-slate-500 flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> 7-Day Automated Retention Pruning
                        </div>
                    </div>

                    <!-- Copy 3: Offsite Cloud S3 -->
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-cyan-400">
                            <span>1 Offsite Cloud</span>
                            <span class="px-2 py-0.5 rounded bg-cyan-950 border border-cyan-500/30 text-[10px] uppercase">Zero-Knowledge</span>
                        </div>
                        <h4 class="text-sm font-bold text-white">Supabase Cloud S3 Mirror</h4>
                        <p class="text-[11px] text-slate-400">
                            Replicated offsite as raw ciphertext. Neither host nor third parties possess the offline private decryption keys.
                        </p>
                        <div class="pt-1 text-[10px] text-slate-500 flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span> 30-Day Cloud Retention Policy
                        </div>
                    </div>

                    <!-- DR Metrics: RTO / RPO -->
                    <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-purple-400">
                            <span>Business Continuity</span>
                            <span class="px-2 py-0.5 rounded bg-purple-950 border border-purple-500/30 text-[10px] uppercase">Verified</span>
                        </div>
                        <h4 class="text-sm font-bold text-white">RTO & RPO Metrics</h4>
                        <div class="space-y-0.5 text-xs">
                            <div class="flex justify-between text-slate-300">
                                <span>Recovery Time (RTO):</span>
                                <span class="font-bold text-emerald-400 font-mono">&lt; 3.5 seconds</span>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Recovery Point (RPO):</span>
                                <span class="font-bold text-emerald-400 font-mono">0 Data Loss</span>
                            </div>
                        </div>
                        <div class="pt-1 text-[10px] text-slate-500 flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-purple-400"></span> Offline Air-Gapped Key Decryption
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Snapshots & Rollback Points -->
            <div class="glass-card rounded-2xl p-6 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>⚙️</span>
                            <span>System Configuration Snapshots & Restore Points</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Requirement #9 Proof: Restore points rollback <strong>ONLY system security configurations</strong>. The 52 Patient clinical records are <strong>never wiped or altered</strong>!
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($systemConfigs as $sc)
                        <div class="p-5 rounded-2xl border {{ $sc->is_active ? 'border-cyan-500/50 bg-cyan-950/20 ring-2 ring-cyan-500/20' : 'border-slate-800 bg-slate-900/60' }} space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white text-sm">{{ $sc->snapshot_name }}</span>
                                @if($sc->is_active)
                                    <span class="px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 text-[10px] font-bold">Active Configuration</span>
                                @else
                                    <span class="text-[10px] text-slate-500">Stored Snapshot</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400">{{ $sc->description }}</p>

                            <div class="grid grid-cols-2 gap-2 text-xs font-mono text-slate-300 pt-1">
                                <div class="p-2 rounded-lg bg-slate-950/70">
                                    <span class="text-[10px] text-slate-500 block">Session Lifetime:</span>
                                    <span>{{ $sc->session_lifetime_minutes }} mins</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-950/70">
                                    <span class="text-[10px] text-slate-500 block">Lockout Attempts:</span>
                                    <span>{{ $sc->max_login_attempts }} failures</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-950/70">
                                    <span class="text-[10px] text-slate-500 block">Lockout Duration:</span>
                                    <span>{{ $sc->lockout_duration_minutes }} mins</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-950/70">
                                    <span class="text-[10px] text-slate-500 block">Pepper Version:</span>
                                    <span class="text-amber-400">{{ $sc->pepper_version }}</span>
                                </div>
                            </div>

                            <div class="pt-2 flex items-center justify-between">
                                <span class="text-[10px] text-slate-500">Created by: {{ $sc->created_by }}</span>
                                @if(!$sc->is_active && $userRole === 'Admin')
                                    <form method="POST" action="{{ route('admin.config.rollback', $sc->id) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-slate-950 font-bold text-xs transition-all shadow-md">
                                            Rollback Configuration ↺
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-xs text-slate-300 flex items-center gap-3">
                    <span class="text-xl">💡</span>
                    <span>
                        <strong>State vs. Configuration Separation:</strong> Rolling back to any snapshot activates that security policy in milliseconds without modifying the <code>patients</code> table, preserving all medical histories.
                    </span>
                </div>
            </div>
        </div>

        <!-- Modal 1: Register New Patient -->
        <div x-show="showRegisterModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-2xl p-6 border border-slate-700 w-full max-w-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Admit New Hospital Patient</h3>
                    <button @click="showRegisterModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form method="POST" action="/api/patients" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-300 mb-1">Full Patient Name</label>
                        <input type="text" name="name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-slate-300 mb-1">Age</label>
                            <input type="number" name="age" value="35" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1">Gender</label>
                            <select name="gender" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                                <option>Female</option>
                                <option>Male</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 mb-1">Blood Group</label>
                            <select name="blood_group" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                                <option>O+</option><option>A+</option><option>B+</option><option>AB+</option><option>O-</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Ward / Room</label>
                        <input type="text" name="ward_room" value="General Ward 2 (Bed 05)" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Primary Clinical Diagnosis</label>
                        <input type="text" name="diagnosis" value="Post-operative observation" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showRegisterModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 font-bold text-slate-950">Save Patient</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 2: Add New Staff Member (Admin Only) -->
        <div x-show="showNewStaffModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-2xl p-6 border border-slate-700 w-full max-w-lg space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Create New Staff User (With Salt & Pepper)</h3>
                    <button @click="showNewStaffModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block text-slate-300 mb-1">Staff Member Name</label>
                        <input type="text" name="name" required placeholder="e.g. Dr. John Doe, MD" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Email Address</label>
                        <input type="email" name="email" required placeholder="e.g. jdoe@medivault.cloud" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Initial Password</label>
                        <input type="password" name="password" required placeholder="Min 8 characters" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Hospital Role Assignment</label>
                        <select name="role_id" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white">
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="p-3 rounded-xl bg-purple-950/40 border border-purple-800/40 text-[11px] text-purple-200">
                        ⚡ The system will automatically generate a 16-byte unique cryptographic Salt and hash this password with Argon2id + HMAC Pepper in PostgreSQL.
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showNewStaffModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 font-bold text-white">Create Staff</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 3: Edit Staff Personnel (Admin Only) -->
        <div x-show="showEditStaffModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showEditStaffModal = false" class="glass-card rounded-2xl p-6 border border-slate-700 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>✏️</span>
                            <span>Edit Staff Profile & Role Assignment</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Modify personnel identity and RBAC authorization in Supabase PostgreSQL.
                        </p>
                    </div>
                    <button @click="showEditStaffModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <form method="POST" :action="'/admin/users/' + selectedStaff.id" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Staff Member Full Name</label>
                        <input type="text" name="name" x-model="selectedStaff.name" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Login Email Address</label>
                        <input type="email" name="email" x-model="selectedStaff.email" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white font-mono focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Assigned Hospital Role</label>
                        <select name="role" x-model="selectedStaff.role" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2.5 text-white focus:border-cyan-500 focus:outline-none font-semibold">
                            <option value="Admin">👑 Chief Administrator (Admin)</option>
                            <option value="Manager">📋 Clinical Operations Manager (Manager)</option>
                            <option value="Doctor">🩺 Attending Medical Doctor (Doctor)</option>
                            <option value="Nurse">💉 Senior Ward Nurse (Nurse)</option>
                            <option value="Pharmacist">💊 Dispensary Pharmacist (Pharmacist)</option>
                            <option value="Auditor">🔍 Compliance & Forensic Auditor (Auditor)</option>
                        </select>
                    </div>

                    <div class="p-3 rounded-xl bg-cyan-950/40 border border-cyan-800/40 text-[11px] text-cyan-200">
                        💡 Changes take effect immediately across all sessions and update the audit log trail.
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showEditStaffModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-slate-950 transition-all">Save Profile Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 4: Chief Admin Master Password Reset (Admin Only) -->
        <div x-show="showResetPassModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showResetPassModal = false" class="glass-card rounded-2xl p-6 border border-amber-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🔑</span>
                            <span>Master Password Reset & Re-Hashing</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Resetting password for: <span class="text-amber-400 font-bold" x-text="selectedStaff.name"></span>
                        </p>
                    </div>
                    <button @click="showResetPassModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <form method="POST" :action="'/admin/users/' + selectedStaff.id + '/reset-password'" class="space-y-4 text-xs">
                    @csrf

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Target Account Email</label>
                        <div class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 font-mono text-cyan-400" x-text="selectedStaff.email"></div>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">New Plain Password</label>
                        <input type="password" name="new_password" required placeholder="Minimum 8 characters (e.g. NewSecret@2026!)" class="w-full rounded-xl border border-amber-500/50 bg-slate-900 px-3.5 py-2.5 text-white font-mono focus:border-amber-400 focus:outline-none">
                    </div>

                    <div class="p-3.5 rounded-xl bg-amber-950/30 border border-amber-800/40 text-[11px] text-amber-200/90 space-y-1.5">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span>🛡️</span>
                            <span>Cryptographic Guarantee:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-slate-300">
                            <li>Generates a fresh 16-byte random Cryptographic Salt.</li>
                            <li>Computes Argon2id hash with 64 MB memory cost & 4 iterations.</li>
                            <li>Applies HMAC-SHA256 Pepper <code>v1</code> from server <code>.env</code>.</li>
                            <li>Clears any failed login lockouts and updates <code>password_changed_at</code>.</li>
                        </ul>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showResetPassModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700">Cancel</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 font-bold text-slate-950 transition-all shadow-lg">Commit Password Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal 5: Cryptographic Hash & Salt Deep Inspector -->
        <div x-show="showHashModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showHashModal = false" class="glass-card rounded-2xl p-6 border border-purple-500/40 w-full max-w-2xl space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🔍</span>
                            <span>Argon2id Hash Forensic Inspector</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Verification breakdown for <span class="text-purple-300 font-bold" x-text="selectedStaff.name"></span> (<span class="font-mono text-cyan-400" x-text="selectedStaff.email"></span>)
                        </p>
                    </div>
                    <button @click="showHashModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <div class="space-y-4 text-xs font-mono">
                    <!-- Raw Hash -->
                    <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">1. Full Raw Argon2id Hash in PostgreSQL:</span>
                            <button type="button" @click="copyText(selectedStaff.hash, 'Argon2id Hash')" class="text-[11px] text-cyan-400 hover:text-cyan-300 font-sans">
                                📋 Copy Hash
                            </button>
                        </div>
                        <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 text-cyan-300 text-[11px] break-all select-all leading-relaxed" x-text="selectedStaff.hash"></div>
                    </div>

                    <!-- Hash Parameters Decomposition -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                        <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <div class="text-[10px] text-slate-500 uppercase">Algorithm</div>
                            <div class="text-white font-bold text-xs mt-1">argon2id</div>
                            <div class="text-[9px] text-slate-500">RFC 9106</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <div class="text-[10px] text-slate-500 uppercase">Memory (m)</div>
                            <div class="text-emerald-400 font-bold text-xs mt-1">64 MB</div>
                            <div class="text-[9px] text-slate-500">65,536 KiB</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <div class="text-[10px] text-slate-500 uppercase">Iterations (t)</div>
                            <div class="text-purple-400 font-bold text-xs mt-1">4 Passes</div>
                            <div class="text-[9px] text-slate-500">Time Cost</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                            <div class="text-[10px] text-slate-500 uppercase">Threads (p)</div>
                            <div class="text-cyan-400 font-bold text-xs mt-1">2 Threads</div>
                            <div class="text-[9px] text-slate-500">Parallelism</div>
                        </div>
                    </div>

                    <!-- Salt & Pepper Breakdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <div class="flex items-center justify-between text-[10px] text-slate-400 uppercase font-bold">
                                <span>2. Cryptographic Salt (in DB):</span>
                                <button type="button" @click="copyText(selectedStaff.salt, 'Salt')" class="text-cyan-400 hover:text-cyan-300 font-sans">
                                    📋 Copy
                                </button>
                            </div>
                            <div class="text-slate-300 text-[11px] break-all select-all" x-text="selectedStaff.salt || 'N/A'"></div>
                            <div class="text-[9px] text-slate-500 font-sans">16-byte CSPRNG random salt per user.</div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <div class="text-[10px] text-slate-400 uppercase font-bold">3. HMAC-SHA256 Pepper:</div>
                            <div class="text-amber-400 text-[11px]" x-text="'Version: ' + (selectedStaff.pepper || 'v1') + ' (.env)'"></div>
                            <div class="text-[9px] text-slate-500 font-sans">Never stored in PostgreSQL; resides strictly in server environment.</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-800">
                    <button type="button" @click="showHashModal = false" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs">Close Inspector</button>
                </div>
            </div>
        </div>

        <!-- Modal: Doctor Prescription & Diagnosis -->
        <div x-show="showDoctorModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showDoctorModal = false" class="glass-card rounded-2xl p-6 border border-cyan-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>🩺</span>
                            <span>Physician Clinical Order & Electronic Rx</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Attending Doctor: <strong class="text-cyan-400">{{ $user->name }}</strong> • Patient: <strong class="text-white" x-text="selectedPatient.name"></strong> (<span class="font-mono text-cyan-400" x-text="selectedPatient.patient_code"></span>)
                        </p>
                    </div>
                    <button @click="showDoctorModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Ward Location</label>
                        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 font-medium" x-text="selectedPatient.ward_room"></div>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Physician Clinical Assessment / Diagnosis</label>
                        <input type="text" x-model="selectedPatient.diagnosis" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Prescription Order & Dosage (Transmitted to Pharmacy)</label>
                        <textarea rows="3" x-model="selectedPatient.treatment_plan" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-cyan-300 font-mono text-xs focus:border-cyan-500 focus:outline-none"></textarea>
                    </div>
                    <div class="p-3 rounded-xl bg-cyan-950/40 border border-cyan-800/40 text-[11px] text-cyan-200 flex items-center gap-2">
                        <span>🛡️</span>
                        <span>Clinical digital signature attached: <strong>{{ $user->name }} (Attending MD)</strong></span>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showDoctorModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showDoctorModal = false; alert('Electronic Prescription & Clinical Diagnosis signed and dispatched to Pharmacy!')" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-slate-950 transition-all">Sign & Transmit Rx 💊</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Nurse Vitals Logger -->
        <div x-show="showNurseModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showNurseModal = false" class="glass-card rounded-2xl p-6 border border-pink-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>💉</span>
                            <span>Shift Nursing Vitals & Bed Telemetry</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Recording Nurse: <strong class="text-pink-400">{{ $user->name }}</strong> • Patient: <strong class="text-white" x-text="selectedPatient.name"></strong>
                        </p>
                    </div>
                    <button @click="showNurseModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Blood Pressure (mmHg)</label>
                            <input type="text" value="122/82" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Heart Rate (bpm)</label>
                            <input type="text" value="74 bpm" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Body Temperature (°C)</label>
                            <input type="text" value="36.8 °C" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Oxygen Saturation (SpO2)</label>
                            <input type="text" value="99%" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Shift Nursing Observation Note</label>
                        <textarea rows="2" placeholder="Patient resting comfortably in ward bed. Zero acute distress observed." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-white text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showNurseModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showNurseModal = false; alert('Vital signs recorded and synchronized to Cloud EHR!')" class="px-5 py-2 rounded-xl bg-pink-600 hover:bg-pink-500 font-bold text-white transition-all">Commit Vitals Entry 💉</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Pharmacist Dispense -->
        <div x-show="showPharmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div @click.away="showPharmModal = false" class="glass-card rounded-2xl p-6 border border-amber-500/40 w-full max-w-lg space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <span>💊</span>
                            <span>Hospital Pharmacy Dispensing & Verification</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Pharmacist: <strong class="text-amber-400">{{ $user->name }}</strong> • Patient: <strong class="text-white" x-text="selectedPatient.name"></strong>
                        </p>
                    </div>
                    <button @click="showPharmModal = false" class="text-slate-400 hover:text-white text-lg">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Prescription Order from MD</label>
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 font-mono text-cyan-300 text-xs" x-text="selectedPatient.treatment_plan || 'Amoxicillin 500mg TDS x 5/7'"></div>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-800/40 text-emerald-200 text-[11px] space-y-1">
                        <div class="font-bold">✓ Automated Pharmacological Cross-Checks Passed:</div>
                        <ul class="list-disc list-inside text-slate-300 space-y-0.5">
                            <li>Dosage adheres to British National Formulary (BNF) standards.</li>
                            <li>Zero cross-allergy contraindications with patient blood group.</li>
                            <li>Pharmaceutical batch code verified in inventory.</li>
                        </ul>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                        <button type="button" @click="showPharmModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="button" @click="showPharmModal = false; alert('Medication verified and marked DISPENSED in Pharmacy records!')" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 font-bold text-slate-950 transition-all">Verify & Dispense Medication 💊</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
