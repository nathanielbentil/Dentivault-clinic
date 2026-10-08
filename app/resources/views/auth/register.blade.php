<x-guest-layout>
    <x-slot name="heading">
        Account Registration
    </x-slot>
    <x-slot name="subheading">
        NIST SP 800-63B Compliant Password Validation
    </x-slot>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{
        password: '',
        confirmPassword: '',
        get lengthValid() { return this.password.length >= 12; },
        get hasUpper() { return /[A-Z]/.test(this.password); },
        get hasLower() { return /[a-z]/.test(this.password); },
        get hasNumber() { return /[0-9]/.test(this.password); },
        get hasSpecial() { return /[^A-Za-z0-9]/.test(this.password); },
        get matches() { return this.password.length > 0 && this.password === this.confirmPassword; },
        get score() {
            let s = 0;
            if (this.password.length >= 12) s += 25;
            if (this.password.length >= 16) s += 15;
            if (this.hasUpper && this.hasLower) s += 20;
            if (this.hasNumber) s += 20;
            if (this.hasSpecial) s += 20;
            return Math.min(100, s);
        },
        get strengthLabel() {
            if (this.score < 40) return { label: 'Weak', color: 'bg-red-500', text: 'text-red-400' };
            if (this.score < 70) return { label: 'Medium', color: 'bg-amber-500', text: 'text-amber-400' };
            if (this.score < 90) return { label: 'Strong', color: 'bg-blue-500', text: 'text-blue-400' };
            return { label: 'Bank-Grade (Argon2id Ready)', color: 'bg-emerald-500', text: 'text-emerald-400' };
        }
    }">
        @csrf

        <!-- Full Name -->
        <div>
            <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Full Name
            </label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                placeholder="Jane Doe">
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Corporate or Personal Email
            </label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                placeholder="jane@secureauth.bank">
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Master Password (Min 12 Characters)
            </label>
            <input id="password" type="password" name="password" required autocomplete="new-password" x-model="password"
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                placeholder="••••••••••••">

            <!-- Real-Time Password Strength Meter -->
            <div class="mt-2.5 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Entropy Strength:</span>
                    <span class="font-bold" :class="strengthLabel.text" x-text="strengthLabel.label"></span>
                </div>
                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300" :class="strengthLabel.color" :style="`width: ${score}%`"></div>
                </div>

                <!-- Checklist -->
                <div class="grid grid-cols-2 gap-1.5 pt-1 text-[11px]">
                    <div class="flex items-center gap-1.5" :class="lengthValid ? 'text-emerald-400' : 'text-slate-500'">
                        <span x-text="lengthValid ? '✓' : '○'"></span> 12+ characters
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasUpper ? 'text-emerald-400' : 'text-slate-500'">
                        <span x-text="hasUpper ? '✓' : '○'"></span> Uppercase letter
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasLower ? 'text-emerald-400' : 'text-slate-500'">
                        <span x-text="hasLower ? '✓' : '○'"></span> Lowercase letter
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasNumber ? 'text-emerald-400' : 'text-slate-500'">
                        <span x-text="hasNumber ? '✓' : '○'"></span> Number digit
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasSpecial ? 'text-emerald-400' : 'text-slate-500'">
                        <span x-text="hasSpecial ? '✓' : '○'"></span> Special symbol
                    </div>
                    <div class="flex items-center gap-1.5" :class="matches ? 'text-emerald-400' : 'text-slate-500'">
                        <span x-text="matches ? '✓' : '○'"></span> Passwords match
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Confirm Password
            </label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" x-model="confirmPassword"
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition-all"
                placeholder="••••••••••••">
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:from-emerald-500 hover:to-teal-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition-all flex items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Create Secure Account</span>
            </button>
        </div>
    </form>

    <div class="mt-6 text-center border-t border-slate-800 pt-4 text-xs text-slate-400">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-emerald-400 hover:text-emerald-300">
            Sign in to existing account
        </a>
    </div>
</x-guest-layout>
