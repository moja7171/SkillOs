<x-app-layout title="خانه">
    <div class="px-4 sm:px-8 lg:px-12 py-8 sm:py-9">
        @if (session('status'))
            <div class="alert alert-ok mb-6">{{ session('status') }}</div>
        @endif

        <header class="flex items-baseline gap-3.5 mb-7">
            <h1 class="m-0 text-[30px] font-extrabold leading-tight">امروز</h1>
            <span class="text-[14px] text-muted">{{ fa_date(now(), 'l j F') }}</span>
        </header>

        <div class="flex flex-wrap gap-8 items-start">

            <div class="flex-[999_1_560px] min-w-0 flex flex-col gap-9">

                {{-- «دوره‌های من»: the course with the latest progress comes first (DECISIONS.md §70). --}}
                <section aria-label="دوره‌های من" class="flex flex-col gap-4">
                    <h2 class="m-0 text-[16px] font-bold">دوره‌های من</h2>

                    @forelse ($mine as $row)
                        @if ($loop->first)
                            <div class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-4">
                        @endif
                        @php $course = $row['course']; $color = course_color($course->id); @endphp
                        <a href="{{ route('courses.learn', $course) }}" class="card rounded-[18px] p-5 flex flex-col gap-3.5 text-ink shadow-sm hover:text-ink hover:border-line2">
                            <div class="flex items-center gap-3">
                                <span class="w-[42px] h-[42px] rounded-xl grid place-items-center shrink-0 text-[18px] font-extrabold" dir="auto"
                                      style="background: color-mix(in srgb, {{ $color }} 16%, transparent); color: {{ $color }};">{{ mb_substr($course->title, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <div class="font-bold text-[15px] truncate" dir="auto">{{ $course->title }}</div>
                                    <div class="text-[12.5px] text-muted">
                                        @if ($row['position'])درس {{ fa_num($row['position']) }} از {{ fa_num($course->lessons_count) }}@else{{ fa_num($course->lessons_count) }} درس@endif
                                    </div>
                                </div>
                            </div>
                            @if ($row['next'])
                                <div class="text-[13.5px]" dir="auto">{{ $row['next']->title }}</div>
                            @endif
                            <div class="flex items-center gap-2.5">
                                <div class="flex-1 h-1.5 rounded-full bg-surface2 overflow-hidden" role="progressbar" aria-label="پیشرفت دوره" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $row['percent'] }}">
                                    <div class="h-full rounded-full" style="width: {{ $row['percent'] }}%; background: {{ $color }};"></div>
                                </div>
                                <span class="text-[12.5px] text-muted">{{ fa_num($row['percent']) }}٪</span>
                            </div>
                        </a>
                        @if ($loop->last)
                            </div>
                        @endif
                    @empty
                        <div class="card rounded-[18px] p-6 text-[13.5px] text-muted">هنوز دوره‌ای برنداشتی. از پایین یکی رو انتخاب کن.</div>
                    @endforelse
                </section>

                @if ($others->isNotEmpty())
                    <section aria-label="بقیه‌ی دوره‌ها" class="flex flex-col gap-4">
                        <h2 class="m-0 text-[16px] font-bold">{{ $mine->isEmpty() ? 'دوره‌ها' : 'بقیه‌ی دوره‌ها' }} <span class="font-normal text-faint text-[13px]">· {{ fa_num($others->count()) }} دوره</span></h2>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(230px,1fr))] gap-3.5">
                            @foreach ($others as $course)
                                <a href="{{ route('courses.show', $course) }}" class="card rounded-2xl p-4 flex flex-col gap-2.5 min-h-[124px] text-ink shadow-sm hover:text-ink hover:border-line2">
                                    <span class="w-[34px] h-[34px] rounded-[10px] bg-surface2 text-muted grid place-items-center font-extrabold" dir="auto">{{ mb_substr($course->title, 0, 1) }}</span>
                                    <span class="font-semibold text-[14px] leading-[1.6] line-clamp-2" dir="auto">{{ $course->title }}</span>
                                    <span class="text-[12.5px] text-faint mt-auto">{{ fa_num($course->lessons_count) }} درس@if ($course->category) · {{ $course->category }}@endif</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="flex-[1_1_320px] max-w-[380px] w-full max-lg:max-w-none max-lg:order-first flex flex-col gap-4">

                {{-- Reviews are their own queue, not part of the lessons (DECISIONS.md §70). --}}
                <section aria-label="مرور امروز" class="card rounded-[22px] p-6 flex flex-col gap-[18px] shadow-sm">
                    @if ($dueCount > 0)
                        <div class="flex items-center gap-2 text-[14px] font-semibold text-muted">
                            <x-icon name="refresh" class="w-[18px] h-[18px] text-accent-text" /> مرور امروز
                        </div>
                        <div class="flex items-baseline gap-2.5">
                            <span class="text-[72px] font-extrabold leading-none text-accent-text">{{ fa_num($dueCount) }}</span>
                            <span class="text-[16px] font-semibold">مرور سررسیده</span>
                        </div>
                        <div class="flex flex-col gap-2 text-[13.5px]">
                            @foreach ($dueByCourse as $row)
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ course_color($row['course']->id) }};"></span>
                                    <span class="flex-1 min-w-0 truncate" dir="auto">{{ $row['course']->title }}</span>
                                    <span class="text-muted">{{ fa_num($row['count']) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <form method="POST" action="{{ route('reviews.start') }}">
                            @csrf
                            <x-primary-button class="w-full h-12 text-[15px] rounded-xl">شروع مرور</x-primary-button>
                        </form>
                    @else
                        <span class="w-12 h-12 rounded-[14px] grid place-items-center bg-ok text-white"><x-icon name="check" class="w-6 h-6" /></span>
                        <div>
                            <div class="text-[20px] font-extrabold">{{ $reviewsDoneToday > 0 ? 'مرور امروز تموم شد' : 'مروری سررسید نشده' }}</div>
                            <div class="text-[13.5px] text-muted">{{ $dueTomorrow > 0 ? 'فردا '.fa_num($dueTomorrow).' مرور منتظرته.' : 'هر درسی که تموم کنی، مرورش خودش میاد.' }}</div>
                        </div>
                    @endif
                </section>

                <section aria-label="استریک" class="card rounded-[22px] px-6 py-5 flex flex-col gap-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="w-[42px] h-[42px] rounded-xl grid place-items-center text-accent-text" style="background: color-mix(in srgb, var(--accent) 15%, transparent);"><x-icon name="flame" class="w-[22px] h-[22px]" /></span>
                        <div>
                            @if ($streakCount > 0)
                                <div class="font-extrabold text-[20px] leading-tight">{{ fa_num($streakCount) }} روز</div>
                                <div class="text-[12.5px] text-muted">پشت‌سرهم درس خوندی</div>
                            @else
                                <div class="font-bold text-[16px] leading-tight">استریک</div>
                                <div class="text-[12.5px] text-muted">امروز یه درس یا مرور بزن تا شروع بشه</div>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-between gap-1.5">
                        @foreach ($week as $day)
                            <div class="flex flex-col items-center gap-1.5 text-[12px] {{ $day['state'] === 'today' ? 'text-ink font-bold' : 'text-faint' }}">
                                <span @class([
                                    'w-7 h-7 rounded-full box-border',
                                    'bg-accent' => $day['state'] === 'done',
                                    'border-2 border-dashed border-accent' => $day['state'] === 'today',
                                    'bg-surface2' => in_array($day['state'], ['missed', 'future'], true),
                                ])></span>
                                {{ $day['label'] }}
                            </div>
                        @endforeach
                    </div>
                </section>

                @if ($weakSpotCount > 0)
                    <a href="{{ route('weak-spots') }}" class="text-[13px] text-muted px-1">{{ fa_num($weakSpotCount) }} نقطه‌ی ضعف ←</a>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
