<x-app-layout>
    <x-slot name="title">
        SuperAdmin Zone
    </x-slot>

    <div class="space-y-6 pt-6" x-data="{
        selectedUser: null,
        showRoleModal: false,
        openRoleModal(user) {
            this.selectedUser = user;
            this.showRoleModal = true;
        }
    }">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-purple-500"></span>
                    <span>SuperAdmin Command Center</span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    System-wide user lifecycle administration, RBAC policy enforcement, and security controls.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.backups') }}" class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-700 hover:border-cyan-500/50 text-xs font-bold text-cyan-300 transition-all flex items-center gap-2">
                    <svg class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z" />
                    </svg>
                    <span>3-2-1 Backup & DR Hub</span>
                </a>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Registered Users</span>
                <span class="text-3xl font-black text-white mt-1 block font-mono">{{ $stats['total_users'] }}</span>
                <span class="text-[11px] text-purple-400 font-semibold mt-2 block">PostgreSQL Supabase Managed</span>
            </div>

            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Active Accounts</span>
                <span class="text-3xl font-black text-emerald-400 mt-1 block font-mono">{{ $stats['active_users'] }}</span>
                <span class="text-[11px] text-slate-500 mt-2 block">100% Argon2id Secured</span>
            </div>

            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Locked Accounts (Defense)</span>
                <span class="text-3xl font-black {{ $stats['locked_users'] > 0 ? 'text-red-400 animate-pulse' : 'text-slate-200' }} mt-1 block font-mono">
                    {{ $stats['locked_users'] }}
                </span>
                <span class="text-[11px] text-slate-500 mt-2 block">Sliding Lockout Active</span>
            </div>

            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Immutable Audit Records</span>
                <span class="text-3xl font-black text-amber-400 mt-1 block font-mono">{{ $stats['audit_events_count'] }}</span>
                <span class="text-[11px] text-amber-400/80 font-semibold mt-2 block">SHA-256 Chained Blocks</span>
            </div>
        </div>

        <!-- Users Management Table -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>User Accounts & Tiered RBAC Assignments</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-3">User</th>
                            <th class="py-3 px-3">Assigned Role</th>
                            <th class="py-3 px-3">Account Status</th>
                            <th class="py-3 px-3">2FA Status</th>
                            <th class="py-3 px-3">Created Date</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @foreach($users as $u)
                            <tr class="hover:bg-slate-900/50 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white">{{ $u->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $u->email }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    @foreach($u->roles as $r)
                                        @php
                                            $badgeClass = match($r->name) {
                                                'SuperAdmin' => 'bg-purple-950 text-purple-300 border-purple-500/40',
                                                'SecurityAuditor' => 'bg-amber-950 text-amber-300 border-amber-500/40',
                                                'BranchManager' => 'bg-blue-950 text-blue-300 border-blue-500/40',
                                                default => 'bg-emerald-950 text-emerald-300 border-emerald-500/40',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold border {{ $badgeClass }}">
                                            {{ $r->name }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="py-3 px-3">
                                    @if($u->status === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950 text-emerald-400 border border-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-950 text-red-400 border border-red-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-400"></span> Locked
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    @if($u->two_factor_confirmed_at)
                                        <span class="text-emerald-400 font-semibold flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Enabled</span>
                                        </span>
                                    @else
                                        <span class="text-slate-500">Disabled</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-400">{{ $u->created_at->format('M d, Y') }}</td>
                                <td class="py-3 px-3 text-right">
                                    <button @click="openRoleModal({{ json_encode(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role_id' => $u->roles->first()?->id]) }})" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-purple-900/60 hover:text-purple-200 border border-slate-700 text-slate-200 transition-colors font-medium text-[11px]">
                                        Modify Role
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pt-3 border-t border-slate-800">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Role Assignment Modal -->
        <div x-show="showRoleModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.outside="showRoleModal = false" class="glass-card max-w-md w-full p-6 rounded-2xl border border-purple-500/30 space-y-4 shadow-2xl">
                <div class="flex items-center gap-3 text-purple-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h3 class="text-base font-bold text-white">Assign Role to User</h3>
                </div>
                
                <p class="text-xs text-slate-300">
                    Modifying role for <span class="font-bold text-white" x-text="selectedUser ? selectedUser.name : ''"></span> (<span class="font-mono text-purple-300" x-text="selectedUser ? selectedUser.email : ''"></span>). This action is recorded in the tamper-evident audit log.
                </p>

                <form :action="selectedUser ? `/admin/users/${selectedUser.id}/assign-role` : '#'" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="role_id" class="block text-xs font-semibold text-slate-300 uppercase mb-1">
                            Select Tiered Role
                        </label>
                        <select name="role_id" id="role_id" required class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-slate-100 focus:border-purple-500 focus:outline-none">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" :selected="selectedUser && selectedUser.role_id == {{ $role->id }}">
                                    {{ $role->name }} — {{ $role->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showRoleModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-xs font-bold text-white transition-colors shadow-lg shadow-purple-900/40">
                            Save Role Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
