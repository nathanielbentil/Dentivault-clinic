<x-app-layout>
    <x-slot name="title">
        Branch Operations
    </x-slot>

    <div class="space-y-6 pt-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <span class="h-3 w-3 rounded-full bg-blue-500"></span>
                    <span>Branch Manager Customer Operations</span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Branch-level customer account verification, lockout resets, and customer identity operations.
                </p>
            </div>
        </div>

        <!-- Customer Accounts Management Table -->
        <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Registered Branch Customer Directory</span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider font-semibold">
                            <th class="py-3 px-3">Customer Name</th>
                            <th class="py-3 px-3">Email Address</th>
                            <th class="py-3 px-3">Failed Attempts</th>
                            <th class="py-3 px-3">Account Status</th>
                            <th class="py-3 px-3">Lockout Window</th>
                            <th class="py-3 px-3 text-right">Branch Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($customers as $c)
                            <tr class="hover:bg-slate-900/50 transition-colors">
                                <td class="py-3 px-3 font-bold text-white">{{ $c->name }}</td>
                                <td class="py-3 px-3 font-mono text-slate-400">{{ $c->email }}</td>
                                <td class="py-3 px-3">
                                    <span class="font-mono font-bold {{ $c->failed_login_attempts >= 5 ? 'text-red-400' : 'text-slate-300' }}">
                                        {{ $c->failed_login_attempts }} / 5
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    @if($c->status === 'locked' || ($c->lockout_until && $c->lockout_until->isFuture()))
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-950 text-red-400 border border-red-500/40">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-400 animate-pulse"></span> LOCKED (DEFENSE)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-950 text-emerald-400 border border-emerald-500/30">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Active
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-400">
                                    {{ $c->lockout_until ? $c->lockout_until->diffForHumans() : 'None' }}
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($c->status === 'locked' || ($c->lockout_until && $c->lockout_until->isFuture()) || $c->failed_login_attempts > 0)
                                        <form method="POST" action="{{ route('branch.customers.unlock', $c) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-slate-950 text-[11px] font-bold transition-colors shadow-sm">
                                                Verify & Unlock
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-500 text-[11px]">Normal</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-slate-500 font-sans">
                                    No customer records registered yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-800">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
