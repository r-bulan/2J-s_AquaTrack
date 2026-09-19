<x-layouts.auth title="Sign In">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Sign in to your account</h2>
        <p class="text-xs text-slate-500 mt-1">Enter your station credentials to continue</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
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

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Forgot?</a>
            </div>
            <input
                id="password"
                type="password"
                name="password"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition"
                placeholder="••••••••"
            >
        </div>

        <div class="flex items-center">
            <input
                id="remember"
                type="checkbox"
                name="remember"
                class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500"
            >
            <label for="remember" class="ml-2 block text-xs font-medium text-slate-600">Keep me logged in</label>
        </div>

        <button
            type="submit"
            class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-500/25 transition flex items-center justify-center gap-2 cursor-pointer"
        >
            <span>Sign In</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500">
            Need a refill account?
            <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Register as Customer</a>
        </p>
    </div>

    <!-- Quick Demo Accounts Switcher for Development Testing -->
    <div class="mt-6 p-3.5 bg-slate-50 rounded-2xl border border-slate-200/60" x-data>
        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 text-center">Demo Quick Fill</p>
        <div class="grid grid-cols-3 gap-1.5 text-center">
            <button
                type="button"
                @click="document.getElementById('email').value='admin@twojs.test'; document.getElementById('password').value='Password123!';"
                class="px-2 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-600 transition shadow-2xs"
            >
                Admin
            </button>
            <button
                type="button"
                @click="document.getElementById('email').value='rider@twojs.test'; document.getElementById('password').value='Password123!';"
                class="px-2 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-600 transition shadow-2xs"
            >
                Rider
            </button>
            <button
                type="button"
                @click="document.getElementById('email').value='customer@twojs.test'; document.getElementById('password').value='Password123!';"
                class="px-2 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold text-slate-700 hover:border-blue-500 hover:text-blue-600 transition shadow-2xs"
            >
                Customer
            </button>
        </div>
    </div>
</x-layouts.auth>
