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
            <div>
                <a href="{{ route('courses.learn', $course) }}" class="btn btn-primary"><x-icon name="play" class="w-4 h-4" /> {{ $enrollment ? 'ادامه بده' : 'شروع کن' }}</a>
            </div>
        </div>
    </div>

    <div class="page pt-5">
        <div class="flex flex-col gap-5">
            @if (session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif

            @php
                // Group by section so long lesson tables can collapse per section and
                // jump-to-section works; a course authored without sections stays one flat list.
                $sections = $course->lessons->groupBy(fn ($lesson) => $lesson->section ?: '');
                $currentLesson = $course->lessons->first(fn ($lesson) => ! ($enrollment && $isLocked($lesson)) && \App\Models\MasteryRecord::LEVEL_INDEX[$levelOf($lesson)] < 2);
                $defaultOpenIndex = $currentLesson && $currentLesson->section
                    ? $sections->keys()->values()->search($currentLesson->section)
                    : 0;
                $openMap = $sections->keys()->values()->mapWithKeys(fn ($title, $i) => [(string) $i => $title === '' || $i === $defaultOpenIndex]);
            @endphp
            <div class="card" x-data="{ open: {{ \Illuminate\Support\Js::from($openMap) }} }">
                <div class="card-h">
                    <h3>درس‌ها <span class="text-faint font-normal">· {{ fa_num($course->lessons_count) }}</span></h3>
                    @if ($enrollment)
                        <x-course-progress :lessons="$course->lessons" :user="$user" class="w-64 hidden sm:block" />
                    @endif
                </div>
                @if ($sections->keys()->filter()->count() > 1)
                    <div class="px-[18px] py-2.5 border-b border-line flex flex-wrap gap-1.5">
                        @foreach ($sections as $title => $lessons)
                            @continue($title === '')
                            <button type="button" x-ref="jump-{{ $loop->index }}"
                                    @click="open[{{ $loop->index }}] = true; $nextTick(() => $refs['section-{{ $loop->index }}'].scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                                    class="badge badge-ghost hover:border-faint">
                                {{ $title }}
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th class="w-11">#</th><th>درس</th><th class="w-44">سطح</th><th class="w-24"></th></tr></thead>
                    @foreach ($sections as $title => $lessons)
                        <tbody x-ref="section-{{ $loop->index }}">
                            @if ($title !== '')
                                <tr class="cursor-pointer select-none" @click="open[{{ $loop->index }}] = !open[{{ $loop->index }}]">
                                    <td colspan="4" class="!py-2 bg-surface2 text-[12px] font-semibold text-muted">
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-icon name="chevron" class="w-3 h-3 transition-transform" ::class="open[{{ $loop->index }}] ? '-rotate-90' : 'rotate-90'" />
                                            {{ $title }}
                                            <span class="text-faint font-normal">· {{ fa_num($lessons->count()) }} درس</span>
                                        </span>
                                    </td>
                                </tr>
                            @endif
                            @foreach ($lessons as $lesson)
                                @php $locked = $enrollment && $isLocked($lesson); @endphp
                                <tr x-show="open[{{ $loop->parent->index }}]">
                                    <td class="num">{{ $lesson->order + 1 }}</td>
                                    <td>
                                        <a href="{{ $lesson->url() }}" class="font-medium {{ $locked ? 'text-muted' : 'text-ink' }} hover:text-accent">{{ $lesson->title }}</a>
                                        @if ($lesson->summary)
                                            <div class="text-[12.5px] text-muted">{{ $lesson->summary }}</div>
                                        @endif
                                        <div class="text-[12px] text-faint mt-0.5 flex items-center gap-2">
                                            <span>{{ fa_num($lesson->estimated_minutes) }} دقیقه</span>
                                            @if ($lesson->videos->isNotEmpty())<span>· <x-icon name="video" class="w-3 h-3 inline" /> ویدیو</span>@endif
                                            @if ($locked)<span class="flex items-center gap-1">· <x-icon name="lock" class="w-3 h-3" /> نیاز به {{ $lesson->prerequisites->pluck('title')->join('، ') }}</span>@endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1.5">
                                            <x-level-badge :level="$levelOf($lesson)" />
                                            @if ($seenLessonIds->contains($lesson->id))
                                                <span class="text-faint" title="ویدیو/متن این درس رو دیدی"><x-icon name="check" class="w-3.5 h-3.5" /></span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end"><a href="{{ $lesson->url() }}" class="btn btn-sm">باز کن</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
                </div>
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
                        <a href="{{ route('enrollments.edit', $enrollment) }}" class="iconbtn w-7 h-7" title="ویرایش" aria-label="ویرایش زمان‌بندی"><x-icon name="gear" class="w-3.5 h-3.5" /></a>
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
