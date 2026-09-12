@props(['active'])
<a {{ $attributes->merge(['class' => ($active ?? false) ? 'block px-3 py-2 rounded-md text-ink bg-surface2' : 'block px-3 py-2 rounded-md text-muted hover:text-ink hover:bg-surface2']) }}>{{ $slot }}</a>
