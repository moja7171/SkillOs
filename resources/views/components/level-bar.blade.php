{{-- $counts: [levelIndex => n] --}}
@props(['counts'])
@php
    $total = max(1, array_sum($counts));
    $keys = array_keys(\App\Models\MasteryRecord::LEVEL_LABELS);
    // A stacked multi-segment bar has no single "value" for aria-valuenow, so the
    // accessible equivalent is a plain-text breakdown via aria-label instead.
    $summary = collect($counts)->filter(fn ($n) => $n > 0)->sortKeysDesc()
        ->map(fn ($n, $i) => fa_num($n).' '.\App\Models\MasteryRecord::LEVEL_LABELS[$keys[$i]])
        ->implode('، ');
@endphp
<div {{ $attributes->merge(['class' => 'levelbar']) }} role="img" aria-label="{{ $summary ?: 'هنوز پیشرفتی ثبت نشده' }}">
    @for ($i = 4; $i >= 0; $i--)
        @if (($counts[$i] ?? 0) > 0)
            <span style="width: {{ round(($counts[$i] / $total) * 100, 1) }}%; background: var(--l{{ $i }});"></span>
        @endif
    @endfor
</div>
