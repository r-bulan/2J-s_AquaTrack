<x-layouts.auth title="Customer Registration">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Create Customer Account</h2>
        <p class="text-xs text-slate-500 mt-1">Register for convenient water refill delivery</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                placeholder="Juan Dela Cruz"
            >
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                    placeholder="juan@example.com"
                >
            </div>
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Phone (Mobile)</label>
                <input
                    id="phone"
                    type="text"
                    name="phone"
                    value="{{ old('phone') }}"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                    placeholder="0917-123-4567"
                >
            </div>
        </div>

        <div>
            <label for="address" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Delivery Address</label>
            <textarea
                id="address"
                name="address"
                rows="2"
                required
                class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                placeholder="House / Unit No., Street, Subdivision"
            >{{ old('address') }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="barangay" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Barangay</label>
                <input
                    id="barangay"
                    type="text"
                    name="barangay"
                    value="{{ old('barangay') }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                    placeholder="Brgy. San Antonio"
                >
            </div>
            <div>
                <label for="area" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Zone / Area</label>
                <input
                    id="area"
                    type="text"
                    name="area"
                    value="{{ old('area') }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                    placeholder="Zone 1"
                >
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
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
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Confirm</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                    placeholder="••••••••"
                >
            </div>
        </div>

        <button
            type="submit"
            class="w-full py-3 px-4 mt-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/25 transition flex items-center justify-center gap-2 cursor-pointer"
        >
            <span>Complete Registration</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Sign In here</a>
        </p>
    </div>
</x-layouts.auth>
