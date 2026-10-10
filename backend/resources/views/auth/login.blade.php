<x-guest-layout>
    <x-slot name="heading">
        Clinical Staff Portal
    </x-slot>
    <x-slot name="subheading">
        Sign in to access patient dental charts & practice operations
    </x-slot>

    <div class="space-y-5" x-data="{
        userType: 'dentist',
        email: '{{ old('email', '') }}',
        password: '',
        showPass: false,
        selectType(type) {
            this.userType = type;
            if (type === 'admin') {
                this.email = 'admin@dentivault.com';
                this.password = 'Admin@Dental2026!';
            } else {
                this.email = '';
                this.password = '';
            }
        }
    }">
        <!-- User Type Selector -->
        <div>
            <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                Select Account Type:
            </label>
            <div class="grid grid-cols-3 gap-2">
                <!-- Option 1: Dentist -->
                <button type="button" @click="selectType('dentist')"
                    :class="userType === 'dentist' ? 'bg-cyan-950/90 border-cyan-500 text-cyan-300 shadow-md shadow-cyan-900/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">🩺</span>
                    <span class="text-xs font-bold">Dentist</span>
                    <span class="text-[10px] text-slate-500">Manual Entry</span>
                </button>

                <!-- Option 2: Practice Manager -->
                <button type="button" @click="selectType('manager')"
                    :class="userType === 'manager' ? 'bg-emerald-950/90 border-emerald-500 text-emerald-300 shadow-md shadow-emerald-900/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">📋</span>
                    <span class="text-xs font-bold">Manager</span>
                    <span class="text-[10px] text-slate-500">Manual Entry</span>
                </button>

                <!-- Option 3: Chief Admin -->
                <button type="button" @click="selectType('admin')"
                    :class="userType === 'admin' ? 'bg-purple-950/90 border-purple-500 text-purple-300 shadow-md shadow-purple-900/30' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:border-slate-700'"
                    class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-base">👑</span>
                    <span class="text-xs font-bold">Chief Admin</span>
                    <span class="text-[10px] text-purple-400 font-semibold">Autofill ⚡</span>
                </button>
            </div>
        </div>

        <!-- Mode Status Notification Banner -->
        <template x-if="userType === 'admin'">
            <div class="p-3 rounded-xl bg-purple-950/50 border border-purple-500/40 text-xs text-purple-200 flex items-center justify-between animate-fadeIn">
                <div class="flex items-center gap-2">
                    <span class="text-base">👑</span>
                    <div>
                        <div class="font-bold text-white">Chief Administrator Mode Active</div>
                        <div class="text-[11px] text-purple-300/80 font-mono">admin@dentivault.com (Credentials Autofilled)</div>
                    </div>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/30 text-purple-300 font-bold border border-purple-500/40">Ready</span>
            </div>
        </template>

        <template x-if="userType !== 'admin'">
            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs text-slate-300 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base" x-text="userType === 'dentist' ? '🩺' : '📋'"></span>
                    <div>
                        <div class="font-bold text-white" x-text="userType === 'dentist' ? 'Attending Dentist Login' : 'Practice Manager Login'"></div>
                        <div class="text-[11px] text-slate-400">Enter your credentials below (Blank for Security)</div>
                    </div>
                </div>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-semibold border border-slate-700">Blank Space</span>
            </div>
        </template>

        <!-- Login Form -->
        <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1">
                    Staff Email Address
                </label>
                <div class="relative">
                    <input id="email" type="email" name="email" x-model="email" required autofocus autocomplete="username"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all"
                        :placeholder="userType === 'dentist' ? 'e.g. dentist@dentivault.com' : (userType === 'manager' ? 'e.g. manager@dentivault.com' : 'admin@dentivault.com')">
                </div>
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                        Password
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-cyan-400 hover:text-cyan-300 font-medium">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <div class="relative">
                    <input id="password" :type="showPass ? 'text' : 'password'" name="password" x-model="password" required autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-sm text-slate-100 placeholder-slate-500 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-all pr-10"
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

            <!-- Remember Me & Shield Info -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-cyan-500/20">
                    <span class="text-xs text-slate-400">Remember session</span>
                </label>
                <span class="text-[11px] text-slate-500">NIST SP 800-63B Shield</span>
            </div>

            <!-- Submit Button -->
            <div class="pt-2 space-y-2">
                <button type="submit"
                    :class="userType === 'admin' ? 'from-purple-600 to-indigo-500 hover:from-purple-500 hover:to-indigo-400 text-white shadow-purple-900/30' : 'from-cyan-600 to-teal-500 hover:from-cyan-500 hover:to-teal-400 text-slate-950 shadow-cyan-500/20'"
                    class="w-full rounded-xl bg-gradient-to-r py-3 text-sm font-bold shadow-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition-all flex items-center justify-center gap-2">
                    <span x-text="userType === 'admin' ? '👑 Sign In as Chief Administrator' : 'Authorize & Sign In'"></span>
                    <span>&rarr;</span>
                </button>

                <!-- Biometric Fingerprint / Passkey Divider & Button -->
                <div class="relative py-2">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-800"></div>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-slate-950 px-2 text-[10px] text-slate-500 font-semibold tracking-wider">Or Fast Touch Authorization</span>
                    </div>
                </div>

                <div x-data="{
                    bioLoading: false,
                    bioError: '',
                    bioSuccess: '',
                    async handleBiometric() {
                        this.bioLoading = true;
                        this.bioError = '';
                        this.bioSuccess = '';
                        try {
                            const result = await window.DentiVaultBiometrics.login(email);
                            this.bioSuccess = 'Biometrics verified! Redirecting...';
                            setTimeout(() => {
                                window.location.href = result.redirect || '/dashboard';
                            }, 500);
                        } catch (err) {
                            console.error(err);
                            this.bioError = err.message || 'Fingerprint sensor error. Please use password or register sensor first.';
                        } finally {
                            this.bioLoading = false;
                        }
                    }
                }">
                    <button type="button" @click="handleBiometric()" :disabled="bioLoading"
                        class="w-full rounded-xl bg-slate-900 hover:bg-slate-800/90 border border-cyan-500/40 hover:border-cyan-400 p-3 text-xs font-bold text-cyan-300 transition-all flex items-center justify-center gap-2 group shadow-lg shadow-cyan-950/40">
                        <span class="text-base group-hover:scale-110 transition-transform">👆</span>
                        <span x-show="!bioLoading">1-Touch Fingerprint / Windows Hello</span>
                        <span x-show="bioLoading" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Scanning Biometric Sensor...</span>
                        </span>
                    </button>

                    <div x-show="bioError" x-cloak class="mt-2 p-2 rounded-lg bg-red-950/70 border border-red-500/40 text-[11px] text-red-300">
                        <span x-text="bioError"></span>
                    </div>

                    <div x-show="bioSuccess" x-cloak class="mt-2 p-2 rounded-lg bg-emerald-950/70 border border-emerald-500/40 text-[11px] text-emerald-300 font-bold">
                        <span x-text="bioSuccess"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-6 text-center border-t border-slate-800 pt-4 text-xs text-slate-400">
        New clinical staff onboarding?
        <a href="{{ route('register') }}" class="font-semibold text-cyan-400 hover:text-cyan-300">
            Register Staff Account
        </a>
    </div>
</x-guest-layout>
