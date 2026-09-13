@php
    $user = auth()->user();
    $levelOf = fn ($lesson) => $lesson->levelFor($user);
    $isLocked = fn ($lesson) => $lesson->prerequisites->contains(fn ($p) => \App\Models\MasteryRecord::LEVEL_INDEX[$p->levelFor($user)] < 2);
@endphp
<x-app-layout :title="$course->title">
    <div class="px-4 sm:px-8 pt-6">
        <div class="text-[12.5px] text-muted flex items-center gap-2">
            <a href="{{ route('courses.index') }}" class="text-muted">همه‌ی دوره‌ها</a><span class="text-faint">/</span><span dir="auto">{{ $course->title }}</span>
        </div>
        <div class="flex flex-col gap-2 mt-2">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="m-0 text-[26px] font-bold" dir="auto">{{ $course->title }}</h1>
                @if ($enrollment)
                    <span class="badge {{ $enrollment->status === 'active' ? 'badge-ok' : 'badge-ghost' }}"><span class="dot"></span>{{ $enrollment->statusLabel() }}</span>
                @endif
            </div>
            @if ($course->outcome_statement)
                <div class="max-w-3xl text-muted leading-[1.8]" dir="auto">
                    <span class="text-[12.5px] font-semibold text-faint">هدف نهایی · </span>{{ $course->outcome_statement }}
                </div>
            @endif
        </div>
    </div>

    <div class="page pt-5">
        <div class="flex flex-col gap-5">
            @if (session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif

            <div class="card">
                <div class="card-h">
                    <h3>درس‌ها <span class="text-faint font-normal">· {{ fa_num($course->lessons_count) }}</span></h3>
                    @if ($enrollment)
                        <x-course-progress :lessons="$course->lessons" :user="$user" class="w-64 hidden sm:block" />
                    @endif
                </div>
                <table class="table">
                    <thead><tr><th class="w-11">#</th><th>درس</th><th class="w-44">سطح</th><th class="w-24"></th></tr></thead>
                    <tbody>
                        @foreach ($course->lessons as $lesson)
                            @php $locked = $enrollment && $isLocked($lesson); @endphp
                            @if ($lesson->section && (! $loop->first) && $lesson->section !== $course->lessons[$loop->index - 1]->section)
                                <tr><td colspan="4" class="!py-2 bg-surface2 text-[12px] font-semibold text-muted" dir="auto">{{ $lesson->section }}</td></tr>
                            @elseif ($lesson->section && $loop->first)
                                <tr><td colspan="4" class="!py-2 bg-surface2 text-[12px] font-semibold text-muted" dir="auto">{{ $lesson->section }}</td></tr>
                            @endif
                            <tr>
                                <td class="num">{{ $lesson->order + 1 }}</td>
                                <td dir="auto">
                                    <a href="{{ route('lessons.show', $lesson) }}" class="font-medium {{ $locked ? 'text-muted' : 'text-ink' }} hover:text-accent">{{ $lesson->title }}</a>
                                    @if ($lesson->summary)
                                        <div class="text-[12.5px] text-muted">{{ $lesson->summary }}</div>
                                    @endif
                                    <div class="text-[12px] text-faint mt-0.5 flex items-center gap-2">
                                        <span>{{ fa_num($lesson->estimated_minutes) }} دقیقه</span>
                                        @if ($lesson->videos->isNotEmpty())<span>· <x-icon name="video" class="w-3 h-3 inline" /> ویدیو</span>@endif
                                        @if ($locked)<span class="flex items-center gap-1">· <x-icon name="lock" class="w-3 h-3" /> نیاز به {{ $lesson->prerequisites->pluck('title')->join('، ') }}</span>@endif
                                    </div>
                                </td>
                                <td><x-level-badge :level="$levelOf($lesson)" /></td>
                                <td class="text-end"><a href="{{ route('lessons.show', $lesson) }}" class="btn btn-sm">باز کن</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-5">
            @if (! $enrollment)
                <div class="card p-[18px]" style="border-color: color-mix(in srgb, var(--accent) 50%, transparent);">
                    <div class="text-[15px] font-semibold mb-1">این دوره رو بردار</div>
                    <div class="text-[13px] text-muted mb-4">بعدش زمان روزانه و اولویتش رو تنظیم می‌کنی و توی پلن میاد.</div>
                    <form method="POST" action="{{ route('courses.enroll', $course) }}">
                        @csrf
                        <x-primary-button class="w-full"><x-icon name="plus" class="w-4 h-4" /> برداشتن دوره</x-primary-button>
                    </form>
                </div>
            @else
                <div class="card">
                    <div class="card-h">
                        <h3>زمان‌بندی</h3>
                        <a href="{{ route('enrollments.edit', $enrollment) }}" class="iconbtn w-7 h-7" title="ویرایش"><x-icon name="gear" class="w-3.5 h-3.5" /></a>
                    </div>
                    <div class="px-[18px] py-1.5">
                        <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">اولویت</span><span class="font-semibold">{{ fa_num($enrollment->priority) }} از ۵</span></div>
                        <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">زمان روزانه</span><span class="font-semibold">{{ $enrollment->daily_time_minutes ? fa_num($enrollment->daily_time_minutes).' دقیقه' : '—' }}</span></div>
                        <div class="flex justify-between py-2"><span class="text-muted">ساعت ترجیحی</span><span class="font-semibold">{{ $enrollment->preferred_time ? fa_num(substr($enrollment->preferred_time, 0, 5)) : '—' }}</span></div>
                    </div>
                    @unless ($enrollment->daily_time_minutes)
                        <div class="px-[18px] pb-3 text-[12.5px] text-warn">بدون زمان روزانه، این دوره توی پلن نمیاد.</div>
                    @endunless
                </div>
            @endif

            @if ($course->source_note)
                <div class="card">
                    <div class="card-h"><h3>منابع</h3></div>
                    <div class="px-[18px] py-3 text-[13px] text-muted" dir="auto">{{ $course->source_note }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
