<x-guest-layout>
    <x-slot name="heading">
        Verify Your Email
    </x-slot>
    <x-slot name="subheading">
        Security verification link sent
    </x-slot>

    <div class="space-y-4 text-sm text-slate-300">
        <p>
            Thanks for signing up! Before getting started, please verify your email address by clicking on the link we just emailed to you.
        </p>
        <p class="text-xs text-slate-400">
            If you didn't receive the email, we will gladly send you another.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="rounded-lg bg-emerald-950/80 border border-emerald-500/30 p-3 text-xs text-emerald-300">
                A new verification link has been sent to the email address you provided during registration.
            </div>
        @endif

        <div class="pt-3 flex items-center justify-between gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-emerald-500 transition-all">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-red-400 transition-colors">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
