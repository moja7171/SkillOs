{{-- Level distribution for one user's lessons in a course. $lessons must have masteryRecords loaded for that user. --}}
@props(['lessons', 'user'])
@php
    $counts = $lessons->countBy(fn ($l) => \App\Models\MasteryRecord::LEVEL_INDEX[$l->levelFor($user)])->all();
    $labels = \App\Models\MasteryRecord::LEVEL_LABELS; $keys = array_keys($labels);
@endphp
<div {{ $attributes }}>
    <x-level-bar :counts="$counts" />
    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-[12.5px]">
        @for ($i = 4; $i >= 1; $i--)
            @if (($counts[$i] ?? 0) > 0)
                <span style="color: var(--l{{ $i }});">{{ fa_num($counts[$i]) }} {{ $labels[$keys[$i]] }}</span>
            @endif
        @endfor
        @if (($counts[0] ?? 0) > 0)
            <span class="text-faint">{{ fa_num($counts[0]) }} شروع‌نشده</span>
        @endif
    </div>
</div>
