{{-- Kind badge for a plan item: review / learn / practice.
     Deliberately neutral (badge-ghost + icon, not a color) — the l0-l4 palette is
     reserved for mastery level (x-level-badge), so kind and level never collide when
     both appear in the same row (e.g. week.blade.php's review list). --}}
@props(['item'])
@if ($item->source === 'review')
    <span class="badge badge-ghost"><x-icon name="refresh" class="w-3 h-3" /> مرور</span>
@elseif ($item->activity->isLearn())
    <span class="badge badge-ghost"><x-icon name="play" class="w-3 h-3" /> یادگیری</span>
@else
    <span class="badge badge-ghost"><x-icon name="bulb" class="w-3 h-3" /> تمرین</span>
@endif
