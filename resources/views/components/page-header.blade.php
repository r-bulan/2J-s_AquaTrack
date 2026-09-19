@props(['title', 'subtitle' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm text-slate-500 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex items-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
