@php
    $user = auth()->user();
    $course = $lesson->course;
    $level = $lesson->levelFor($user);
    $hasVideo = $lesson->videos->isNotEmpty();
    $blockedBy = $lesson->prerequisites->filter(fn ($p) => \App\Models\MasteryRecord::LEVEL_INDEX[$p->levelFor($user)] < 2);
@endphp
<x-app-layout :title="$lesson->title">
    <div class="px-4 sm:px-8 pt-6">
        <div class="text-[12.5px] text-muted flex items-center gap-2 flex-wrap">
            <a href="{{ route('courses.index') }}" class="text-muted">همه‌ی دوره‌ها</a><span class="text-faint">/</span>
            <a href="{{ route('courses.show', $course) }}" class="text-muted" dir="auto">{{ $course->title }}</a><span class="text-faint">/</span>
            <span dir="auto">درس {{ fa_num($lesson->order + 1) }}</span>
        </div>
        <div class="flex items-center gap-3 flex-wrap mt-2">
            <h1 class="m-0 text-[26px] font-bold" dir="auto">{{ $lesson->title }}</h1>
            <x-level-badge :level="$level" />
        </div>
        @if ($lesson->summary)
            <div class="max-w-3xl text-muted leading-[1.8] mt-1" dir="auto">{{ $lesson->summary }}</div>
        @endif
    </div>

    <div class="page pt-5">
        <div class="flex flex-col gap-5">
            @if ($enrollment && $blockedBy->isNotEmpty())
                <div class="alert alert-warn flex items-center gap-2">
                    <x-icon name="lock" class="w-4 h-4 shrink-0" />
                    <span>پلن این درس رو هنوز پیشنهاد نمی‌ده — اول {{ $blockedBy->pluck('title')->join('، ') }} باید حداقل «آشنا» بشه. ولی می‌تونی همین الان آزادانه بخونیش.</span>
                </div>
            @endif

            <x-lesson-content :lesson="$lesson" class="card" />

            <div class="card">
                <div class="card-h"><h3>تمرین‌ها <span class="text-faint font-normal">· {{ fa_num($lesson->practices->count()) }}</span></h3></div>
                @forelse ($lesson->practices as $practice)
                    <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line last:border-b-0">
                        <span class="badge badge-ghost">{{ $practice->formLabel() }}</span>
                        <span @class(['badge', 'badge-ok' => $practice->payload['difficulty'] === 'intro', 'badge-warn' => $practice->payload['difficulty'] === 'core', 'badge-bad' => $practice->payload['difficulty'] === 'stretch'])>{{ $practice->difficultyLabel() }}</span>
                        <span class="flex-1 font-medium" dir="auto">{{ $practice->title }}</span>
                        <span class="num">{{ $practice->estimated_minutes }}m</span>
                        <form method="POST" action="{{ route('session.start', $practice) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm"><x-icon name="play" class="w-3.5 h-3.5" /> شروع</button>
                        </form>
                    </div>
                @empty
                    <div class="px-[18px] py-3 text-[13px] text-faint">این درس هنوز تمرین نداره.</div>
                @endforelse
            </div>

            <div class="flex items-center justify-between">
                @if ($previous)
                    <a href="{{ route('lessons.show', $previous) }}" class="btn btn-ghost btn-sm" dir="auto">→ {{ $previous->title }}</a>
                @else<span></span>@endif
                @if ($next)
                    <a href="{{ route('lessons.show', $next) }}" class="btn btn-ghost btn-sm" dir="auto">{{ $next->title }} ←</a>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-5">
            @unless ($enrollment)
                <div class="card p-[18px]" style="border-color: color-mix(in srgb, var(--accent) 50%, transparent);">
                    <div class="text-[13px] text-muted mb-3">برای اینکه این دوره توی پلنت بیاد و پیشرفتت ثبت بشه، اول برش دار.</div>
                    <form method="POST" action="{{ route('courses.enroll', $course) }}">
                        @csrf
                        <x-primary-button class="w-full"><x-icon name="plus" class="w-4 h-4" /> برداشتن دوره</x-primary-button>
                    </form>
                </div>
            @endunless

            @php $record = $lesson->masteryRecords->first(); @endphp
            <div class="card">
                <div class="card-h"><h3>وضعیت من</h3></div>
                <div class="px-[18px] py-1.5">
                    <div class="flex justify-between items-center py-2 border-b border-line"><span class="text-muted">سطح</span><x-level-badge :level="$level" /></div>
                    <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">مرور بعدی</span><span class="font-semibold">{{ $record?->next_review_due_at ? fa_date($record->next_review_due_at, 'l j F') : '—' }}</span></div>
                    <div class="flex justify-between py-2"><span class="text-muted">آخرین فعالیت</span><span class="font-semibold">{{ $record?->last_evaluated_at ? fa_date($record->last_evaluated_at, 'j F') : '—' }}</span></div>
                </div>
            </div>

            <div class="card">
                <div class="card-h"><h3>پیش‌نیازها</h3></div>
                @if ($lesson->prerequisites->isEmpty())
                    <div class="px-[18px] py-3 text-[13px] text-faint">این درس پیش‌نیاز نداره.</div>
                @else
                    @foreach ($lesson->prerequisites as $prereq)
                        <a href="{{ route('lessons.show', $prereq) }}" class="flex items-center justify-between gap-3 px-[18px] py-2.5 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                            <span class="text-[13.5px]" dir="auto">{{ $prereq->title }}</span>
                            <x-level-badge :level="$prereq->levelFor($user)" />
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
