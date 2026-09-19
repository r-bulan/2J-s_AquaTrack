@props(['status'])

@php
    $normalized = trim($status);

    $colorClasses = match (strtolower($normalized)) {
        'pending', 'open', 'unpaid', 'due soon' => 'bg-amber-50 text-amber-700 border-amber-200',
        'confirmed', 'partial', 'in progress' => 'bg-blue-50 text-blue-700 border-blue-200',
        'out for delivery', 'en route' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'delivered', 'active', 'paid', 'settled', 'income', 'resolved', 'good' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled', 'failed', 'overdue', 'credit', 'expense' => 'bg-rose-50 text-rose-700 border-rose-200',
        'assigned', 'inactive' => 'bg-slate-100 text-slate-700 border-slate-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {$colorClasses}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5 opacity-70"></span>
    {{ $slot->isNotEmpty() ? $slot : $status }}
</span>
