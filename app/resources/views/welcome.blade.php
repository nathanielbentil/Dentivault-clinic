<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SecureAuth - Bank-Grade Authentication & 3-2-1 Disaster Recovery System</title>
    
    <!-- Tailwind CSS CDN & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bank: {
                            500: '#22c55e',
                            600: '#16a34a',
                            950: '#052e16',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-full font-sans antialiased selection:bg-emerald-500 selection:text-slate-950 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(16,185,129,0.2),rgba(255,255,255,0))]">
    
    <!-- Top Navigation -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/20">
                        <div class="h-full w-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                            <svg class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight text-white block">Secure<span class="text-emerald-400">Auth</span></span>
                        <span class="text-[10px] uppercase font-bold tracking-widest text-emerald-500 block">Core Banking Shield</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:from-emerald-500 hover:to-teal-400 transition-all">
                            Enter Banking Dashboard →
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-2.5 text-xs font-bold text-slate-200 hover:border-emerald-500/40 hover:text-emerald-400 transition-all">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-400 transition-all">
                            Open Account
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 sm:py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center space-y-8">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 shadow-xl shadow-emerald-950/50">
            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>PostgreSQL 17.11 (Supabase Cloud) • Argon2id (64MB) • age X25519</span>
        </div>

        <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white max-w-4xl mx-auto leading-tight sm:leading-tight">
            Bank-Grade Authentication & <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">3-2-1 Disaster Recovery</span>
        </h1>

        <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
            Enterprise multi-tenant authentication, 4-tier Role-Based Access Control, RFC 6238 TOTP two-factor security, cryptographic append-only audit trails, and automated ransomware-proof backups.
        </p>

        <!-- CTA Action Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
            <a href="{{ route('login') }}" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-8 py-4 text-sm font-bold text-slate-950 shadow-xl shadow-emerald-500/25 hover:from-emerald-500 hover:to-teal-400 hover:scale-[1.02] transition-all flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <span>Explore Live Banking Portal</span>
            </a>
            <a href="{{ route('register') }}" class="rounded-xl border border-slate-700 bg-slate-900/90 px-8 py-4 text-sm font-bold text-slate-200 hover:border-emerald-500/40 hover:text-emerald-400 hover:scale-[1.02] transition-all">
                Test NIST SP 800-63B Registration
            </a>
        </div>
    </section>

    <!-- Architecture Pillars Grid (3x3) -->
    <section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-white">Defense-in-Depth Core Architecture</h2>
            <p class="text-sm text-slate-400 mt-2">Verified against OWASP Top 10 and STRIDE threat modeling.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 1. Password Security -->
            <div class="glass-card p-6 rounded-3xl space-y-3 hover:border-emerald-500/30 transition-all">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">Argon2id + HMAC Pepper</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    64MB memory cost, 4 iterations, dual threads (~300ms defense latency). Server-side HMAC pepper rotated with zero downtime.
                </p>
            </div>

            <!-- 2. Two-Factor Authentication -->
            <div class="glass-card p-6 rounded-3xl space-y-3 hover:border-emerald-500/30 transition-all">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">RFC 6238 Offline TOTP</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Pure vector SVG QR generation without external API dependencies. 8 single-use hashed emergency recovery codes.
                </p>
            </div>

            <!-- 3. 4-Tier RBAC -->
            <div class="glass-card p-6 rounded-3xl space-y-3 hover:border-emerald-500/30 transition-all">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">Strict 4-Tier RBAC</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    SuperAdmin, SecurityAuditor, BranchManager, and Customer segregation enforced via middleware gates and DB policies.
                </p>
            </div>

            <!-- 4. Append-Only Audit Logging -->
            <div class="glass-card p-6 rounded-3xl space-y-3 hover:border-emerald-500/30 transition-all">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">SHA-256 Chained Audit Trail</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Cryptographic block chaining. Immediate intrusion alerts on database row tampering. Certified forensic JSON/CSV exports.
                </p>
            </div>

            <!-- 5. 3-2-1 Asymmetric Backups -->
            <div class="glass-card p-6 rounded-3xl space-y-3 hover:border-emerald-500/30 transition-all">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">Asymmetric age Backups</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Server only holds public key. Ransomware cannot decrypt backups. Air-gapped offline private key decryption protocol.
                </p>
            </div>

            <!-- 6. Disaster Recovery Drills -->
            <div class="glass-card p-6 rounded-3xl space-y-3 hover:border-emerald-500/30 transition-all">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">Automated DR Drill Engine</h3>
                <p class="text-xs text-slate-400 leading-relaxed">
                    RTO < 4 seconds, RPO zero data loss. End-to-end restore verification with automated table integrity hashing.
                </p>
            </div>
        </div>
    </section>

    <!-- Pre-seeded Role Showcase for Presentation Panel -->
    <section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-slate-800">
        <div class="glass-card p-8 rounded-3xl border border-slate-800 space-y-6 text-center">
            <h3 class="text-xl font-bold text-white">Live Role Demo Switcher (Viva Defense Ready)</h3>
            <p class="text-xs text-slate-400 max-w-xl mx-auto">
                Pre-seeded test accounts with tiered privileges ready for immediate panel demonstration.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-left">
                <!-- SuperAdmin -->
                <div class="p-4 rounded-2xl bg-purple-950/40 border border-purple-500/30 space-y-2">
                    <span class="text-xs font-bold text-purple-300 block">SuperAdmin</span>
                    <span class="font-mono text-[11px] text-purple-200 block truncate">admin@secureauth.bank</span>
                    <p class="text-[11px] text-slate-400">Full system governance, RBAC assignment, 3-2-1 backup controls.</p>
                </div>

                <!-- Auditor -->
                <div class="p-4 rounded-2xl bg-amber-950/40 border border-amber-500/30 space-y-2">
                    <span class="text-xs font-bold text-amber-300 block">Security Auditor</span>
                    <span class="font-mono text-[11px] text-amber-200 block truncate">auditor@secureauth.bank</span>
                    <p class="text-[11px] text-slate-400">Forensics explorer, cryptographic hash chain validator, export reports.</p>
                </div>

                <!-- Branch Manager -->
                <div class="p-4 rounded-2xl bg-blue-950/40 border border-blue-500/30 space-y-2">
                    <span class="text-xs font-bold text-blue-300 block">Branch Manager</span>
                    <span class="font-mono text-[11px] text-blue-200 block truncate">branch@secureauth.bank</span>
                    <p class="text-[11px] text-slate-400">Customer directory, account verification, lockout resets.</p>
                </div>

                <!-- Customer -->
                <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 space-y-2">
                    <span class="text-xs font-bold text-emerald-300 block">Customer</span>
                    <span class="font-mono text-[11px] text-emerald-200 block truncate">customer@secureauth.bank</span>
                    <p class="text-[11px] text-slate-400">Checking balance, TOTP setup, transaction security telemetry.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-800 py-8 text-center text-xs text-slate-500">
        <p>Built with Laravel 11, Supabase Cloud PostgreSQL 17.11, and age X25519.</p>
        <p class="mt-1">Zero-Leak Git Version Control • 100% Free Open Infrastructure</p>
    </footer>
</body>
</html>
