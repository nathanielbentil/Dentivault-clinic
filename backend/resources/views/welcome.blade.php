<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DentiVault - Dental Practice Management & Clinical EDR Platform</title>
    
    <!-- Tailwind CSS CDN & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dental: {
                            50: '#ecfeff',
                            100: '#cffafe',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2',
                            700: '#0e7490',
                            800: '#155e75',
                            900: '#164e63',
                            950: '#083344',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-full font-sans antialiased selection:bg-cyan-500 selection:text-slate-950 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(6,182,212,0.2),rgba(255,255,255,0))]">
    
    <!-- Top Navigation -->
    <header class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-20 items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-cyan-600 to-teal-400 p-0.5 shadow-lg shadow-cyan-500/20">
                        <div class="h-full w-full bg-slate-950 rounded-[14px] flex items-center justify-center text-xl">
                            🦷
                        </div>
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight text-white block">Denti<span class="text-cyan-400">Vault</span></span>
                        <span class="text-[10px] uppercase font-bold tracking-widest text-cyan-500 block">Dental Practice & EDR</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:from-cyan-500 hover:to-teal-400 transition-all">
                            Enter Clinical Portal &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:from-cyan-500 hover:to-teal-400 transition-all">
                            Staff Login &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-cyan-950/70 border border-cyan-500/30 text-cyan-300 text-xs font-semibold mb-6">
                <span>✨</span>
                <span>Commercial Dental Practice Management & Clinical EDR Platform</span>
            </div>
            
            <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white leading-tight">
                Secure Dental Records. <br>
                <span class="bg-gradient-to-r from-cyan-400 via-teal-300 to-emerald-400 bg-clip-text text-transparent">
                    Effortless Practice Flow.
                </span>
            </h1>
            
            <p class="mt-6 text-base sm:text-lg text-slate-400 leading-relaxed">
                Empower your dental practice with interactive tooth diagnostics, HIPAA cryptographic audit trails, 3-tier role authorization, and Age X25519 automated clinical backups.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 text-slate-950 font-bold text-sm shadow-xl shadow-cyan-500/20 hover:from-cyan-500 hover:to-teal-400 transition-all flex items-center justify-center gap-2">
                    <span>Access Clinical Portal</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- 3 Staff Roles Preview -->
        <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-cyan-950 border border-cyan-500/30 flex items-center justify-center text-xl mb-4">
                    🩺
                </div>
                <h3 class="text-base font-bold text-white">Attending Dentist</h3>
                <p class="text-xs text-slate-400 mt-1">Dr. Isaac Donkoh</p>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Interactive tooth charting, procedure documentation, clinical notes, and treatment plan progress tracking.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-emerald-950 border border-emerald-500/30 flex items-center justify-center text-xl mb-4">
                    📋
                </div>
                <h3 class="text-base font-bold text-white">Clinic Practice Manager</h3>
                <p class="text-xs text-slate-400 mt-1">Dr. Bright Ainoo</p>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Practice operations, new patient registration intake, treatment flow metrics, and clinic capacity KPIs.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-6 border border-slate-800">
                <div class="h-10 w-10 rounded-xl bg-purple-950 border border-purple-500/30 flex items-center justify-center text-xl mb-4">
                    👑
                </div>
                <h3 class="text-base font-bold text-white">Chief Dental Director</h3>
                <p class="text-xs text-slate-400 mt-1">Dr. Prof. Nathaniel Bentil</p>
                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                    Master governance, staff role management, HIPAA tamper-evident audit telemetry, and Age 3-2-1 backup vault.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/60 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            DentiVault Platform • 100% Offline Capable • Argon2id + HMAC Pepper • RFC 6238 TOTP • SHA-256 Hash Chain
        </div>
    </footer>
</body>
</html>
