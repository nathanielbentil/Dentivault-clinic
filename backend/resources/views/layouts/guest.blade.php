<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'DentiVault' }} - Dental Practice Management & Clinical EDR</title>
    
    <!-- Tailwind CSS CDN & Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="/js/webauthn.js"></script>
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
        [x-cloak] { display: none !important; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="h-full font-sans antialiased selection:bg-cyan-500 selection:text-slate-950 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(6,182,212,0.18),rgba(255,255,255,0))]">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <a href="/" class="inline-flex items-center gap-3 group">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-400 p-0.5 shadow-lg shadow-cyan-500/20 group-hover:scale-105 transition-transform">
                    <div class="h-full w-full bg-slate-950 rounded-[10px] flex items-center justify-center text-xl">
                        🦷
                    </div>
                </div>
                <div class="text-left">
                    <span class="text-xl font-black tracking-tight text-white block">Denti<span class="text-cyan-400">Vault</span></span>
                    <span class="text-[10px] uppercase font-semibold tracking-widest text-slate-400 block">Dental Practice & EDR</span>
                </div>
            </a>
            
            @if (isset($heading))
                <h2 class="mt-6 text-2xl font-bold tracking-tight text-slate-100">
                    {{ $heading }}
                </h2>
            @endif
            @if (isset($subheading))
                <p class="mt-2 text-sm text-slate-400">
                    {{ $subheading }}
                </p>
            @endif
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="glass-panel py-8 px-6 shadow-2xl rounded-2xl sm:px-10 border border-slate-800">
                <!-- Validation & Status Alerts -->
                @if (session('status'))
                    <div class="mb-4 rounded-xl bg-cyan-950/60 p-4 border border-cyan-500/30 text-cyan-200 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl bg-red-950/60 p-4 border border-red-500/30 text-red-200 text-xs">
                        <div class="font-bold flex items-center gap-1.5 mb-1 text-red-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Security Validation Notice</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-slate-300">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
            
            <!-- Global Footer Info -->
            <div class="mt-6 text-center text-xs text-slate-500 flex items-center justify-center gap-4">
                <span>Argon2id + HMAC Pepper</span>
                <span>•</span>
                <span>RFC 6238 TOTP</span>
                <span>•</span>
                <span>HIPAA Encrypted</span>
            </div>
        </div>
    </div>
</body>
</html>
