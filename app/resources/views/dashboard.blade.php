<x-app-layout>
    <x-slot name="title">
        Banking Overview
    </x-slot>

    <div class="space-y-6 pt-6">
        <!-- Welcome Banner & Security Status -->
        <div class="glass-card p-6 sm:p-8 rounded-3xl border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="space-y-2 relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-slate-900 border border-slate-700 text-slate-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Encrypted PostgreSQL Session • Verified</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                    Welcome back, {{ $user->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-400">
                    Account: <span class="font-mono text-emerald-400 font-bold">{{ $accountData['account_number'] }}</span> • {{ $accountData['account_type'] }}
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 relative z-10">
                <a href="{{ route('two-factor.show') }}" class="px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 hover:border-emerald-500/40 text-xs font-bold text-slate-200 hover:text-emerald-400 transition-all flex items-center justify-center gap-2 shadow-sm">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ $user->two_factor_confirmed_at ? 'Manage 2FA Shield' : 'Activate 2FA (Recommended)' }}</span>
                </a>
            </div>
        </div>

        <!-- Metric Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Balance Card -->
            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Available Balance</span>
                <span class="text-2xl font-black text-white mt-1 block font-mono">{{ $accountData['available_balance'] }}</span>
                <div class="flex items-center gap-1.5 mt-2 text-[11px] text-emerald-400 font-semibold">
                    <span>↑ 4.85% APY</span>
                    <span class="text-slate-500 font-normal">FDIC Insured</span>
                </div>
            </div>

            <!-- Security Tier Card -->
            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Security Level</span>
                <span class="text-sm font-bold {{ $user->two_factor_confirmed_at ? 'text-emerald-400' : 'text-amber-400' }} mt-1 block">
                    {{ $accountData['security_level'] }}
                </span>
                <span class="text-[11px] text-slate-500 mt-2 block">Argon2id + HMAC Pepper v1</span>
            </div>

            <!-- Primary Role Card -->
            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">RBAC Authorization</span>
                <span class="text-sm font-bold text-purple-300 mt-1 block">
                    {{ $user->roles->pluck('name')->join(', ') ?: 'Customer' }}
                </span>
                <span class="text-[11px] text-slate-500 mt-2 block">Strict Role-Based Gates</span>
            </div>

            <!-- Lockout Defense Status -->
            <div class="stat-card p-5 rounded-2xl">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Account Health</span>
                <span class="text-sm font-bold text-emerald-400 mt-1 block">Good Standing (0 Failures)</span>
                <span class="text-[11px] text-slate-500 mt-2 block">5-Attempt Lockout Defense Active</span>
            </div>
        </div>

        <!-- Recent Security & Authentication Activity -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Your Recent Authentications (Cryptographic Audit Trail)</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-2.5 px-3">Event Type</th>
                            <th class="py-2.5 px-3">Description</th>
                            <th class="py-2.5 px-3">IP Address</th>
                            <th class="py-2.5 px-3">Timestamp (UTC)</th>
                            <th class="py-2.5 px-3">Forensic Hash</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                        @forelse($accountData['recent_logins'] as $log)
                            <tr class="hover:bg-slate-900/50 transition-colors">
                                <td class="py-2.5 px-3 font-bold text-emerald-400">{{ $log->event_type }}</td>
                                <td class="py-2.5 px-3 font-sans text-slate-200">{{ $log->description }}</td>
                                <td class="py-2.5 px-3 text-slate-400">{{ $log->ip_address }}</td>
                                <td class="py-2.5 px-3 text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="py-2.5 px-3 text-slate-500 font-mono text-[10px]">{{ substr($log->log_hash, 0, 16) }}...</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500 font-sans">
                                    No prior logins recorded yet for this session.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
