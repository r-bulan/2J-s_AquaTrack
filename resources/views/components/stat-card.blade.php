@props(['title', 'value', 'subtext' => null, 'icon' => null, 'iconBg' => 'bg-blue-50 text-blue-600'])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between transition hover:shadow-md']) }}>
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $title }}</p>
        @if ($icon)
            <div class="w-10 h-10 rounded-xl {{ $iconBg }} flex items-center justify-center">
                {{ $icon }}
            </div>
        @endif
    </div>
    <div class="mt-4">
        <h3 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $value }}</h3>
        @if ($subtext)
            <p class="mt-1 text-xs text-slate-500 flex items-center gap-1.5">
                {{ $subtext }}
            </p>
        @endif
    </div>
</div>
