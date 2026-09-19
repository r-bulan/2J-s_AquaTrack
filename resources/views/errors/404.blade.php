<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Not Found - Two J’s AquaTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-6 text-slate-800" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="text-center max-w-md bg-white p-8 rounded-3xl border border-slate-100 shadow-xl shadow-slate-200/50">
        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">404</h1>
        <h2 class="text-lg font-bold text-slate-800 mt-1">Page Not Found</h2>
        <p class="text-sm text-slate-500 mt-2">The page or refill route you are looking for does not exist or has been moved.</p>
        <div class="mt-6">
            <a href="{{ route('root') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/20 transition">
                Return to Station
            </a>
        </div>
    </div>
</body>
</html>
