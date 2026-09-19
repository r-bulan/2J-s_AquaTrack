<x-layouts.auth title="Set New Password">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Set New Password</h2>
        <p class="text-xs text-slate-500 mt-1">Please enter your new password below</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $email) }}"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
            >
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">New Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                placeholder="••••••••"
            >
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                placeholder="••••••••"
            >
        </div>

        <button
            type="submit"
            class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/25 transition flex items-center justify-center gap-2 cursor-pointer"
        >
            <span>Update Password</span>
        </button>
    </form>
</x-layouts.auth>
