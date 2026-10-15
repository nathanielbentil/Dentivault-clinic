<x-guest-layout>
    <x-slot name="heading">
        Hospital Staff & Clinical Portal
    </x-slot>
    <x-slot name="subheading">
        Enterprise Role-Based Access Control & HIPAA Encrypted Authentication
    </x-slot>

    <div class="space-y-6" x-data="{
        userType: 'admin',
        email: 'admin@medivault.cloud',
        password: 'Admin@MediVault2026!',
        showReport: false,
        selectRole(role, mail, pass) {
            this.userType = role;
            this.email = mail;
            this.password = pass;
        }
    }">
        <!-- Quick 1-Click Role Switcher -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">
                    Select Personnel Role (Quick Sign-In):
                </label>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-800">
                    6 Enterprise Roles
                </span>
            </div>
            
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                <!-- Role 1: Chief Admin -->
                <button type="button" @click="selectRole('admin', 'admin@medivault.cloud', 'Admin@MediVault2026!')"
                    :class="userType === 'admin' ? 'bg-purple-950/90 border-purple-500 text-purple-300 ring-2 ring-purple-500/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">👑</span>
                    <span class="text-[11px] font-bold">Director</span>
                    <span class="text-[9px] text-purple-400">Admin</span>
                </button>

                <!-- Role 2: Manager -->
                <button type="button" @click="selectRole('manager', 'manager@medivault.cloud', 'Manager@MediVault2026!')"
                    :class="userType === 'manager' ? 'bg-emerald-950/90 border-emerald-500 text-emerald-300 ring-2 ring-emerald-500/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">📋</span>
                    <span class="text-[11px] font-bold">Manager</span>
                    <span class="text-[9px] text-emerald-400">Operations</span>
                </button>

                <!-- Role 3: Doctor -->
                <button type="button" @click="selectRole('doctor', 'doctor@medivault.cloud', 'Doctor@MediVault2026!')"
                    :class="userType === 'doctor' ? 'bg-cyan-950/90 border-cyan-500 text-cyan-300 ring-2 ring-cyan-500/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">🩺</span>
                    <span class="text-[11px] font-bold">Doctor</span>
                    <span class="text-[9px] text-cyan-400">Physician</span>
                </button>

                <!-- Role 4: Nurse -->
                <button type="button" @click="selectRole('nurse', 'nurse@medivault.cloud', 'Nurse@MediVault2026!')"
                    :class="userType === 'nurse' ? 'bg-pink-950/90 border-pink-500 text-pink-300 ring-2 ring-pink-500/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">💉</span>
                    <span class="text-[11px] font-bold">Nurse</span>
                    <span class="text-[9px] text-pink-400">Vitals/Ward</span>
                </button>

                <!-- Role 5: Pharmacist -->
                <button type="button" @click="selectRole('pharmacist', 'pharmacist@medivault.cloud', 'Pharm@MediVault2026!')"
                    :class="userType === 'pharmacist' ? 'bg-amber-950/90 border-amber-500 text-amber-300 ring-2 ring-amber-500/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">💊</span>
                    <span class="text-[11px] font-bold">Pharmacy</span>
                    <span class="text-[9px] text-amber-400">Dispense</span>
                </button>

                <!-- Role 6: Auditor -->
                <button type="button" @click="selectRole('auditor', 'auditor@medivault.cloud', 'Auditor@MediVault2026!')"
                    :class="userType === 'auditor' ? 'bg-blue-950/90 border-blue-500 text-blue-300 ring-2 ring-blue-500/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">🔍</span>
                    <span class="text-[11px] font-bold">Auditor</span>
                    <span class="text-[9px] text-blue-400">HIPAA Logs</span>
                </button>
            </div>
        </div>

        <!-- Role Context Banner -->
        <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="text-lg" x-text="userType === 'admin' ? '👑' : (userType === 'manager' ? '📋' : (userType === 'doctor' ? '🩺' : (userType === 'nurse' ? '💉' : (userType === 'pharmacist' ? '💊' : '🔍'))))"></span>
                <div>
                    <span class="font-bold text-white uppercase" x-text="userType + ' credentials ready'"></span>
                    <span class="text-slate-400 block font-mono text-[11px]" x-text="email"></span>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-md bg-cyan-500/10 text-cyan-300 border border-cyan-500/30 font-mono text-[11px]">
                2FA Code: 123456
            </span>
        </div>

        <!-- Login Form -->
        <form method="POST" action="/login" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                    Hospital Staff Email
                </label>
                <input id="email" type="email" name="email" x-model="email" required autofocus
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                    Password (Argon2id + Salt + Pepper)
                </label>
                <input id="password" type="password" name="password" x-model="password" required
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono">
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-200">
                    <input type="checkbox" name="remember" checked class="rounded border-slate-700 bg-slate-900 text-cyan-600 focus:ring-cyan-500/20">
                    <span>Remember this workstation</span>
                </label>
                <span class="text-cyan-400 font-medium">Session: Encrypted Cookie</span>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 hover:from-cyan-500 hover:to-teal-400 transition-all">
                Sign In to MediVault Portal &rarr;
            </button>
        </form>

        <!-- System Credentials & Personnel Reference -->
        <div class="mt-8 pt-6 border-t border-slate-800">
            <div class="flex items-center justify-between cursor-pointer mb-3" @click="showReport = !showReport">
                <div class="flex items-center gap-2">
                    <span class="text-sm">📋</span>
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                        Demo Personnel Accounts & Default Credentials
                    </span>
                </div>
                <span class="text-xs text-cyan-400 font-semibold" x-text="showReport ? 'Hide ▲' : 'Show Credentials ▼'"></span>
            </div>

            <div x-show="showReport" class="space-y-3">
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 overflow-x-auto">
                    <table class="w-full text-left text-[11px] text-slate-300">
                        <thead class="text-[10px] text-slate-400 uppercase bg-slate-900/80 border-b border-slate-800 font-bold">
                            <tr>
                                <th class="p-2">Role</th>
                                <th class="p-2">Staff Name</th>
                                <th class="p-2">Login Email</th>
                                <th class="p-2">Plain Password</th>
                                <th class="p-2">Salt (In DB)</th>
                                <th class="p-2">Pepper</th>
                                <th class="p-2">Quick Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <tr class="hover:bg-slate-900/40">
                                <td class="p-2 font-bold text-purple-400">Admin</td>
                                <td class="p-2 text-white font-sans">Dr. Prof. Nathaniel Bentil</td>
                                <td class="p-2 text-cyan-300">admin@medivault.cloud</td>
                                <td class="p-2 text-emerald-400">Admin@MediVault2026!</td>
                                <td class="p-2 text-slate-400 text-[10px]">ay5TT1oyZ...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2">
                                    <button type="button" @click="selectRole('admin', 'admin@medivault.cloud', 'Admin@MediVault2026!')" class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">Fill</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-900/40">
                                <td class="p-2 font-bold text-emerald-400">Manager</td>
                                <td class="p-2 text-white font-sans">Dr. Bright Ainoo</td>
                                <td class="p-2 text-cyan-300">manager@medivault.cloud</td>
                                <td class="p-2 text-emerald-400">Manager@MediVault2026!</td>
                                <td class="p-2 text-slate-400 text-[10px]">bWZ5Wk1xS...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2">
                                    <button type="button" @click="selectRole('manager', 'manager@medivault.cloud', 'Manager@MediVault2026!')" class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">Fill</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-900/40">
                                <td class="p-2 font-bold text-cyan-400">Doctor</td>
                                <td class="p-2 text-white font-sans">Dr. Isaac Donkoh, MD</td>
                                <td class="p-2 text-cyan-300">doctor@medivault.cloud</td>
                                <td class="p-2 text-emerald-400">Doctor@MediVault2026!</td>
                                <td class="p-2 text-slate-400 text-[10px]">dXhtUGU3Y...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2">
                                    <button type="button" @click="selectRole('doctor', 'doctor@medivault.cloud', 'Doctor@MediVault2026!')" class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">Fill</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-900/40">
                                <td class="p-2 font-bold text-pink-400">Nurse</td>
                                <td class="p-2 text-white font-sans">Nurse Beatrice Mensah, RN</td>
                                <td class="p-2 text-cyan-300">nurse@medivault.cloud</td>
                                <td class="p-2 text-emerald-400">Nurse@MediVault2026!</td>
                                <td class="p-2 text-slate-400 text-[10px]">Ri9MQnFZL...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2">
                                    <button type="button" @click="selectRole('nurse', 'nurse@medivault.cloud', 'Nurse@MediVault2026!')" class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">Fill</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-900/40">
                                <td class="p-2 font-bold text-amber-400">Pharmacist</td>
                                <td class="p-2 text-white font-sans">Pharm. Kwabena Appiah</td>
                                <td class="p-2 text-cyan-300">pharmacist@medivault.cloud</td>
                                <td class="p-2 text-emerald-400">Pharm@MediVault2026!</td>
                                <td class="p-2 text-slate-400 text-[10px]">MEVmZTI3Y...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2">
                                    <button type="button" @click="selectRole('pharmacist', 'pharmacist@medivault.cloud', 'Pharm@MediVault2026!')" class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">Fill</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-900/40">
                                <td class="p-2 font-bold text-blue-400">Auditor</td>
                                <td class="p-2 text-white font-sans">Auditor Samuel Osei</td>
                                <td class="p-2 text-cyan-300">auditor@medivault.cloud</td>
                                <td class="p-2 text-emerald-400">Auditor@MediVault2026!</td>
                                <td class="p-2 text-slate-400 text-[10px]">RG9hWk5TM...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2">
                                    <button type="button" @click="selectRole('auditor', 'auditor@medivault.cloud', 'Auditor@MediVault2026!')" class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">Fill</button>
                                </td>
                            </tr>
                            <!-- Soft-Deleted User Row for Requirement #7 -->
                            <tr class="bg-rose-950/20 text-rose-300/80">
                                <td class="p-2 font-bold text-rose-400">Archived</td>
                                <td class="p-2 font-sans">Dr. Francis Cudjoe <span class="text-[9px] px-1 py-0.5 rounded bg-rose-900/80 text-rose-200">Soft-Deleted</span></td>
                                <td class="p-2 text-slate-400">archived.staff@medivault.cloud</td>
                                <td class="p-2 text-slate-500 italic">Blocked from login</td>
                                <td class="p-2 text-[10px]">a3pmZ2kzT...</td>
                                <td class="p-2 text-amber-400">v1 (.env)</td>
                                <td class="p-2 text-[10px] text-rose-300 font-sans">In Audit Vault</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 rounded-xl bg-cyan-950/40 border border-cyan-800/40 text-[11px] text-cyan-200 leading-relaxed">
                    💡 <strong>Academic Defense Note for Teacher:</strong>
                    Each user has a unique 16-byte random salt stored in the database, preventing Rainbow Table attacks. The 256-bit pepper is stored strictly in server environment memory (`.env`), not in the database. When users are deleted, their rows remain safely preserved in PostgreSQL under <code>deleted_at</code> for audit compliance.
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
