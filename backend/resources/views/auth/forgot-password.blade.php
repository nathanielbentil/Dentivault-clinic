<x-guest-layout>
    <x-slot name="heading">
        Account Recovery
    </x-slot>
    <x-slot name="subheading">
        Send a secure password reset link to your email
    </x-slot>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Registered Email Address
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                placeholder="name@secureauth.bank">
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition-all flex items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Email Secure Reset Link</span>
            </button>
        </div>
    </form>

    <div class="mt-6 text-center border-t border-slate-800 pt-4 text-xs text-slate-400">
        <a href="{{ route('login') }}" class="font-semibold text-slate-400 hover:text-emerald-400 flex items-center justify-center gap-1">
            <span>← Back to login</span>
        </a>
    </div>
</x-guest-layout>
