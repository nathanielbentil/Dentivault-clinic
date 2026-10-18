<x-guest-layout>
    <x-slot name="heading">
        Hospital Staff & Clinical Portal
    </x-slot>
    <x-slot name="subheading">
        Enterprise Role-Based Access Control & HIPAA Encrypted Authentication
    </x-slot>

    @php
        // Default passwords dictionary for quick testing
        $passwords = [
            'admin@medivault.cloud' => 'Admin@MediVault2026!',
            'manager@medivault.cloud' => 'Manager@MediVault2026!',
            'doctor@medivault.cloud' => 'Doctor@MediVault2026!',
            'doctor.kofi@medivault.cloud' => 'Doctor@Kofi2026!',
            'doctor.akua@medivault.cloud' => 'Doctor@Akua2026!',
            'nurse@medivault.cloud' => 'Nurse@MediVault2026!',
            'nurse.abena@medivault.cloud' => 'Nurse@Abena2026!',
            'nurse.comfort@medivault.cloud' => 'Nurse@Comfort2026!',
            'nurse.dorothy@medivault.cloud' => 'Nurse@Dorothy2026!',
            'nurse.emmanuel@medivault.cloud' => 'Nurse@Emmanuel2026!',
            'nurse.florence@medivault.cloud' => 'Nurse@Florence2026!',
            'pharmacist@medivault.cloud' => 'Pharm@MediVault2026!',
            'pharm.adwoa@medivault.cloud' => 'Pharm@Adwoa2026!',
            'auditor@medivault.cloud' => 'Auditor@MediVault2026!',
            'auditor.sarah@medivault.cloud' => 'Auditor@Sarah2026!',
            'archived.staff@medivault.cloud' => 'Archived@Pass2026!',
            'evelyn.asante@medivault.cloud' => 'Nurse@Archive2026!',
            'david.quaye@medivault.cloud' => 'Pharm@Archive2026!',
            'marcus.obeng@medivault.cloud' => 'Doctor@Archive2026!',
            'grace.taylor@medivault.cloud' => 'Auditor@Archive2026!',
        ];

        // Ensure activeStaff collection exists
        $staffList = isset($activeStaff) ? $activeStaff : \App\Models\User::whereNull('deleted_at')->where('status', 'active')->get();
        
        $staffData = $staffList->map(function($s) use ($passwords) {
            $roleIcon = match($s->role) {
                'Admin' => '👑',
                'Manager' => '📋',
                'Doctor' => '🩺',
                'Nurse' => '💉',
                'Pharmacist' => '💊',
                'Auditor' => '🔍',
                default => '👤'
            };
            return [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'role' => $s->role,
                'icon' => $roleIcon,
                'password' => $passwords[$s->email] ?? 'Hospital@Pass2026!',
            ];
        })->values();
    @endphp

    <div class="space-y-6" x-data="{
        staffMembers: {{ Js::from($staffData) }},
        selectedCategory: 'all',
        showDropdown: false,
        searchTerm: '',
        userType: 'Admin',
        selectedName: 'Dr. Prof. Nathaniel Bentil',
        email: 'admin@medivault.cloud',
        password: 'Admin@MediVault2026!',
        showReport: false,

        selectStaff(member) {
            this.email = member.email;
            this.password = member.password;
            this.userType = member.role;
            this.selectedName = member.name;
            this.showDropdown = false;
        },

        filteredStaff() {
            return this.staffMembers.filter(m => {
                const matchesCategory = 
                    this.selectedCategory === 'all' || 
                    (this.selectedCategory === 'nurse' && m.role === 'Nurse') ||
                    (this.selectedCategory === 'doctor' && m.role === 'Doctor') ||
                    (this.selectedCategory === 'leadership' && (m.role === 'Admin' || m.role === 'Manager')) ||
                    (this.selectedCategory === 'other' && (m.role === 'Pharmacist' || m.role === 'Auditor'));
                
                const matchesSearch = 
                    this.searchTerm === '' ||
                    m.name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                    m.email.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                    m.role.toLowerCase().includes(this.searchTerm.toLowerCase());

                return matchesCategory && matchesSearch;
            });
        }
    }">

        <!-- Quick Filter Role Tabs -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-bold text-cyan-400 uppercase tracking-wider">
                    Quick-Fill Personnel Selection:
                </label>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-950 text-cyan-300 border border-cyan-800 font-mono">
                    <span x-text="staffMembers.length"></span> Active Staff (PostgreSQL)
                </span>
            </div>

            <!-- Category Pills -->
            <div class="flex flex-wrap gap-1.5 mb-3">
                <button type="button" @click="selectedCategory = 'all'"
                    :class="selectedCategory === 'all' ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
                    class="px-2.5 py-1 rounded-lg text-xs transition-all">
                    All Staff (<span x-text="staffMembers.length"></span>)
                </button>
                <button type="button" @click="selectedCategory = 'nurse'"
                    :class="selectedCategory === 'nurse' ? 'bg-pink-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
                    class="px-2.5 py-1 rounded-lg text-xs transition-all flex items-center gap-1">
                    <span>💉</span>
                    <span>Nurses (<span x-text="staffMembers.filter(m => m.role === 'Nurse').length"></span>)</span>
                </button>
                <button type="button" @click="selectedCategory = 'doctor'"
                    :class="selectedCategory === 'doctor' ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
                    class="px-2.5 py-1 rounded-lg text-xs transition-all flex items-center gap-1">
                    <span>🩺</span>
                    <span>Doctors (<span x-text="staffMembers.filter(m => m.role === 'Doctor').length"></span>)</span>
                </button>
                <button type="button" @click="selectedCategory = 'leadership'"
                    :class="selectedCategory === 'leadership' ? 'bg-purple-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
                    class="px-2.5 py-1 rounded-lg text-xs transition-all flex items-center gap-1">
                    <span>👑</span>
                    <span>Admin & MD (Managing Director)</span>
                </button>
                <button type="button" @click="selectedCategory = 'other'"
                    :class="selectedCategory === 'other' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
                    class="px-2.5 py-1 rounded-lg text-xs transition-all flex items-center gap-1">
                    <span>💊</span>
                    <span>Pharmacy & Audit (4)</span>
                </button>
            </div>
        </div>

        <!-- Selected Personnel Summary Card -->
        <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-cyan-500/20 to-purple-500/20 border border-cyan-500/40 flex items-center justify-center text-lg">
                    <span x-text="userType === 'Admin' ? '👑' : (userType === 'Manager' ? '📋' : (userType === 'Doctor' ? '🩺' : (userType === 'Nurse' ? '💉' : (userType === 'Pharmacist' ? '💊' : '🔍'))))"></span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white text-xs" x-text="selectedName"></span>
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded uppercase"
                            :class="userType === 'Admin' ? 'bg-purple-950 text-purple-300 border border-purple-800' :
                                    (userType === 'Manager' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' :
                                    (userType === 'Doctor' ? 'bg-cyan-950 text-cyan-300 border border-cyan-800' :
                                    (userType === 'Nurse' ? 'bg-pink-950 text-pink-300 border border-pink-800' :
                                    (userType === 'Pharmacist' ? 'bg-amber-950 text-amber-300 border border-amber-800' : 'bg-blue-950 text-blue-300 border border-blue-800'))))"
                            x-text="userType">
                        </span>
                    </div>
                    <span class="text-slate-400 font-mono text-[11px]" x-text="email"></span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-right">
                <span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-300 border border-emerald-500/30 font-mono text-[11px]">
                    2FA Code: 123456
                </span>
            </div>
        </div>

        <!-- Login Form -->
        <form method="POST" action="/login" class="space-y-4 relative" @click.away="showDropdown = false">
            @csrf

            <!-- Email Address with Interactive Staff Dropdown -->
            <div class="relative">
                <div class="flex items-center justify-between mb-1">
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                        Hospital Staff Username / Email (Click to Select):
                    </label>
                    <button type="button" @click="showDropdown = !showDropdown" class="text-xs text-cyan-400 hover:text-cyan-300 flex items-center gap-1 font-semibold">
                        <span>Choose Staff</span>
                        <span x-text="showDropdown ? '▲' : '▼'"></span>
                    </button>
                </div>

                <!-- Input with Click to Open Dropdown -->
                <div class="relative">
                    <input id="email" type="email" name="email" x-model="email" required autofocus
                        @focus="showDropdown = true"
                        placeholder="Click here to select a staff member or enter email..."
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/90 pl-10 pr-10 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <span>👤</span>
                    </div>
                    <button type="button" @click="showDropdown = !showDropdown" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-cyan-300">
                        <span class="text-xs">▼</span>
                    </button>
                </div>

                <!-- Interactive Staff Dropdown Menu -->
                <div x-show="showDropdown" x-transition 
                    class="absolute z-50 left-0 right-0 mt-1 rounded-2xl border border-slate-700 bg-slate-950/95 shadow-2xl backdrop-blur-xl p-2.5 space-y-2 max-h-72 overflow-y-auto">
                    
                    <!-- Search input inside dropdown -->
                    <div class="relative mb-2">
                        <input type="text" x-model="searchTerm" placeholder="Filter staff by name or role..."
                            class="w-full rounded-lg border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:border-cyan-500 focus:outline-none">
                    </div>

                    <!-- Staff items list -->
                    <div class="divide-y divide-slate-800/60">
                        <template x-for="m in filteredStaff()" :key="m.id">
                            <div @click="selectStaff(m)"
                                class="p-2 rounded-xl hover:bg-slate-900/90 cursor-pointer transition-all flex items-center justify-between group">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-base" x-text="m.icon"></span>
                                    <div>
                                        <div class="font-bold text-white text-xs group-hover:text-cyan-300 flex items-center gap-2">
                                            <span x-text="m.name"></span>
                                            <span class="text-[9px] px-1.5 py-0.2 rounded font-mono"
                                                :class="m.role === 'Nurse' ? 'bg-pink-950 text-pink-300 border border-pink-800' :
                                                        (m.role === 'Doctor' ? 'bg-cyan-950 text-cyan-300 border border-cyan-800' :
                                                        (m.role === 'Admin' ? 'bg-purple-950 text-purple-300 border border-purple-800' :
                                                        (m.role === 'Manager' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' :
                                                        (m.role === 'Pharmacist' ? 'bg-amber-950 text-amber-300 border border-amber-800' : 'bg-blue-950 text-blue-300 border border-blue-800'))))"
                                                x-text="m.role">
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono" x-text="m.email"></div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-mono text-emerald-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">
                                        Auto-Fill ↵
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="filteredStaff().length === 0" class="text-center py-4 text-xs text-slate-500">
                        No active staff found matching your search.
                    </div>
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                    Password (Argon2id + Salt + Pepper)
                </label>
                <div class="relative">
                    <input id="password" type="password" name="password" x-model="password" required
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/90 pl-10 pr-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <span>🔒</span>
                    </div>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-200">
                    <input type="checkbox" name="remember" checked class="rounded border-slate-700 bg-slate-900 text-cyan-600 focus:ring-cyan-500/20">
                    <span>Remember workstation</span>
                </label>
                <span class="text-cyan-400 font-medium">Session: Encrypted Cookie</span>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 hover:from-cyan-500 hover:to-teal-400 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span>Sign In to MediVault Portal</span>
                <span>&rarr;</span>
            </button>
        </form>

        <!-- Academic Audit Report Download & Printable Link -->
        <div class="p-3.5 rounded-xl bg-gradient-to-r from-purple-950/40 to-slate-900/80 border border-purple-500/30 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="text-xl">📄</span>
                <div>
                    <div class="font-bold text-white">Official Information Security Defense Report</div>
                    <div class="text-[10px] text-purple-300">Complete architectural proof covering all 10 Teacher Requirements</div>
                </div>
            </div>
            <a href="{{ route('security.report.pdf') }}" 
                class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs transition-all shadow-md flex items-center gap-1">
                <span>Download PDF</span>
                <span>📥</span>
            </a>
        </div>

        <!-- System Credentials & Personnel Reference Accordion -->
        <div class="mt-6 pt-4 border-t border-slate-800">
            <div class="flex items-center justify-between cursor-pointer" @click="showReport = !showReport">
                <div class="flex items-center gap-2">
                    <span class="text-sm">📋</span>
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">
                        Full Active Staff Credentials Table (15 Users)
                    </span>
                </div>
                <span class="text-xs text-cyan-400 font-semibold" x-text="showReport ? 'Hide ▲' : 'Show All Credentials ▼'"></span>
            </div>

            <div x-show="showReport" class="mt-3 space-y-3">
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-left text-[11px] text-slate-300">
                        <thead class="text-[10px] text-slate-400 uppercase bg-slate-900/80 border-b border-slate-800 font-bold sticky top-0">
                            <tr>
                                <th class="p-2">Role</th>
                                <th class="p-2">Staff Member</th>
                                <th class="p-2">Login Email</th>
                                <th class="p-2">Plain Password</th>
                                <th class="p-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <template x-for="m in staffMembers" :key="m.id">
                                <tr class="hover:bg-slate-900/40">
                                    <td class="p-2 font-bold"
                                        :class="m.role === 'Admin' ? 'text-purple-400' :
                                                (m.role === 'Manager' ? 'text-emerald-400' :
                                                (m.role === 'Doctor' ? 'text-cyan-400' :
                                                (m.role === 'Nurse' ? 'text-pink-400' :
                                                (m.role === 'Pharmacist' ? 'text-amber-400' : 'text-blue-400'))))"
                                        x-text="m.role">
                                    </td>
                                    <td class="p-2 text-white font-sans font-medium" x-text="m.name"></td>
                                    <td class="p-2 text-cyan-300" x-text="m.email"></td>
                                    <td class="p-2 text-emerald-400" x-text="m.password"></td>
                                    <td class="p-2">
                                        <button type="button" @click="selectStaff(m)"
                                            class="px-2 py-0.5 rounded bg-cyan-900 text-cyan-200 hover:bg-cyan-700 text-[10px]">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</x-guest-layout>
