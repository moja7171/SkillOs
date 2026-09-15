<x-app-layout title="همه‌ی دوره‌ها">
    <div class="page-narrow max-w-4xl">
        <h1 class="m-0 text-[22px] font-bold mb-1">همه‌ی دوره‌ها</h1>
        <p class="text-muted mb-5">دوره‌ای که می‌خوای رو بردار؛ پیشرفتت فقط برای خودته.</p>

        @if ($courses->isEmpty())
            <div class="card p-10 text-center text-muted">هنوز دوره‌ای وارد نشده. <span class="mono text-[12px]">php artisan content:import &lt;slug&gt;</span></div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($courses as $course)
                @php
                    $enrolled = $course->enrollments->isNotEmpty();
                    // Deterministic per-course hue (golden-angle spacing) so cards get a
                    // distinct visual identity without hand-picking a color per course.
                    $hue = ($course->id * 137) % 360;
                @endphp
                <a href="{{ route('courses.show', $course) }}" class="card p-[18px] flex flex-col gap-3 text-ink hover:text-ink hover:border-line2 transition">
                    <div class="flex items-start gap-3">
                        <span class="w-11 h-11 rounded-xl grid place-items-center shrink-0 text-[17px] font-bold"
                              style="background: color-mix(in srgb, hsl({{ $hue }} 65% 55%) 18%, transparent); color: hsl({{ $hue }} 65% 55%);">
                            {{ mb_substr($course->title, 0, 1) }}
                        </span>
                        <div class="flex-1 min-w-0 flex items-start justify-between gap-2">
                            <div class="text-[16px] font-semibold leading-[1.4]" dir="auto">{{ $course->title }}</div>
                            @if ($enrolled)
                                <span class="badge badge-ok shrink-0"><span class="dot"></span>برداشته‌شده</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-[13px] text-muted line-clamp-3 flex-1" dir="auto">{{ $course->description ?? $course->outcome_statement }}</div>
                    <div class="flex items-center gap-3 text-[12.5px] text-faint pt-2.5 border-t border-line">
                        <span>{{ fa_num($course->lessons_count) }} درس</span>
                        @if ($course->lessons_minutes_sum)
                            <span>· حدود {{ fa_num((int) round($course->lessons_minutes_sum / 60)) }} ساعت</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
