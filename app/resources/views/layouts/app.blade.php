<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} - SecureAuth Banking & Disaster Recovery</title>
    
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
                            50: '#f0fdf4',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d',
                            950: '#052e16',
                        },
                        cyber: {
                            50: '#f0f9ff',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .stat-card {
            background: radial-gradient(circle at top left, rgba(16, 185, 129, 0.08), transparent 70%), rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-emerald-500 selection:text-slate-950 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(16,185,129,0.1),rgba(255,255,255,0))] flex flex-col min-h-screen">
    
    <!-- Top Banking & Security Navbar -->
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl" x-data="{ mobileMenuOpen: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 p-0.5 shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                            <div class="h-full w-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                                <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <span class="text-lg font-black tracking-tight text-white block">Secure<span class="text-emerald-400">Auth</span></span>
                            <span class="text-[9px] uppercase font-bold tracking-widest text-emerald-500 block">Bank-Grade Protocol</span>
                        </div>
                    </a>

                    <!-- Navigation Links Based on Roles -->
                    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800/80 text-emerald-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-900' }}">
                            Banking Overview
                        </a>

                        @if(Auth::user()->hasRole('SuperAdmin'))
                            <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-purple-950/60 border border-purple-500/30 text-purple-300 font-semibold' : 'text-slate-300 hover:text-purple-300 hover:bg-slate-900' }}">
                                SuperAdmin Zone
                            </a>
                            <a href="{{ route('admin.backups') }}" class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('admin.backups') ? 'bg-cyan-950/60 border border-cyan-500/30 text-cyan-300 font-semibold' : 'text-slate-300 hover:text-cyan-300 hover:bg-slate-900' }}">
                                3-2-1 Backups & DR
                            </a>
                        @endif

                        @if(Auth::user()->hasAnyRole(['SecurityAuditor', 'SuperAdmin']))
                            <a href="{{ route('audit.dashboard') }}" class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('audit.*') ? 'bg-amber-950/60 border border-amber-500/30 text-amber-300 font-semibold' : 'text-slate-300 hover:text-amber-300 hover:bg-slate-900' }}">
                                Auditor Forensics Hub
                            </a>
                        @endif

                        @if(Auth::user()->hasAnyRole(['BranchManager', 'SuperAdmin']))
                            <a href="{{ route('branch.dashboard') }}" class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('branch.*') ? 'bg-blue-950/60 border border-blue-500/30 text-blue-300 font-semibold' : 'text-slate-300 hover:text-blue-300 hover:bg-slate-900' }}">
                                Branch Ops
                            </a>
                        @endif
                    </nav>
                </div>

                <!-- Right Side: User Profile & Security Badge -->
                <div class="flex items-center gap-3">
                    <!-- 2FA Active Indicator Badge -->
                    <a href="{{ route('two-factor.show') }}" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ Auth::user()->two_factor_confirmed_at ? 'bg-emerald-950 border border-emerald-500/40 text-emerald-400' : 'bg-amber-950 border border-amber-500/40 text-amber-400 animate-pulse' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ Auth::user()->two_factor_confirmed_at ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                        {{ Auth::user()->two_factor_confirmed_at ? '2FA Active' : 'Enable 2FA' }}
                    </a>

                    <!-- User Role Badge -->
                    @php
                        $primaryRole = Auth::user()->roles->first()?->name ?? 'Customer';
                        $badgeColor = match($primaryRole) {
                            'SuperAdmin' => 'bg-purple-950 text-purple-300 border-purple-500/30',
                            'SecurityAuditor' => 'bg-amber-950 text-amber-300 border-amber-500/30',
                            'BranchManager' => 'bg-blue-950 text-blue-300 border-blue-500/30',
                            default => 'bg-slate-900 text-slate-300 border-slate-700',
                        };
                    @endphp
                    <span class="hidden md:inline-block px-2.5 py-1 rounded-md text-xs font-bold border {{ $badgeColor }}">
                        {{ $primaryRole }}
                    </span>

                    <!-- User Menu Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 p-1.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 transition-colors">
                            <div class="h-8 w-8 rounded-lg bg-emerald-900/50 border border-emerald-500/30 flex items-center justify-center font-bold text-emerald-400 text-xs">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="hidden lg:inline text-xs font-medium text-slate-200 max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 rounded-xl glass-card py-2 shadow-2xl z-50 border border-slate-800">
                            <div class="px-4 py-2 border-b border-slate-800 text-xs">
                                <p class="text-slate-400">Signed in as</p>
                                <p class="font-semibold text-slate-200 truncate">{{ Auth::user()->email }}</p>
                            </div>

                            <a href="{{ route('two-factor.show') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs text-slate-300 hover:bg-slate-800/80 hover:text-emerald-400 transition-colors">
                                <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>2FA Security Center</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-800 mt-1">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-xs text-red-400 hover:bg-red-950/40 hover:text-red-300 transition-colors text-left">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Global Flash Status Messages -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if (session('status'))
            <div class="mb-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 p-4 text-sm text-emerald-300 flex items-center justify-between shadow-lg shadow-emerald-950/20">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="mb-4 rounded-xl bg-red-950/80 border border-red-500/40 p-4 text-sm text-red-300 shadow-lg shadow-red-950/20">
                <div class="flex items-center gap-2 font-semibold mb-1">
                    <svg class="h-5 w-5 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Security Alert</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-xs text-red-300/90">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content Slot -->
    <main class="flex-1 pb-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-slate-800/80 bg-slate-950/80 py-6 text-center text-xs text-slate-500">
        <div class="mx-auto max-w-7xl px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Supabase PostgreSQL 17.11 & Pooler • Live Cloud Connection Active</span>
            </div>
            <div>
                <span>Argon2id (64MB) + Pepper • Asymmetric age X25519 • Zero-Knowledge Cloud</span>
            </div>
        </div>
    </footer>
</body>
</html>
