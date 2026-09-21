{{-- NOTE: The active application layout used by all views via <x-layouts.app> is resources/views/components/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Two J’s AquaTrack' }} - Water Filling Station</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 selection:bg-blue-500 selection:text-white" x-data="{ mobileMenuOpen: false }">
    <div class="min-h-full flex flex-col">
        <!-- Mobile Sidebar Drawer Backdrop -->
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 lg:hidden"
            x-on:click="mobileMenuOpen = false"
            style="display: none;"
        ></div>

        <!-- Mobile Sidebar Drawer -->
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 shadow-2xl flex flex-col lg:hidden"
            style="display: none;"
        >
            <!-- Mobile Brand Header -->
            <div class="h-16 px-6 flex items-center justify-between border-b border-slate-100">
                <a href="{{ route('root') }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-400 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-base text-slate-900 leading-tight">Two J’s</div>
                        <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">AquaTrack</div>
                    </div>
                </a>
                <button
                    x-on:click="mobileMenuOpen = false"
                    class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation Links -->
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
                @include('layouts.navigation-links')
            </div>

            <!-- Mobile User Footer -->
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-sm shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="$dispatch('open-modal', 'edit-profile-name-modal'); mobileMenuOpen = false"
                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                        title="Edit Your Display Name"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>

        <!-- Desktop Sidebar (256px) -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-slate-200/80 z-30">
            <!-- Brand Header -->
            <div class="h-16 px-6 flex items-center border-b border-slate-100">
                <a href="{{ route('root') }}" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-400 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-base text-slate-900 tracking-tight leading-none">Two J’s</div>
                        <div class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mt-0.5">AquaTrack</div>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-4 py-5 space-y-1">
                @include('layouts.navigation-links')
            </div>

            <!-- Desktop User Footer -->
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-xs shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</p>
                            <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-semibold uppercase tracking-wider bg-blue-50 text-blue-700">
                                {{ auth()->user()->role }}
                            </span>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="$dispatch('open-modal', 'edit-profile-name-modal')"
                        class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                        title="Edit Your Display Name"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:pl-64 flex flex-col flex-1">
            <!-- Top Sticky Header (64px) -->
            <header class="sticky top-0 z-20 h-16 bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Mobile Hamburger Button -->
                    <button
                        x-on:click="mobileMenuOpen = true"
                        class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-hidden"
                        aria-label="Open sidebar"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-slate-500 hidden sm:inline">Station:</span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Two J’s Water Station (Operational)
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs text-slate-400">{{ now()->format('l, F d, Y') }}</p>
                        <p class="text-xs font-semibold text-slate-700">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs border border-slate-200">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            <!-- Main Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                        <button x-on:click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if (session('warning'))
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-between shadow-xs"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium">{{ session('warning') }}</p>
                        </div>
                        <button x-on:click="show = false" class="text-amber-500 hover:text-amber-700 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 shadow-xs"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">Please check the following errors:</p>
                                    <ul class="mt-1.5 list-disc list-inside text-xs space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <button x-on:click="show = false" class="text-rose-500 hover:text-rose-700 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
