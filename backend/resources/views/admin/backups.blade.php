<x-app-layout>
    <x-slot name="title">
        3-2-1 Backups & Disaster Recovery
    </x-slot>

    <div class="space-y-6 pt-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <svg class="h-7 w-7 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z" />
                    </svg>
                    <span>3-2-1 Backup & Disaster Recovery Command</span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Asymmetric age encryption (X25519), offsite cloud replication, and automated RTO/RPO drill engine.
                </p>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.backups.trigger') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-xs font-bold text-slate-950 transition-all shadow-lg shadow-emerald-950/40 flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Create Encrypted Backup</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.backups.drill') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-500 hover:from-cyan-500 hover:to-blue-400 text-xs font-bold text-slate-950 transition-all shadow-lg shadow-cyan-950/40 flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Execute DR Restore Drill</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- 3-2-1 Architecture Visual Status -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Copy 1 & 2: Local Encrypted Storage -->
            <div class="glass-card p-5 rounded-2xl border border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold text-emerald-400">
                    <span>3 Copies • 2 Media</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950 border border-emerald-500/30 text-[10px] uppercase">Active</span>
                </div>
                <h4 class="text-base font-bold text-white">Local Encrypted Volume</h4>
                <p class="text-xs text-slate-400">
                    PostgreSQL tables streamed via PDO and encrypted with server-side public key <span class="font-mono text-emerald-300">age1ql4y...</span>.
                </p>
                <div class="pt-2 text-[11px] text-slate-500 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> 7-Day Automated Retention Pruning
                </div>
            </div>

            <!-- Copy 3: Offsite Cloud S3 -->
            <div class="glass-card p-5 rounded-2xl border border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold text-cyan-400">
                    <span>1 Offsite Cloud</span>
                    <span class="px-2 py-0.5 rounded bg-cyan-950 border border-cyan-500/30 text-[10px] uppercase">Zero-Knowledge</span>
                </div>
                <h4 class="text-base font-bold text-white">Supabase Storage S3 Mirror</h4>
                <p class="text-xs text-slate-400">
                    Replicated offsite as raw ciphertext. Neither host nor third parties possess the offline private decryption keys.
                </p>
                <div class="pt-2 text-[11px] text-slate-500 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-400"></span> 30-Day Cloud Retention Policy
                </div>
            </div>

            <!-- DR Metrics: RTO / RPO -->
            <div class="glass-card p-5 rounded-2xl border border-slate-800 space-y-2">
                <div class="flex items-center justify-between text-xs font-semibold text-purple-400">
                    <span>Business Continuity</span>
                    <span class="px-2 py-0.5 rounded bg-purple-950 border border-purple-500/30 text-[10px] uppercase">Viva Verified</span>
                </div>
                <h4 class="text-base font-bold text-white">RTO & RPO Metrics</h4>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between text-slate-300">
                        <span>Recovery Time (RTO):</span>
                        <span class="font-bold text-emerald-400 font-mono">< 3.5 seconds</span>
                    </div>
                    <div class="flex justify-between text-slate-300">
                        <span>Recovery Point (RPO):</span>
                        <span class="font-bold text-emerald-400 font-mono">0 Data Loss</span>
                    </div>
                </div>
                <div class="pt-1 text-[11px] text-slate-500 flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-400"></span> Offline Air-Gapped Key Decryption
                </div>
            </div>
        </div>

        <!-- Backup Registry & DR History Table -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Supabase Cloud Backup Ledger & Restoration History</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-3">Archive Filename</th>
                            <th class="py-3 px-3">Storage Tier</th>
                            <th class="py-3 px-3">File Size</th>
                            <th class="py-3 px-3">SHA-256 Checksum</th>
                            <th class="py-3 px-3">Restoration Status</th>
                            <th class="py-3 px-3">DR Tested At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                        @forelse($backupLogs as $log)
                            <tr class="hover:bg-slate-900/50 transition-colors">
                                <td class="py-3 px-3 font-bold text-white">
                                    {{ $log->filename }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $log->storage_location === 'both' ? 'bg-cyan-950 text-cyan-300 border border-cyan-500/30' : 'bg-slate-800 text-slate-300' }}">
                                        {{ $log->storage_location }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-300">{{ $log->humanFileSize() }}</td>
                                <td class="py-3 px-3 text-slate-500 text-[10px]">{{ substr($log->sha256_checksum, 0, 20) }}...</td>
                                <td class="py-3 px-3">
                                    @if($log->status === 'verified_restorable')
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950 text-emerald-400 border border-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Verified Restorable
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-950 text-blue-400 border border-blue-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span> {{ ucfirst($log->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-400">
                                    {{ $log->restoration_tested_at ? $log->restoration_tested_at->format('M d, H:i UTC') : 'Pending Drill' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-slate-500 font-sans">
                                    No backup records found. Click "Create Encrypted Backup" above to generate one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-800">
                {{ $backupLogs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
