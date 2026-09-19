<x-layouts.auth title="Forgot Password">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Reset Password</h2>
        <p class="text-xs text-slate-500 mt-1">Enter your registered email to receive a password reset link</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                placeholder="name@example.com"
            >
        </div>

        <button
            type="submit"
            class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/25 transition flex items-center justify-center gap-2 cursor-pointer"
        >
            <span>Send Reset Instructions</span>
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Sign In
        </a>
    </div>
</x-layouts.auth>
