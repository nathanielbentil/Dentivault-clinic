<x-guest-layout>
    <x-slot name="heading">
        Banking Portal Login
    </x-slot>
    <x-slot name="subheading">
        Sign in with your enterprise credentials
    </x-slot>

    <!-- Quick Demo Credential Selectors for Viva Examiners -->
    <div class="mb-6 p-3 rounded-xl bg-slate-900/90 border border-slate-800 text-xs" x-data="{
        fill(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    }">
        <div class="flex items-center justify-between text-slate-400 font-semibold mb-2">
            <span>⚡ Quick Demo Credentials:</span>
            <span class="text-[10px] text-emerald-400 uppercase tracking-wider">Viva Ready</span>
        </div>
        <div class="grid grid-cols-2 gap-1.5">
            <button type="button" @click="fill('admin@secureauth.bank', 'Admin@SecureAuth2026!')" class="text-left px-2.5 py-1.5 rounded-lg bg-purple-950/50 hover:bg-purple-900/50 border border-purple-500/30 text-purple-200 transition-colors">
                <span class="font-bold block text-[11px]">SuperAdmin</span>
                <span class="text-[10px] text-purple-300/70 truncate block">admin@secureauth.bank</span>
            </button>
            <button type="button" @click="fill('auditor@secureauth.bank', 'Auditor@SecureAuth2026!')" class="text-left px-2.5 py-1.5 rounded-lg bg-amber-950/50 hover:bg-amber-900/50 border border-amber-500/30 text-amber-200 transition-colors">
                <span class="font-bold block text-[11px]">Auditor</span>
                <span class="text-[10px] text-amber-300/70 truncate block">auditor@secureauth.bank</span>
            </button>
            <button type="button" @click="fill('branch@secureauth.bank', 'Branch@SecureAuth2026!')" class="text-left px-2.5 py-1.5 rounded-lg bg-blue-950/50 hover:bg-blue-900/50 border border-blue-500/30 text-blue-200 transition-colors">
                <span class="font-bold block text-[11px]">Branch Manager</span>
                <span class="text-[10px] text-blue-300/70 truncate block">branch@secureauth.bank</span>
            </button>
            <button type="button" @click="fill('customer@secureauth.bank', 'Customer@SecureAuth2026!')" class="text-left px-2.5 py-1.5 rounded-lg bg-emerald-950/50 hover:bg-emerald-900/50 border border-emerald-500/30 text-emerald-200 transition-colors">
                <span class="font-bold block text-[11px]">Customer</span>
                <span class="text-[10px] text-emerald-300/70 truncate block">customer@secureauth.bank</span>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ showPass: false }">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Email Address
            </label>
            <div class="relative">
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                    placeholder="name@secureauth.bank">
            </div>
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <input id="password" :type="showPass ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all pr-10"
                    placeholder="••••••••••••">
                <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200">
                    <svg x-show="!showPass" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPass" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Remember Me & Lockout Info -->
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500/20">
                <span class="text-xs text-slate-400">Remember session</span>
            </label>
            <span class="text-[11px] text-slate-500">5 attempts = 15m lockout</span>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition-all flex items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <span>Authorize & Sign In</span>
            </button>
        </div>
    </form>

    <div class="mt-6 text-center border-t border-slate-800 pt-4 text-xs text-slate-400">
        Need a new account?
        <a href="{{ route('register') }}" class="font-semibold text-emerald-400 hover:text-emerald-300">
            Open an account with NIST validation
        </a>
    </div>
</x-guest-layout>
