<x-app-layout>
    <x-slot name="title">
        Security Auditor Forensics
    </x-slot>

    <div class="space-y-6 pt-6" x-data="{
        verifying: false,
        verifiedStatus: null,
        verifyChain() {
            this.verifying = true;
            fetch('{{ route('audit.verify-integrity') }}')
                .then(r => r.json())
                .then(data => {
                    this.verifying = false;
                    this.verifiedStatus = data;
                })
                .catch(err => {
                    this.verifying = false;
                    this.verifiedStatus = { status: 'error', message: 'Verification query failed' };
                });
        }
    }">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                    <span>Security Auditor Forensics Hub</span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Cryptographic SHA-256 hash chaining, anti-tamper anomaly alerts, and certified forensic audit reports.
                </p>
            </div>
            <div>
                <button @click="verifyChain()" :disabled="verifying" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 hover:from-amber-500 hover:to-orange-400 text-xs font-bold text-slate-950 transition-all shadow-lg shadow-amber-950/40 flex items-center gap-2">
                    <svg x-show="!verifying" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span x-text="verifying ? 'Verifying SHA-256 Hashes...' : 'Verify Cryptographic Chain'"></span>
                </button>
            </div>
        </div>

        <!-- Real-Time Cryptographic Chain Verification Result Banner -->
        <div x-show="verifiedStatus" x-cloak class="p-4 rounded-2xl border transition-all" :class="verifiedStatus && verifiedStatus.status === 'VERIFIED' ? 'bg-emerald-950/80 border-emerald-500/40 text-emerald-300' : 'bg-red-950/80 border-red-500/40 text-red-300'">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="font-bold text-sm" x-text="verifiedStatus ? verifiedStatus.message : ''"></h4>
                        <p class="text-xs opacity-90" x-text="verifiedStatus ? `Validated ${verifiedStatus.verified_records} total cryptographic ledger blocks against genesis root.` : ''"></p>
                    </div>
                </div>
                <button @click="verifiedStatus = null" class="text-xs opacity-75 hover:opacity-100 font-bold px-2 py-1">✕</button>
            </div>
        </div>

        <!-- Threat & Intrusion Activity (Security Events) -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Threat Detection & Intercepted Attack Vectors</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-2.5 px-3">Attack Type</th>
                            <th class="py-2.5 px-3">Severity</th>
                            <th class="py-2.5 px-3">Source IP</th>
                            <th class="py-2.5 px-3">Description</th>
                            <th class="py-2.5 px-3">Action Taken</th>
                            <th class="py-2.5 px-3">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                        @forelse($securityEvents as $event)
                            <tr class="hover:bg-slate-900/50 transition-colors">
                                <td class="py-2.5 px-3 font-bold text-red-400">{{ $event->event_type }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $event->severity === 'critical' ? 'bg-red-950 text-red-300 border border-red-500/40' : 'bg-amber-950 text-amber-300 border border-amber-500/40' }}">
                                        {{ $event->severity }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-400">{{ $event->ip_address }}</td>
                                <td class="py-2.5 px-3 font-sans text-slate-200">{{ $event->description }}</td>
                                <td class="py-2.5 px-3 text-emerald-400 font-semibold font-sans">Blocked (HTTP 403)</td>
                                <td class="py-2.5 px-3 text-slate-400">{{ $event->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-slate-500 font-sans">
                                    No active threat events detected. System defense operating normally.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Master Immutable Audit Trail -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Immutable Append-Only Audit Trail (SHA-256 Chained)</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-3">ID</th>
                            <th class="py-3 px-3">Event Type</th>
                            <th class="py-3 px-3">User</th>
                            <th class="py-3 px-3">Description</th>
                            <th class="py-3 px-3">IP Address</th>
                            <th class="py-3 px-3">Timestamp (UTC)</th>
                            <th class="py-3 px-3">Cryptographic Hash</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                        @foreach($logs as $log)
                            <tr class="hover:bg-slate-900/50 transition-colors">
                                <td class="py-3 px-3 text-slate-500 font-bold">{{ $log->id }}</td>
                                <td class="py-3 px-3 font-bold text-amber-400">{{ $log->event_type }}</td>
                                <td class="py-3 px-3 font-sans text-slate-300">{{ $log->user?->email ?? 'SYSTEM' }}</td>
                                <td class="py-3 px-3 font-sans text-slate-200">{{ $log->description }}</td>
                                <td class="py-3 px-3 text-slate-400">{{ $log->ip_address }}</td>
                                <td class="py-3 px-3 text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="py-3 px-3 text-slate-500 text-[10px]" title="{{ $log->log_hash }}">{{ substr($log->log_hash, 0, 16) }}...</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-800">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
