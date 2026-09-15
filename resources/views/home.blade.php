@php $user = auth()->user(); @endphp
<x-app-layout title="خانه">
    <div class="page">
        <div class="flex flex-col gap-5">
            @if (session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif

            {{-- Streak + today's progress: the motivational centerpiece (DECISIONS §21).
                 The quick-review shortcut lives in the same card so it reads as part of
                 this flow instead of floating alone above the "continue learning" card. --}}
            @if ($streakCount > 0 || $plannedMinutes > 0 || $quickReview)
                <div class="card px-5 py-4 flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-4">
                    @if ($streakCount > 0)
                        <div class="flex items-center gap-2.5 shrink-0">
                            <span class="w-10 h-10 rounded-xl grid place-items-center shrink-0" style="background: color-mix(in srgb, var(--warn) 16%, transparent);">
                                <x-icon name="flame" class="w-5 h-5 text-warn" />
                            </span>
                            <div class="leading-tight">
                                <div class="text-[19px] font-bold text-warn">{{ fa_num($streakCount) }} روز</div>
                                <div class="text-[11.5px] text-muted">پشت‌سرهم</div>
                            </div>
                        </div>
                    @endif
                    @if ($plannedMinutes > 0)
                        <div class="flex items-center gap-3 flex-1 min-w-[160px]">
                            <div class="flex-1 h-2.5 rounded-full bg-surface2 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $plannedMinutes }}" aria-valuenow="{{ min($plannedMinutes, $doneMinutes) }}" aria-label="پیشرفت امروز">
                                <div class="h-full rounded-full" style="width: {{ min(100, round($doneMinutes / $plannedMinutes * 100)) }}%; background: var(--accent);"></div>
                            </div>
                            <span class="text-[12.5px] text-muted shrink-0">{{ fa_num($doneMinutes) }}/{{ fa_num($plannedMinutes) }} دقیقه‌ی امروز</span>
                        </div>
                    @endif
                    @if ($quickReview)
                        <form method="POST" action="{{ route('session.start-planned', $quickReview) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn btn-sm">
                                <x-icon name="refresh" class="w-3.5 h-3.5" /> فقط یه مرور سریع ({{ fa_num($quickReview->duration_minutes) }} دقیقه)
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Gentle nudge after a short gap --}}
            @if ($daysSinceLastActivity !== null && $daysSinceLastActivity >= 2 && $daysSinceLastActivity < 14)
                <div class="card px-4 py-3 flex items-center gap-3" style="border-color: var(--line2);">
                    <x-icon name="sparkle" class="w-[18px] h-[18px] text-muted shrink-0" />
                    <span class="text-[13.5px]">{{ fa_num($daysSinceLastActivity) }} روزه نیومدی — یه مرور کوتاه امروز حالتو جا میاره.</span>
                </div>
            @endif

            {{-- Continue Learning --}}
            @if ($primary)
                <div class="card" style="border-color: var(--line2);">
                    <div class="p-6 flex flex-col lg:flex-row gap-6 items-start">
                        <div class="flex flex-col gap-2.5 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="badge badge-warn"><span class="dot"></span>ادامه‌ی یادگیری</span>
                                <x-plan-item-badge :item="$primary" />
                                @unless ($primary->activity->isLearn())<span class="badge badge-ghost">{{ $primary->activity->formLabel() }}</span>@endunless
                            </div>
                            <div class="text-[22px] font-bold leading-[1.4]" dir="auto">{{ $primary->activity->title }}</div>
                            <div class="text-muted" dir="auto">{{ $primary->activity->lesson->course->title }} &nbsp;·&nbsp; {{ $primary->activity->lesson->title }} &nbsp;·&nbsp; <span class="text-faint">دلیل:</span> {{ $primary->reason }}</div>
                            <div class="flex items-center gap-4 mt-1.5">
                                <form method="POST" action="{{ route('session.start-planned', $primary) }}">
                                    @csrf
                                    <x-primary-button><x-icon name="play" class="w-4 h-4" /> شروع کن</x-primary-button>
                                </form>
                                <span class="text-[12.5px] text-muted flex items-center gap-1.5"><x-icon name="clock" class="w-[15px] h-[15px]" /> حدود {{ fa_num($primary->duration_minutes) }} دقیقه</span>
                            </div>
                        </div>

                        @if ($alternatives->isNotEmpty())
                            <div class="flex flex-col gap-2 w-full lg:w-[340px] shrink-0">
                                <div class="text-[12.5px] text-muted font-semibold">یا به‌جاش:</div>
                                @foreach ($alternatives as $alt)
                                    @php $isItem = $alt instanceof \App\Models\PlanItem; $act = $isItem ? $alt->activity : $alt; @endphp
                                    <form method="POST" action="{{ $isItem ? route('session.start-planned', $alt) : route('session.start', $act) }}">
                                        @csrf
                                        <button type="submit" class="w-full card bg-surface2 px-3.5 py-2.5 flex items-center gap-2.5 text-start text-ink hover:border-line2">
                                            @if ($isItem)<x-plan-item-badge :item="$alt" />@else<span class="badge badge-ghost">آزاد</span>@endif
                                            <span class="flex-1 min-w-0 truncate" dir="auto">{{ $act->title }} <span class="text-faint">· {{ $act->lesson->course->title }}</span></span>
                                            <span class="text-faint text-[12.5px] shrink-0">{{ fa_num($act->estimated_minutes) }} دقیقه</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($enrollments->where('status', 'active')->isNotEmpty())
                <div class="card p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex-1">
                        <div class="text-[16px] font-semibold mb-1">{{ $todayByEnrollment->isNotEmpty() ? 'پلن امروز تموم شد' : 'هنوز چیزی برای امروز نیست' }}</div>
                        <div class="text-muted text-[13.5px]">
                            {{ $todayByEnrollment->isNotEmpty() ? 'می‌تونی از صفحه‌ی هر دوره آزادانه تمرین کنی.' : 'برای دوره‌هات زمان روزانه تعیین کن تا پلن ساخته بشه.' }}
                        </div>
                    </div>
                    <a href="{{ route('courses.index') }}" class="btn btn-sm">دوره‌ها</a>
                </div>
            @endif

            {{-- Today --}}
            @if ($todayByEnrollment->isNotEmpty())
                <div class="card">
                    <div class="card-h">
                        <h3>امروز — {{ fa_date(now(), 'l j F') }}</h3>
                        <span class="text-[12.5px] text-muted">{{ fa_num($todayByEnrollment->flatten()->where('status', 'completed')->count()) }} از {{ fa_num($todayByEnrollment->flatten()->count()) }} انجام شده · {{ fa_num($doneMinutes) }} از {{ fa_num($plannedMinutes) }} دقیقه</span>
                    </div>
                    @foreach ($todayByEnrollment as $items)
                        @php $courseTitle = $items->first()->activity->lesson->course->title; @endphp
                        <div class="px-[18px] pt-3 pb-1.5 flex items-center gap-2 bg-surface2">
                            <span class="text-[12.5px] font-semibold" dir="auto">{{ $courseTitle }}</span>
                            <span class="text-faint text-[12px]">{{ fa_num($items->where('status', 'completed')->count()) }} از {{ fa_num($items->count()) }}</span>
                        </div>
                        @foreach ($items as $item)
                            <div class="flex items-center gap-3.5 px-[18px] py-3 border-b border-line last:border-b-0 {{ $primary && $item->is($primary) ? 'bg-hover' : '' }}">
                                @if ($item->status === 'completed')
                                    <span class="w-5 h-5 rounded-md grid place-items-center shrink-0 text-[#06261a]" style="background: var(--ok);"><x-icon name="check" class="w-3 h-3" /></span>
                                @elseif ($item->status === 'skipped')
                                    <span class="w-5 h-5 rounded-md border-[1.5px] border-line2 grid place-items-center shrink-0 text-faint"><x-icon name="x" class="w-3 h-3" /></span>
                                @else
                                    <span class="w-5 h-5 rounded-md border-[1.5px] shrink-0 {{ $primary && $item->is($primary) ? 'border-accent' : 'border-line2' }}"></span>
                                @endif
                                <x-plan-item-badge :item="$item" />
                                <span class="flex-1 min-w-0 truncate {{ $item->status === 'completed' ? 'text-muted line-through' : ($primary && $item->is($primary) ? 'font-semibold' : '') }}" dir="auto">{{ $item->activity->title }}</span>
                                <span class="num">{{ $item->duration_minutes }}m</span>
                                @if ($item->status === 'scheduled')
                                    <form method="POST" action="{{ route('plan-items.skip', $item) }}">
                                        @csrf
                                        <button type="submit" class="iconbtn w-8 h-8" title="نه امروز" aria-label="نه امروز"><x-icon name="x" class="w-3.5 h-3.5" /></button>
                                    </form>
                                    <form method="POST" action="{{ route('session.start-planned', $item) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">شروع</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif

            {{-- Weekly/monthly recap --}}
            @if ($weeklyStats['count'] > 0 || $monthlyStats['count'] > 0)
                <div class="card">
                    <div class="card-h"><h3>خلاصه‌ی فعالیت</h3></div>
                    <div class="px-[18px] py-3.5 flex items-center gap-8 flex-wrap">
                        <div>
                            <div class="text-[12.5px] text-muted mb-0.5">این هفته</div>
                            <div class="text-[15px] font-semibold">{{ fa_num($weeklyStats['count']) }} تمرین · {{ fa_num($weeklyStats['minutes']) }} دقیقه</div>
                        </div>
                        <div>
                            <div class="text-[12.5px] text-muted mb-0.5">این ماه</div>
                            <div class="text-[15px] font-semibold">{{ fa_num($monthlyStats['count']) }} تمرین · {{ fa_num($monthlyStats['minutes']) }} دقیقه</div>
                        </div>
                        @if ($weakSpotCount > 0)
                            <a href="{{ route('weak-spots') }}" class="ms-auto text-[12.5px] text-bad font-semibold hover:opacity-80">
                                {{ fa_num($weakSpotCount) }} نقطه‌ی ضعف ←
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- My courses --}}
        <div class="flex flex-col gap-5">
            <div class="card">
                <div class="card-h"><h3>دوره‌های من</h3><a href="{{ route('courses.index') }}" class="text-[12.5px]">+ دوره‌ی جدید</a></div>
                @if ($enrollments->isEmpty())
                    <div class="px-[18px] py-6 text-center">
                        <div class="text-muted text-[13.5px] mb-3">هنوز دوره‌ای برنداشتی.</div>
                        <a href="{{ route('courses.index') }}" class="btn btn-primary btn-sm">دیدن دوره‌ها</a>
                    </div>
                @endif
                <div class="p-3 flex flex-col gap-2.5">
                    @foreach ($enrollments as $enrollment)
                        @php $course = $enrollment->course; $color = course_color($course->id); @endphp
                        {{-- Each enrollment is its own bg-surface2 chip (same nesting pattern as the
                             "یا به‌جاش" alternatives list on this page) instead of a flat, hairline-
                             separated row — clearer separation when there's more than one or two. --}}
                        <div class="card bg-surface2">
                            <a href="{{ route('courses.show', $course) }}" class="flex items-start gap-3 p-3.5 text-ink hover:bg-hover">
                                <span class="w-10 h-10 rounded-lg grid place-items-center shrink-0 text-[15px] font-bold"
                                      style="background: color-mix(in srgb, {{ $color }} 20%, transparent); color: {{ $color }};">
                                    {{ mb_substr($course->title, 0, 1) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-semibold truncate text-right {{ $enrollment->status !== 'active' ? 'text-muted' : '' }}" dir="auto">{{ $course->title }}</span>
                                        @if ($enrollment->status !== 'active')
                                            <span class="badge badge-ghost shrink-0">{{ $enrollment->statusLabel() }}</span>
                                        @else
                                            <span class="badge badge-ghost shrink-0">اولویت {{ fa_num($enrollment->priority) }}</span>
                                        @endif
                                    </div>
                                    <div class="text-[12.5px] {{ $enrollment->daily_time_minutes ? 'text-muted' : 'text-warn' }} mt-0.5 mb-2">
                                        {{ $enrollment->daily_time_minutes ? fa_num($enrollment->daily_time_minutes).' دقیقه در روز' : 'بدون زمان روزانه — توی پلن نمیاد' }} · {{ fa_num($course->lessons->count()) }} درس
                                    </div>
                                    <x-course-progress :lessons="$course->lessons" :user="$user" />
                                </div>
                            </a>
                            <div class="px-3.5 pb-3.5 pt-1 ps-[3.25rem]">
                                <a href="{{ route('courses.learn', $course) }}" class="btn btn-sm"><x-icon name="play" class="w-3.5 h-3.5" /> ادامه‌ی درس‌ها</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
