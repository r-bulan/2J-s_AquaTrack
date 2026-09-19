@props(['title' => null, 'subtitle' => null, 'action' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-100 shadow-sm p-6']) }}>
    @if ($title || $action)
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div>
                @if ($title)
                    <h2 class="text-lg font-bold text-slate-800">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($action)
                <div>{{ $action }}</div>
            @endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
</div>
