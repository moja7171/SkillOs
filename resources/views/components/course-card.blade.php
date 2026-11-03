{{-- Course box shared by the catalog and Home (cover, title, body, footer). $progress = ['percent', 'position', 'next'] for an enrolled course on Home. --}}
@props(['course', 'href', 'enrolled' => false, 'step' => null, 'stepTitle' => null, 'progress' => null, 'canEnroll' => false])
<div class="card overflow-hidden flex flex-col">
    <a href="{{ $href }}" class="flex-1 flex flex-col text-ink hover:text-ink">
        <div class="relative aspect-[16/9] shrink-0">
            <x-course-cover :course="$course" />
            <div class="absolute inset-x-2 top-2 flex items-start justify-between gap-2">
                @if ($step)
                    <span class="badge bg-surface/90 backdrop-blur-sm shadow-sm" title="{{ $stepTitle }}">قدم {{ fa_num($step) }}</span>
                @else
                    <span></span>
                @endif
                @if ($enrolled)
                    <span class="badge badge-ok bg-surface/90 backdrop-blur-sm shadow-sm"><span class="dot"></span>برداشته‌شده</span>
                @endif
            </div>
        </div>
        <div class="p-3.5 pb-2 flex-1 flex flex-col gap-1.5">
            <div class="text-[14px] font-semibold leading-[1.4] line-clamp-2" dir="auto">{{ $course->title }}</div>
            @if ($progress)
                <div class="text-[12px] text-muted line-clamp-1" dir="auto">
                    @if ($progress['position'])درس {{ fa_num($progress['position']) }} از {{ fa_num($course->lessons_count) }}@if ($progress['next']) · {{ $progress['next']->title }}@endif@else{{ fa_num($course->lessons_count) }} درس@endif
                </div>
                <div class="flex items-center gap-2 mt-auto pt-1">
                    <div class="flex-1 h-1.5 rounded-full bg-surface2 overflow-hidden" role="progressbar" aria-label="پیشرفت دوره" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['percent'] }}">
                        <div class="h-full rounded-full" style="width: {{ $progress['percent'] }}%; background: {{ course_color($course->id) }};"></div>
                    </div>
                    <span class="text-[12px] text-muted">{{ fa_num($progress['percent']) }}٪</span>
                </div>
            @else
                <div class="text-[12px] text-muted line-clamp-2">{{ $course->description ?? $course->outcome_statement }}</div>
            @endif
        </div>
    </a>
    <div class="flex items-center justify-between gap-2 px-3.5 pb-3.5 pt-2 mt-auto border-t border-line">
        <div class="flex items-center gap-2 text-[11.5px] text-faint">
            <span>{{ fa_num($course->lessons_count) }} درس</span>
            @if ($course->lessons_minutes_sum)
                <span>· {{ fa_num((int) round($course->lessons_minutes_sum / 60)) }} ساعت</span>
            @endif
        </div>
        @if ($canEnroll)
            <form method="POST" action="{{ route('courses.enroll', $course) }}">
                @csrf
                <button type="submit" class="btn btn-sm"><x-icon name="plus" class="w-3 h-3" /> برداشتن</button>
            </form>
        @elseif ($progress)
            <a href="{{ $href }}" class="btn btn-sm btn-primary"><x-icon name="play" class="w-3 h-3" /> ادامه</a>
        @endif
    </div>
</div>
