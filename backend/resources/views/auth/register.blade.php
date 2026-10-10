<x-guest-layout>
    <x-slot name="heading">
        Clinical Staff Onboarding
    </x-slot>
    <x-slot name="subheading">
        Register a new Dentist or Practitioner account with NIST SP 800-63B protection
    </x-slot>

    <!-- Quick Assist Banner / Demo Autofill Helper -->
    <div x-data class="mb-4 p-3 rounded-xl bg-cyan-950/40 border border-cyan-500/30 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-base">💡</span>
            <div>
                <div class="text-xs font-bold text-white">Need a quick test account?</div>
                <div class="text-[11px] text-cyan-300/80">Click autofill to populate sample valid credentials.</div>
            </div>
        </div>
        <button type="button" @click="
            $dispatch('autofill-demo', {
                name: 'Dr. Sarah Mensah',
                email: 'sarah.dentist' + Math.floor(Math.random() * 900 + 100) + '@dentivault.com',
                password: 'DentalStaff@2026!'
            })
        " class="px-3 py-1 rounded-lg bg-cyan-500/20 hover:bg-cyan-500/30 border border-cyan-500/40 text-[11px] font-bold text-cyan-300 transition-all flex items-center gap-1 cursor-pointer">
            <span>⚡ Autofill Demo</span>
        </button>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{
        name: '{{ old('name') }}',
        email: '{{ old('email') }}',
        password: '',
        confirmPassword: '',
        showPass: false,
        showConfirmPass: false,
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
            return { label: 'Clinical-Grade (Argon2id Ready)', color: 'bg-cyan-500', text: 'text-cyan-400' };
        },
        generatePassword() {
            const pass = 'DentalPass#' + Math.floor(Math.random() * 9000 + 1000) + '!';
            this.password = pass;
            this.confirmPassword = pass;
        }
    }" @autofill-demo.window="
        name = $event.detail.name;
        email = $event.detail.email;
        password = $event.detail.password;
        confirmPassword = $event.detail.password;
    ">
        @csrf

        <!-- Full Name -->
        <div>
            <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Full Name & Professional Title
            </label>
            <input id="name" type="text" name="name" x-model="name" required autofocus autocomplete="name"
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all"
                placeholder="e.g. Dr. Sarah Mensah">
            <p class="text-[11px] text-slate-400 mt-1">Hint: Enter your clinical title and full name.</p>
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Clinical Staff Email
            </label>
            <input id="email" type="email" name="email" x-model="email" required autocomplete="email"
                class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all"
                placeholder="e.g. dr.sarah@dentivault.com or sarah@gmail.com">
            <p class="text-[11px] text-slate-400 mt-1">Hint: Any standard email format (e.g. <span class="text-cyan-400 font-mono">name@domain.com</span>).</p>
        </div>

        <!-- Master Password -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                    Master Password (Min 12 Characters)
                </label>
                <button type="button" @click="generatePassword()" class="text-[11px] text-cyan-400 hover:text-cyan-300 font-semibold flex items-center gap-1 cursor-pointer">
                    <span>⚡ Generate Strong Password</span>
                </button>
            </div>
            <div class="relative">
                <input id="password" :type="showPass ? 'text' : 'password'" name="password" x-model="password" required autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all pr-10"
                    placeholder="e.g. DentalStaff@2026!">
                
                <!-- Password Show/Hide Eye Toggle -->
                <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200 cursor-pointer">
                    <svg x-show="!showPass" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPass" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>

            <!-- NIST Checklist & Password Hints -->
            <div class="mt-2 space-y-1.5">
                <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-400">Password Strength:</span>
                    <span class="font-bold" :class="strengthLabel.text" x-text="password.length > 0 ? strengthLabel.label : 'Enter 12+ characters'"></span>
                </div>
                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300 rounded-full" :class="strengthLabel.color" :style="`width: ${password.length > 0 ? score : 0}%`"></div>
                </div>

                <!-- NIST Checklist Requirements with Real-time Tick -->
                <div class="grid grid-cols-2 gap-1.5 text-[11px] pt-1">
                    <div class="flex items-center gap-1.5" :class="lengthValid ? 'text-emerald-400 font-bold' : 'text-slate-400'">
                        <span x-text="lengthValid ? '✓' : '✕'"></span>
                        <span>12+ Characters</span>
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasUpper && hasLower ? 'text-emerald-400 font-bold' : 'text-slate-400'">
                        <span x-text="hasUpper && hasLower ? '✓' : '✕'"></span>
                        <span>Upper & Lower Case</span>
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasNumber ? 'text-emerald-400 font-bold' : 'text-slate-400'">
                        <span x-text="hasNumber ? '✓' : '✕'"></span>
                        <span>Number (0-9)</span>
                    </div>
                    <div class="flex items-center gap-1.5" :class="hasSpecial ? 'text-emerald-400 font-bold' : 'text-slate-400'">
                        <span x-text="hasSpecial ? '✓' : '✕'"></span>
                        <span>Symbol (!@#$%^&*)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                Confirm Password
            </label>
            <div class="relative">
                <input id="password_confirmation" :type="showConfirmPass ? 'text' : 'password'" name="password_confirmation" x-model="confirmPassword" required autocomplete="new-password"
                    class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all pr-10"
                    placeholder="Re-enter password">
                
                <!-- Confirm Password Show/Hide Eye Toggle -->
                <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200 cursor-pointer">
                    <svg x-show="!showConfirmPass" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showConfirmPass" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
            
            <div x-show="confirmPassword.length > 0" x-cloak class="text-[11px] mt-1">
                <span x-show="matches" class="text-emerald-400 font-bold flex items-center gap-1">✓ Passwords match perfectly!</span>
                <span x-show="!matches" class="text-red-400 flex items-center gap-1">✕ Passwords do not match.</span>
            </div>
        </div>

        <!-- Role Badge Notice -->
        <div class="p-3 rounded-xl bg-cyan-950/40 border border-cyan-500/20 text-xs text-slate-300 space-y-1">
            <div class="font-semibold text-cyan-300 flex items-center gap-1.5">
                <span>🩺</span>
                <span>Default Role: Attending Dentist</span>
            </div>
            <p class="text-[11px] text-slate-400">
                You will be granted immediate access to dental charts, tooth diagnostics, and clinical treatment notes upon registration.
            </p>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-cyan-600 to-teal-500 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 hover:from-cyan-500 hover:to-teal-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span>Complete Staff Registration</span>
                <span>&rarr;</span>
            </button>
        </div>
    </form>

    <div class="mt-6 text-center border-t border-slate-800 pt-4 text-xs text-slate-400">
        Already registered on staff?
        <a href="{{ route('login') }}" class="font-semibold text-cyan-400 hover:text-cyan-300">
            Sign in to Clinical Portal
        </a>
    </div>
</x-guest-layout>
