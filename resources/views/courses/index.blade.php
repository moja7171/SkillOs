<x-app-layout title="همه‌ی دوره‌ها">
    <div class="page-narrow max-w-4xl">
        <h1 class="m-0 text-[22px] font-bold mb-1">همه‌ی دوره‌ها</h1>
        <p class="text-muted mb-5">دوره‌ای که می‌خوای رو بردار؛ پیشرفتت فقط برای خودته.</p>

        @if ($courses->isEmpty())
            <div class="card p-10 text-center text-muted">هنوز دوره‌ای وارد نشده. <span class="mono text-[12px]">php artisan content:import &lt;slug&gt;</span></div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($courses as $course)
                @php $enrolled = $course->enrollments->isNotEmpty(); @endphp
                <a href="{{ route('courses.show', $course) }}" class="card p-[18px] flex flex-col gap-2 text-ink hover:text-ink hover:border-line2 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-[16px] font-semibold" dir="auto">{{ $course->title }}</div>
                        @if ($enrolled)
                            <span class="badge badge-ok shrink-0"><span class="dot"></span>برداشته‌شده</span>
                        @endif
                    </div>
                    <div class="text-[13px] text-muted line-clamp-3" dir="auto">{{ $course->description ?? $course->outcome_statement }}</div>
                    <div class="text-[12.5px] text-faint mt-auto pt-1">{{ fa_num($course->lessons_count) }} درس</div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
