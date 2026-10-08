<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SecureAuth' }} - Bank-Grade Authentication & Disaster Recovery</title>
    
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
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-emerald-500 selection:text-slate-950 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(16,185,129,0.15),rgba(255,255,255,0))]">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <a href="/" class="inline-flex items-center gap-3 group">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                    <div class="h-full w-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <svg class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                </div>
                <div class="text-left">
                    <span class="text-xl font-black tracking-tight text-white block">Secure<span class="text-emerald-400">Auth</span></span>
                    <span class="text-[10px] uppercase font-semibold tracking-widest text-slate-400 block">Bank-Grade Shield</span>
                </div>
            </a>
            @if(isset($heading))
                <h2 class="mt-6 text-2xl font-bold tracking-tight text-slate-100">{{ $heading }}</h2>
            @endif
            @if(isset($subheading))
                <p class="mt-2 text-sm text-slate-400">{{ $subheading }}</p>
            @endif
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
            <div class="glass-panel py-8 px-6 shadow-2xl rounded-2xl sm:px-10">
                @if (session('status'))
                    <div class="mb-5 rounded-lg bg-emerald-950/80 border border-emerald-500/30 p-4 text-sm text-emerald-300 flex items-center gap-3">
                        <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="mb-5 rounded-lg bg-red-950/80 border border-red-500/30 p-4 text-sm text-red-300">
                        <div class="flex items-center gap-2 font-semibold mb-1">
                            <svg class="h-5 w-5 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Security Validation Notice</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-xs text-red-300/90">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
            
            <div class="mt-6 text-center text-xs text-slate-500 space-y-1">
                <p>Protected by Argon2id (64MB) • HMAC Pepper v1 • Asymmetric age X25519</p>
                <p>© {{ date('Y') }} SecureAuth Core Banking. Zero-Knowledge Defense Protocol.</p>
            </div>
        </div>
    </div>
</body>
</html>
