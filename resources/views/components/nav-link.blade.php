@props(['active'])

@php
$classes = ($active ?? false)
    ? 'px-3 py-1.5 rounded-md text-[14px] font-medium text-ink bg-surface2 hover:text-ink'
    : 'px-3 py-1.5 rounded-md text-[14px] font-medium text-muted hover:text-ink hover:bg-surface2 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
