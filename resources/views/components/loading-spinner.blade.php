@props(['size' => 'w-8 h-8'])

<div {{ $attributes->merge(['class' => "inline-block {$size} border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"]) }} role="status">
    <span class="sr-only">Loading...</span>
</div>
