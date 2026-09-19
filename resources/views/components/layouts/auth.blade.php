<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login' }} - Two J’s AquaTrack</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }
    </style>
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 selection:bg-blue-500 selection:text-white flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-400 items-center justify-center text-white shadow-xl shadow-blue-500/25 mb-4">
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Two J’s AquaTrack</h1>
            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mt-0.5">Water Filling Station Management</p>
        </div>

        <!-- Auth Card Container -->
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50 p-6 sm:p-8">
            @if (session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-blue-50 text-blue-800 text-xs font-medium border border-blue-100">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 text-rose-800 text-xs font-medium border border-rose-100">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>

        <!-- Footer Notice -->
        <div class="text-center mt-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} Two J’s Water Station. All rights reserved.
        </div>
    </div>
</body>
</html>
