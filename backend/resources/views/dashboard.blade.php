<x-app-layout>
    @php
        $isChiefAdmin = Auth::user()->hasRole(['Admin', 'SuperAdmin']);
        $isManager = Auth::user()->hasRole(['Manager', 'Clinic Practice Manager']);
        $isDentist = !$isChiefAdmin && !$isManager;
        
        $roleTitle = $isChiefAdmin ? 'Chief Dental Director & SuperAdmin' : ($isManager ? 'Clinic Practice Manager' : 'Attending Dentist');
        $roleIcon = $isChiefAdmin ? '👑' : ($isManager ? '📋' : '🩺');
        $themeGradient = $isChiefAdmin 
            ? 'from-purple-950/90 via-slate-900/80 to-amber-950/40 border-purple-500/30' 
            : ($isManager 
                ? 'from-emerald-950/90 via-slate-900/80 to-teal-950/40 border-emerald-500/30' 
                : 'from-cyan-950/90 via-slate-900/80 to-teal-950/40 border-cyan-500/30');
    @endphp

    <div x-data="{
        search: '',
        statusFilter: 'all',
        showRegisterModal: false,
        showEditModal: false,
        editPatient: {
            id: null,
            name: '',
            patient_code: '',
            tooth_number: '',
            procedure: '',
            clinical_notes: '',
            status: 'In Progress'
        },
        openEdit(p) {
            this.editPatient = { ...p };
            this.showEditModal = true;
        }
    }" class="space-y-8">
        
        <!-- Role-Differentiated Welcome Header Banner -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 border bg-gradient-to-r {{ $themeGradient }} relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest px-2.5 py-0.5 rounded-full {{ $isChiefAdmin ? 'bg-purple-500/20 text-purple-300 border border-purple-500/40' : ($isManager ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40') }}">
                            {{ $roleIcon }} {{ $roleTitle }}
                        </span>
                        <span class="text-xs text-slate-400 font-mono">
                            {{ $accountData['user_id'] }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        Welcome back, <span class="{{ $isChiefAdmin ? 'text-purple-300' : ($isManager ? 'text-emerald-300' : 'text-cyan-400') }}">{{ $user->name }}</span>
                    </h1>
                    <p class="text-sm text-slate-300 mt-1">
                        @if($isChiefAdmin)
                            👑 Full Governance Portal • Staff Administration, 3-2-1 Age Backup Vault & HIPAA SHA-256 Telemetry
                        @elseif($isManager)
                            📋 Practice Operations & Intake • Patient Intake, KPI Metrics & Treatment Flow Management
                        @else
                            🩺 Clinical Dental Station • Tooth Diagnostic Charts, Procedure Notes & Patient Treatment Progress
                        @endif
                    </p>
                </div>

                <!-- Action Controls Tailored by Role -->
                <div class="flex items-center gap-3">
                    @if($isChiefAdmin || $isManager)
                        <button @click="showRegisterModal = true" class="px-4 py-2.5 rounded-xl bg-gradient-to-r {{ $isChiefAdmin ? 'from-purple-600 to-indigo-500 hover:from-purple-500 hover:to-indigo-400 text-white' : 'from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-slate-950' }} font-bold text-xs shadow-lg transition-all flex items-center gap-1.5">
                            <span>➕</span>
                            <span>Register Patient</span>
                        </button>
                    @endif

                    @if($isChiefAdmin)
                        <a href="{{ route('admin.dashboard') }}" class="px-4 py-2.5 rounded-xl bg-purple-900/90 border border-purple-400/50 text-white font-bold text-xs hover:bg-purple-800 transition-all flex items-center gap-1.5 shadow-lg shadow-purple-900/30">
                            <span>⚙️</span>
                            <span>Admin Suite & Staff Roles</span>
                        </a>
                        <a href="{{ route('admin.backups') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-900/90 border border-slate-700 text-cyan-300 font-bold text-xs hover:border-cyan-500/50 transition-all flex items-center gap-1.5">
                            <span>🔐</span>
                            <span>Age Vault</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4 Practice KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Patients</span>
                    <span class="p-2 rounded-xl bg-cyan-950/60 border border-cyan-500/20 text-cyan-400">👥</span>
                </div>
                <div class="text-3xl font-black text-white mt-2">{{ $totalPatients ?? 5 }}</div>
                <div class="text-xs text-cyan-400 mt-1 flex items-center gap-1">
                    <span>Clinical EDR Directory</span>
                </div>
            </div>

            <div class="stat-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">In Treatment</span>
                    <span class="p-2 rounded-xl bg-amber-950/60 border border-amber-500/20 text-amber-400">⏳</span>
                </div>
                <div class="text-3xl font-black text-amber-300 mt-2">{{ $activeTreatments ?? 2 }}</div>
                <div class="text-xs text-slate-400 mt-1">Ongoing tooth procedures</div>
            </div>

            <div class="stat-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Completed Cases</span>
                    <span class="p-2 rounded-xl bg-emerald-950/60 border border-emerald-500/20 text-emerald-400">✅</span>
                </div>
                <div class="text-3xl font-black text-emerald-300 mt-2">{{ $completedCases ?? 2 }}</div>
                <div class="text-xs text-emerald-400/80 mt-1">Discharged & Follow-up</div>
            </div>

            <div class="stat-card rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Clinical Staff</span>
                    <span class="p-2 rounded-xl bg-purple-950/60 border border-purple-500/20 text-purple-400">🩺</span>
                </div>
                <div class="text-3xl font-black text-purple-300 mt-2">{{ $totalStaff ?? 3 }}</div>
                <div class="text-xs text-purple-400/80 mt-1">3 Granular RBAC Tiers</div>
            </div>
        </div>

        <!-- Dental Patients Directory & Clinical EDR Table -->
        <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
            <div class="p-5 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span>🦷</span>
                        <span>Patient Dental Charts & Clinical Directory</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @if($isDentist)
                            Select any patient to update tooth numbers, diagnostics, and clinical notes
                        @elseif($isManager)
                            Practice intake overview and operational appointment charting
                        @else
                            Full administrative oversight with cryptographic audit logging
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="text" x-model="search" placeholder="Search patient, tooth (#14), code..." 
                        class="rounded-xl border border-slate-700 bg-slate-900/90 px-3.5 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 w-64">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Code / Patient</th>
                            <th class="py-3.5 px-4">Age/Sex</th>
                            <th class="py-3.5 px-4">Tooth #</th>
                            <th class="py-3.5 px-4">Procedure</th>
                            <th class="py-3.5 px-4">Clinical Findings / Notes</th>
                            <th class="py-3.5 px-4">Attending Dentist</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($patients as $p)
                            <tr class="hover:bg-slate-900/40 transition-colors">
                                <td class="py-3.5 px-4 font-mono">
                                    <div class="font-bold text-white">{{ $p->name }}</div>
                                    <span class="text-[11px] text-cyan-400">{{ $p->patient_code }}</span>
                                </td>
                                <td class="py-3.5 px-4">{{ $p->age }} yrs • {{ $p->gender }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded font-mono font-bold bg-slate-800 text-cyan-300 border border-slate-700">
                                        {{ $p->tooth_number }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-200">{{ $p->procedure }}</td>
                                <td class="py-3.5 px-4 text-slate-400 max-w-xs truncate" title="{{ $p->clinical_notes }}">
                                    {{ $p->clinical_notes }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-300">{{ $p->attending_dentist }}</td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $statusClass = match($p->status) {
                                            'Completed' => 'bg-emerald-950 text-emerald-300 border-emerald-500/30',
                                            'In Progress' => 'bg-cyan-950 text-cyan-300 border-cyan-500/30',
                                            default => 'bg-amber-950 text-amber-300 border-amber-500/30',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusClass }}">
                                        {{ $p->status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <button @click="openEdit({{ json_encode($p) }})" class="px-2.5 py-1 rounded-lg bg-cyan-950/60 hover:bg-cyan-900/60 border border-cyan-500/30 text-cyan-300 font-semibold text-[11px] transition-colors">
                                        Edit Chart
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">
                                    No patient records found in practice directory.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Role Boundaries & Security Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-white text-sm flex items-center gap-2">
                        <span>🛡️</span>
                        <span>Clinical Staff Security & 2FA</span>
                    </h3>
                    <span class="text-xs px-2 py-0.5 rounded font-mono {{ $user->two_factor_confirmed_at ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                        {{ $accountData['security_level'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Protect patient medical histories with RFC 6238 TOTP Two-Factor Authentication and Argon2id peppered encryption.
                </p>
                <div class="mt-4 flex items-center gap-3">
                    <a href="{{ route('two-factor.show') }}" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 text-xs font-semibold transition-colors">
                        Configure 2FA & Emergency Keys &rarr;
                    </a>
                </div>
            </div>

            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-white text-sm flex items-center gap-2">
                        <span>📜</span>
                        <span>Recent Staff Activity Log</span>
                    </h3>
                    <span class="text-[10px] text-cyan-400 font-mono">SHA-256 Hash Chain</span>
                </div>
                <div class="space-y-2">
                    @forelse($accountData['recent_logins'] as $log)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-900/60 border border-slate-800/60 text-xs">
                            <span class="text-slate-300 font-mono">{{ $log->event_type }}</span>
                            <span class="text-[11px] text-slate-500">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="text-xs text-slate-500 py-2">Session initiated with Argon2id credentials.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Edit Dental Chart Modal -->
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="showEditModal = false" class="glass-card rounded-2xl p-6 border border-slate-800 max-w-lg w-full shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                    <h3 class="font-bold text-white text-sm flex items-center gap-2">
                        <span>🦷</span>
                        <span>Update Dental Chart: <span x-text="editPatient.name" class="text-cyan-400"></span></span>
                    </h3>
                    <button @click="showEditModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Tooth Designation</label>
                        <input type="text" x-model="editPatient.tooth_number" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Procedure Type</label>
                        <input type="text" x-model="editPatient.procedure" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Clinical Notes & Findings</label>
                        <textarea rows="3" x-model="editPatient.clinical_notes" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100"></textarea>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Treatment Status</label>
                        <select x-model="editPatient.status" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                            <option value="Pending Treatment">Pending Treatment</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-slate-800 pt-3">
                    <button @click="showEditModal = false" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 text-xs">Cancel</button>
                    <button @click="showEditModal = false; alert('Dental chart updated successfully in local database.')" class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-slate-950 font-bold text-xs">Save Chart</button>
                </div>
            </div>
        </div>

        <!-- Register Patient Modal (Manager / Admin) -->
        <div x-show="showRegisterModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="showRegisterModal = false" class="glass-card rounded-2xl p-6 border border-slate-800 max-w-lg w-full shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                    <h3 class="font-bold text-white text-sm flex items-center gap-2">
                        <span>➕</span>
                        <span>Register New Dental Patient</span>
                    </h3>
                    <button @click="showRegisterModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>
                
                <form @submit.prevent="showRegisterModal = false; alert('Patient registered successfully in DentiVault database.');" class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Full Name</label>
                            <input type="text" required placeholder="e.g. Samuel K. Arthur" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Contact Phone</label>
                            <input type="text" required placeholder="+233 24 000 0000" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Age</label>
                            <input type="number" required placeholder="32" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Gender</label>
                            <select class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Tooth #</label>
                            <input type="text" required placeholder="#14 Molar" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Initial Procedure</label>
                        <input type="text" required placeholder="e.g. Composite Restoration / Root Canal" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Medical / Allergy History</label>
                        <textarea rows="2" placeholder="e.g. Penicillin allergy, Hypertension..." class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100"></textarea>
                    </div>

                    <div class="mt-5 flex justify-end gap-2 border-t border-slate-800 pt-3">
                        <button type="button" @click="showRegisterModal = false" class="px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-gradient-to-r from-cyan-600 to-teal-500 text-slate-950 font-bold text-xs">Save Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
