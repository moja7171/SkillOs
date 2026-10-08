@php
    $user = auth()->user();
    $hasPlan = $queue->isNotEmpty();
    $planFinished = $hasPlan && $openCount === 0;
@endphp
<x-app-layout title="خانه">
    <div class="page-narrow flex flex-col gap-5">
        @if (session('status'))
            <div class="alert alert-ok">{{ session('status') }}</div>
        @endif

        {{-- Header: the day, and the streak as a small, quiet chip (DECISIONS.md §68). --}}
        <div class="flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <h1 class="text-[22px] font-bold leading-tight">امروز</h1>
                <div class="text-[12.5px] text-muted mt-0.5">{{ fa_date(now(), 'l j F') }}</div>
            </div>
            @if ($streakCount > 0)
                <span class="badge badge-warn shrink-0" title="{{ $recordedToday ? 'امروز ثبت شد' : 'امروز هنوز ثبت نشده' }}">
                    <x-icon name="flame" class="w-3.5 h-3.5" /> {{ fa_num($streakCount) }} روز @if ($recordedToday)<x-icon name="check" class="w-3 h-3" />@endif
                </span>
            @endif
        </div>

        {{-- Coming back after a gap: one line, no pressure. --}}
        @if ($daysSinceLastActivity !== null && $daysSinceLastActivity >= 2)
            <div class="card px-4 py-3 flex items-center gap-3" style="border-color: var(--line2);">
                <x-icon name="sparkle" class="w-[18px] h-[18px] text-muted shrink-0" />
                <span class="text-[13.5px]">
                    @if ($daysSinceLastActivity >= 14)
                        خوش برگشتی — امروز فقط با مرورها شروع کن، بقیه‌اش خودش میاد.
                    @else
                        {{ fa_num($daysSinceLastActivity) }} روزه نیومدی — یه مرور کوتاه امروز حالتو جا میاره.
                    @endif
                </span>
            </div>
        @endif

        {{-- Active courses that have no daily time never reach the plan: say so where the plan is. --}}
        @foreach ($unscheduled as $enrollment)
            <div class="alert alert-warn flex items-center gap-3 flex-wrap">
                <span class="flex-1 min-w-[200px]">برای «{{ $enrollment->course->title }}» زمان روزانه تعیین نکردی، برای همین توی برنامه‌ی امروز نیست.</span>
                <a href="{{ route('enrollments.edit', $enrollment) }}" class="btn btn-sm"><x-icon name="gear" class="w-3.5 h-3.5" /> تعیین زمان</a>
            </div>
        @endforeach

        @if ($enrollments->isEmpty())
            <div class="card p-8 text-center flex flex-col items-center gap-3">
                <div class="text-[18px] font-bold">اولین دوره‌ت رو انتخاب کن</div>
                <div class="text-muted text-[13.5px]">بعد از انتخاب دوره، هر روز همین‌جا می‌بینی چی مونده.</div>
                <a href="{{ route('courses.index') }}" class="btn btn-primary mt-1">دیدن دوره‌ها</a>
            </div>
        @elseif ($planFinished)
            {{-- Done for today --}}
            <div class="card p-6 flex flex-col gap-3">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0 text-[#06261a]" style="background: var(--ok);"><x-icon name="check" class="w-5 h-5" /></span>
                    <div>
                        <div class="text-[18px] font-bold">{{ $doneCount > 0 ? 'امروز تموم شد' : 'امروز رو کنار گذاشتی' }}</div>
                        <div class="text-[13.5px] text-muted">
                            @if ($tomorrowReviews > 0)
                                فردا {{ fa_num($tomorrowReviews) }} مرور منتظرته.
                            @else
                                {{ $doneCount > 0 ? 'کارِ امروزت انجام شد.' : 'مرورهایی که مونده، فردا هم سر جاشونه.' }}
                            @endif
                        </div>
                    </div>
                </div>
                @if ($continueCourse || $practiceBacklog['total'] > 0)
                    <div class="border-t border-line pt-3 flex flex-col gap-2">
                        <div class="text-[12.5px] text-muted font-semibold">اگه حال داری:</div>
                        <div class="flex flex-wrap gap-2">
                            @if ($continueCourse)
                                <a href="{{ route('courses.learn', $continueCourse) }}" class="btn btn-sm"><x-icon name="play" class="w-3.5 h-3.5" /> ادامه‌ی درس‌های {{ $continueCourse->title }}</a>
                            @endif
                            @if ($practiceBacklog['total'] > 0)
                                <a href="{{ $practiceBacklog['items']->first()['lesson']->url() }}#practices" class="btn btn-sm">
                                    <x-icon name="bulb" class="w-3.5 h-3.5" /> {{ fa_num($practiceBacklog['total']) }} درس تمرین ناتموم داره
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @elseif (! $hasPlan)
            <div class="card p-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1">
                    <div class="text-[16px] font-semibold mb-1">برای امروز چیزی نیست</div>
                    <div class="text-muted text-[13.5px]">
                        {{ $unscheduled->isNotEmpty() ? 'برای دوره‌هات زمان روزانه تعیین کن تا برنامه ساخته بشه.' : 'نه مروری سررسیده، نه درسی که الان نوبتش باشه.' }}
                    </div>
                </div>
                <a href="{{ route('courses.index') }}" class="btn btn-sm">دوره‌ها</a>
            </div>
        @else
            {{-- The plan: how much is left, one button, one queue. --}}
            <div class="card p-5 flex flex-col gap-4">
                <div>
                    <div class="text-[17px] font-semibold">
                        {{ fa_num($openCount) }} کار مونده · حدود {{ fa_num($remainingMinutes) }} دقیقه
                    </div>
                    <div class="flex items-center gap-3 mt-2.5">
                        <div class="flex-1 h-2 rounded-full bg-surface2 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $countedTotal }}" aria-valuenow="{{ $doneCount }}" aria-label="پیشرفت امروز">
                            <div class="h-full rounded-full" style="width: {{ $countedTotal > 0 ? round($doneCount / $countedTotal * 100) : 0 }}%; background: var(--accent);"></div>
                        </div>
                        <span class="text-[12.5px] text-muted shrink-0">{{ fa_num($doneCount) }} از {{ fa_num($countedTotal) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('session.start-planned', $next) }}">
                    @csrf
                    <x-primary-button class="w-full h-12 text-[15px]">
                        <x-icon name="play" class="w-4 h-4" /> {{ $doneCount > 0 ? 'ادامه‌ی امروز' : 'شروع امروز' }}
                    </x-primary-button>
                </form>
                <div class="text-[12.5px] text-muted -mt-2">اول: {{ $next->activity->title }}</div>
            </div>
        @endif

        {{-- The queue (hidden entirely when there is none) --}}
        @if ($hasPlan)
            <div class="card">
                @foreach ($queue as $item)
                    @php
                        $course = $item->activity->lesson->course;
                        $isNext = $next && $item->is($next);
                        $isOpen = $item->status === 'scheduled';
                    @endphp
                    <div class="flex items-center gap-3 ps-4 pe-2 py-2.5 border-b border-line last:border-b-0 {{ $isNext ? 'bg-hover' : '' }}">
                        @if ($item->status === 'completed')
                            <span class="w-5 h-5 rounded-md grid place-items-center shrink-0 text-[#06261a]" style="background: var(--ok);"><x-icon name="check" class="w-3 h-3" /></span>
                        @elseif ($item->status === 'skipped')
                            <span class="w-5 h-5 rounded-md border-[1.5px] border-line2 grid place-items-center shrink-0 text-faint"><x-icon name="x" class="w-3 h-3" /></span>
                        @else
                            <span class="w-5 h-5 rounded-md border-[1.5px] shrink-0 {{ $isNext ? 'border-accent' : 'border-line2' }}"></span>
                        @endif

                        @if ($isOpen)
                            <form method="POST" action="{{ route('session.start-planned', $item) }}" class="flex-1 min-w-0">
                                @csrf
                                <button type="submit" class="w-full text-start text-ink flex flex-col gap-0.5 py-0.5">
                        @else
                            <div class="flex-1 min-w-0 flex flex-col gap-0.5 py-0.5">
                        @endif
                                <span class="flex items-center gap-2 min-w-0">
                                    <x-plan-item-badge :item="$item" />
                                    <span class="truncate {{ $item->status === 'completed' ? 'text-muted line-through' : ($item->status === 'skipped' ? 'text-faint' : ($isNext ? 'font-semibold' : '')) }}">{{ $item->activity->title }}</span>
                                </span>
                                <span class="flex items-center gap-1.5 text-[12px] text-faint min-w-0">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: {{ course_color($course->id) }};"></span>
                                    <span class="truncate">{{ $course->title }}</span>
                                    <span class="shrink-0">· {{ fa_num($item->duration_minutes) }} دقیقه</span>
                                </span>
                        @if ($isOpen)
                                </button>
                            </form>
                        @else
                            </div>
                        @endif

                        @if ($isOpen)
                            <div class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                                <button type="button" class="iconbtn border-transparent" @click="open = ! open" :aria-expanded="open" aria-label="گزینه‌ها">
                                    <x-icon name="dots" class="w-4 h-4" />
                                </button>
                                <div x-show="open" x-cloak class="absolute end-0 top-full mt-1 z-10 card bg-surface p-1 min-w-[140px]">
                                    <form method="POST" action="{{ route('plan-items.skip', $item) }}">
                                        @csrf
                                        <button type="submit" class="w-full text-start px-3 py-2 rounded-md text-[13.5px] text-ink hover:bg-hover">نه امروز</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($extraDueReviews > 0 && $openCount > 0)
                <div class="text-[12.5px] text-muted px-1">{{ fa_num($extraDueReviews) }} مرور دیگه هم سررسیده؛ بعد از این‌ها نوبتشونه.</div>
            @endif
        @endif

        {{-- Everything that isn't «what's left today» lives one click away. --}}
        @if ($enrollments->isNotEmpty())
            <details class="card">
                <summary class="card-h cursor-pointer list-none border-b-0">
                    <h3>بیشتر</h3>
                    <span class="text-[12.5px] text-muted">دوره‌ها و آمار</span>
                </summary>

                <div class="px-[18px] py-3.5 border-y border-line flex items-center gap-8 flex-wrap">
                    <div>
                        <div class="text-[12.5px] text-muted mb-0.5">این هفته</div>
                        <div class="text-[15px] font-semibold">{{ fa_num($weeklyStats['count']) }} تمرین · {{ fa_num($weeklyStats['minutes']) }} دقیقه</div>
                    </div>
                    <div>
                        <div class="text-[12.5px] text-muted mb-0.5">این ماه</div>
                        <div class="text-[15px] font-semibold">{{ fa_num($monthlyStats['count']) }} تمرین · {{ fa_num($monthlyStats['minutes']) }} دقیقه</div>
                    </div>
                    @if ($weakSpotCount > 0)
                        <a href="{{ route('weak-spots') }}" class="ms-auto text-[12.5px] font-semibold">{{ fa_num($weakSpotCount) }} نقطه‌ی ضعف ←</a>
                    @endif
                </div>

                {{-- Lessons finished but practices still pending (DECISIONS.md §65) --}}
                @if ($practiceBacklog['total'] > 0)
                    <div class="px-[18px] pt-3 pb-1.5 bg-surface2 text-[12.5px] font-semibold">تمرین‌های عقب‌افتاده <span class="text-faint font-normal">· {{ fa_num($practiceBacklog['total']) }} درس</span></div>
                    @foreach ($practiceBacklog['items'] as $row)
                        <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line">
                            <span class="flex-1 min-w-0">
                                <span class="block truncate font-medium">{{ $row['lesson']->title }}</span>
                                <span class="block text-[12px] text-faint truncate">{{ $row['lesson']->course->title }}</span>
                            </span>
                            <span class="badge badge-ghost">{{ fa_num($row['passed']) }} از {{ fa_num($row['total']) }}</span>
                            <a href="{{ $row['lesson']->url() }}#practices" class="btn btn-sm"><x-icon name="play" class="w-3.5 h-3.5" /> تمرین‌ها</a>
                        </div>
                    @endforeach
                    @if ($practiceBacklog['total'] > $practiceBacklog['items']->count())
                        <div class="px-[18px] py-2.5 text-[12.5px] text-faint border-b border-line">و {{ fa_num($practiceBacklog['total'] - $practiceBacklog['items']->count()) }} درس دیگر.</div>
                    @endif
                @endif

                <div class="px-[18px] pt-3 pb-1.5 bg-surface2 flex items-center justify-between">
                    <span class="text-[12.5px] font-semibold">دوره‌های من</span>
                    <a href="{{ route('courses.index') }}" class="text-[12.5px]">+ دوره‌ی جدید</a>
                </div>
                @foreach ($enrollments as $enrollment)
                    @php $course = $enrollment->course; $color = course_color($course->id); @endphp
                    <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line last:border-b-0">
                        <span class="w-9 h-9 rounded-lg grid place-items-center shrink-0 text-[14px] font-bold"
                              style="background: color-mix(in srgb, {{ $color }} 20%, transparent); color: {{ $color }};">
                            {{ mb_substr($course->title, 0, 1) }}
                        </span>
                        <a href="{{ route('courses.show', $course) }}" class="flex-1 min-w-0 text-ink">
                            <span class="block truncate font-medium {{ $enrollment->status !== 'active' ? 'text-muted' : '' }}">{{ $course->title }}</span>
                            <span class="block text-[12px] {{ $enrollment->status === 'active' && ! $enrollment->daily_time_minutes ? 'text-warn' : 'text-faint' }}">
                                @if ($enrollment->status !== 'active')
                                    {{ $enrollment->statusLabel() }} ·
                                @endif
                                {{ $enrollment->daily_time_minutes ? fa_num($enrollment->daily_time_minutes).' دقیقه در روز' : 'بدون زمان روزانه' }} · {{ fa_num($course->lessons_count) }} درس
                            </span>
                        </a>
                        <a href="{{ route('courses.learn', $course) }}" class="btn btn-sm shrink-0"><x-icon name="play" class="w-3.5 h-3.5" /> ادامه</a>
                    </div>
                @endforeach
            </details>
        @endif
    </div>
</x-app-layout>
