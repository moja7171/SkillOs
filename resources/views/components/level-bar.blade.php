{{-- $counts: [levelIndex => n] --}}
@props(['counts'])
@php $total = max(1, array_sum($counts)); @endphp
<div {{ $attributes->merge(['class' => 'levelbar']) }}>
    @for ($i = 4; $i >= 0; $i--)
        @if (($counts[$i] ?? 0) > 0)
            <span style="width: {{ round(($counts[$i] / $total) * 100, 1) }}%; background: var(--l{{ $i }});"></span>
        @endif
    @endfor
</div>
