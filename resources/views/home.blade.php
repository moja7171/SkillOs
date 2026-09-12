<x-app-layout title="خانه">
    <div class="page-narrow max-w-3xl">
        <div class="flex items-center justify-between mb-5">
            <h1 class="m-0 text-[22px] font-bold">دوره‌های من</h1>
            <a href="{{ route('courses.index') }}" class="btn btn-sm">همه‌ی دوره‌ها</a>
        </div>

        @if ($enrollments->isEmpty())
            <div class="card p-10 text-center">
                <div class="text-[16px] font-semibold mb-1">هنوز دوره‌ای برنداشتی</div>
                <div class="text-muted mb-5">از کاتالوگ یه دوره انتخاب کن، زمان روزانه‌ش رو تنظیم کن و شروع کن.</div>
                <a href="{{ route('courses.index') }}" class="btn btn-primary">دیدن دوره‌ها</a>
            </div>
        @endif

        <div class="flex flex-col gap-3">
            @foreach ($enrollments as $enrollment)
                @php $course = $enrollment->course; @endphp
                <a href="{{ route('courses.show', $course) }}" class="card p-[18px] block text-ink hover:text-ink hover:border-line2 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-[16px] font-semibold" dir="auto">{{ $course->title }}</div>
                            <div class="text-[13px] text-muted mt-1 line-clamp-2" dir="auto">{{ $course->outcome_statement }}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                            <span class="badge {{ $enrollment->status === 'active' ? 'badge-ok' : 'badge-ghost' }}"><span class="dot"></span>{{ $enrollment->statusLabel() }}</span>
                            @if ($enrollment->daily_time_minutes)
                                <span class="badge badge-ghost">{{ fa_num($enrollment->daily_time_minutes) }} دقیقه در روز</span>
                            @else
                                <span class="badge badge-warn">بدون زمان روزانه</span>
                            @endif
                        </div>
                    </div>
                    <x-course-progress :lessons="$course->lessons" :user="auth()->user()" class="mt-3" />
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
