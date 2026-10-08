<x-app-layout>
    <x-slot name="title">
        2FA Security Center
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6 pt-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                    <svg class="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Two-Factor Authentication (RFC 6238 TOTP)</span>
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    Enforce time-based one-time password security for all sensitive operations and sessions.
                </p>
            </div>
            <div>
                @if($enabled)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 shadow-lg shadow-emerald-950/50">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        PROTECTED (ACTIVE)
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-950/80 border border-amber-500/40 text-amber-400">
                        <span class="h-2 w-2 rounded-full bg-amber-400 animate-ping"></span>
                        UNPROTECTED
                    </span>
                @endif
            </div>
        </div>

        @if(!$enabled && !$qrCodeSvg)
            <!-- State 1: 2FA Disabled -> Prompt to Enable -->
            <div class="glass-card p-8 rounded-2xl border border-slate-800 space-y-6">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-xl bg-emerald-950/80 border border-emerald-500/30 flex items-center justify-center shrink-0">
                        <svg class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white">Activate Hardware / Software TOTP Shield</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            When two-factor authentication is enabled, you will be prompted for a secure, 6-digit one-time token during authentication. Tokens are generated locally on your authenticator device (Google Authenticator, Microsoft Authenticator, Authy, or 1Password) with zero external network leakage.
                        </p>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-5">
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <button type="submit" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-6 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-400 transition-all flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Initialize 2FA Keypair</span>
                        </button>
                    </form>
                </div>
            </div>

        @elseif(!$enabled && $qrCodeSvg)
            <!-- State 2: Setup In Progress -> Show Offline SVG QR + Confirm Code -->
            <div class="glass-card p-8 rounded-2xl border border-slate-800 space-y-6">
                <div class="border-b border-slate-800 pb-4">
                    <span class="text-xs uppercase font-bold tracking-widest text-emerald-400 block mb-1">Step 1 of 2</span>
                    <h3 class="text-lg font-bold text-white">Scan QR Code with Authenticator App</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Rendered entirely offline via pure vector SVG — your secret is never transmitted to Google APIs or external servers.
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-8 justify-center py-4">
                    <!-- Vector SVG QR Code Container -->
                    <div class="p-4 bg-white rounded-2xl shadow-2xl border-4 border-slate-800 shrink-0">
                        {!! $qrCodeSvg !!}
                    </div>

                    <div class="space-y-4 max-w-md">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">
                                Manual Setup Secret Key:
                            </span>
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-700 font-mono text-xs text-emerald-400 tracking-wider break-all select-all flex items-center justify-between gap-2">
                                <span>{{ $secretKey }}</span>
                            </div>
                        </div>

                        <!-- Confirmation Form -->
                        <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="code" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                                    Step 2: Enter 6-Digit Verification Code
                                </label>
                                <input id="code" type="text" inputmode="numeric" name="code" required autofocus maxlength="6"
                                    class="w-full text-center tracking-[0.5em] text-xl font-mono font-bold rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-emerald-400 placeholder-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    placeholder="000000">
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button type="submit" class="w-full rounded-xl bg-emerald-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 transition-all flex items-center justify-center gap-2">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Verify & Enable 2FA</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        @else
            <!-- State 3: 2FA Active -> Recovery Codes Vault & Actions -->
            <div class="space-y-6" x-data="{ showRecoveryModal: false, showDisableModal: false }">
                <!-- Status Banner -->
                <div class="stat-card p-6 rounded-2xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-xl bg-emerald-950 border border-emerald-500/40 flex items-center justify-center text-emerald-400 shrink-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Two-Factor Authentication is Active</h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Confirmed on {{ Auth::user()->two_factor_confirmed_at?->format('M d, Y H:i:s UTC') ?? 'Active' }}. Your banking actions require TOTP signatures.
                            </p>
                        </div>
                    </div>
                    <button @click="showDisableModal = true" class="rounded-xl border border-red-500/40 bg-red-950/40 hover:bg-red-900/60 px-4 py-2 text-xs font-bold text-red-300 transition-colors">
                        Disable 2FA
                    </button>
                </div>

                <!-- Recovery Codes Vault -->
                <div class="glass-card p-6 rounded-2xl border border-slate-800 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                <span>Single-Use Emergency Recovery Codes</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                If you lose access to your authenticator app, each recovery code can be used exactly once.
                            </p>
                        </div>
                    </div>

                    @if(session('recovery_codes'))
                        <div class="p-4 rounded-xl bg-slate-900 border border-emerald-500/40 space-y-3">
                            <div class="flex items-center justify-between text-xs text-emerald-400 font-semibold">
                                <span>⚠️ Store these codes in a secure password vault:</span>
                                <button type="button" @click="navigator.clipboard.writeText('{{ implode('\n', session('recovery_codes')) }}')" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-[11px] text-white transition-colors">
                                    📋 Copy All Codes
                                </button>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-xs text-slate-200">
                                @foreach(session('recovery_codes') as $code)
                                    <div class="p-2 rounded bg-slate-950 border border-slate-800 text-center select-all">
                                        {{ $code }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-xs">
                            <span class="text-slate-400">8 single-use codes are hashed and securely stored on the server.</span>
                            <form method="POST" action="{{ route('two-factor.recovery-codes') }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5 font-semibold text-slate-200 transition-colors">
                                    Regenerate 8 New Codes
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <!-- Disable 2FA Modal -->
                <div x-show="showDisableModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                    <div @click.outside="showDisableModal = false" class="glass-card max-w-md w-full p-6 rounded-2xl border border-red-500/30 space-y-4 shadow-2xl">
                        <div class="flex items-center gap-3 text-red-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <h3 class="text-base font-bold text-white">Disable Two-Factor Shield</h3>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            Warning: Disabling 2FA reduces your account security level back to Tier 1. Confirm your master password to proceed.
                        </p>
                        <form method="POST" action="{{ route('two-factor.disable') }}" class="space-y-3">
                            @csrf
                            @method('DELETE')
                            <div>
                                <label for="disable_password" class="block text-xs font-semibold text-slate-300 uppercase mb-1">
                                    Master Password
                                </label>
                                <input id="disable_password" type="password" name="password" required
                                    class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-red-500 focus:outline-none"
                                    placeholder="••••••••••••">
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="showDisableModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-slate-200">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white transition-colors">
                                    Confirm & Disable
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
