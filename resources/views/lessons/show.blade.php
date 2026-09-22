@php
    use App\Models\MasteryRecord;

    $user = auth()->user();
    $level = $lesson->levelFor($user);
    $blockedBy = $lesson->prerequisites->filter(fn ($p) => MasteryRecord::LEVEL_INDEX[$p->levelFor($user)] < 2);
    $record = $lesson->masteryRecords->first();

    // Review retention: how many spaced reviews of this lesson's practices the learner
    // has actually sat, and how many they got right without needing the reveal.
    $reviewAttempts = \App\Models\Attempt::where('user_id', $user->id)
        ->whereIn('activity_id', $lesson->practices->pluck('id'))
        ->where('evidence->source', 'review')
        ->whereNotIn('result_status', ['started'])
        ->get();
    $reviewCount = $reviewAttempts->count();
    $reviewSuccessCount = $reviewAttempts->whereIn('result_status', ['correct', 'correct_with_hint'])->count();

    // Curriculum grouped by section, in course order. The sidebar circle counts a lesson
    // as done at either signal: real mastery (>= «آشنا»), or the sticky "انجام دادم" mark —
    // the two are deliberately different things (DECISIONS.md), but both mean "filled dot" here.
    $sections = $course->lessons->groupBy(fn ($l) => $l->section ?? '');
    $levelIndexOrDone = fn ($l) => max(MasteryRecord::LEVEL_INDEX[$l->levelFor($user)], $doneLessonIds->contains($l->id) ? 2 : 0);
    $doneCount = $course->lessons->filter(fn ($l) => $levelIndexOrDone($l) >= 2)->count();
    $total = $course->lessons->count();
    $position = $course->lessons->search(fn ($l) => $l->id === $lesson->id) + 1;
@endphp
<x-app-layout :title="$lesson->title">
    <div x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false" class="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">

        {{-- ================= Lesson (right side in RTL) ================= --}}
        <div class="min-w-0">
            <div class="px-4 sm:px-6 pt-4 pb-3 border-b border-line bg-surface flex items-center gap-3">
                <button type="button" class="iconbtn lg:hidden shrink-0" @click="sidebar = true" title="فهرست درس‌ها" aria-label="باز کردن فهرست درس‌ها"><x-icon name="text" class="w-4 h-4" /></button>
                <div class="min-w-0 flex-1 leading-[1.4]">
                    <div class="text-[12px] text-muted truncate">
                        <a href="{{ route('courses.show', $course) }}" class="text-muted hover:text-ink">{{ $course->title }}</a>
                        <span class="text-faint mx-1">/</span>
                        <span>{{ $lesson->section }}</span>
                        <span class="text-faint mx-1">·</span>
                        <span>درس {{ fa_num($position) }} از {{ fa_num($total) }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="m-0 text-[20px] font-bold truncate">{{ $lesson->title }}</h1>
                        <x-level-badge :level="$level" />
                    </div>
                </div>
                <div class="hidden sm:flex items-center gap-1.5 shrink-0">
                    @if ($previous)
                        <a href="{{ $previous->url() }}" class="btn btn-ghost btn-sm" title="{{ $previous->title }}">قبلی</a>
                    @endif
                    @if ($next)
                        <a href="{{ $next->url() }}" class="btn btn-sm" title="{{ $next->title }}">بعدی <x-icon name="arrow" class="w-3.5 h-3.5" /></a>
                    @endif
                </div>
            </div>

            <div class="px-4 sm:px-6 py-5 flex flex-col gap-5">
                @if ($enrollment && $blockedBy->isNotEmpty())
                    <div class="alert alert-warn flex items-center gap-2">
                        <x-icon name="lock" class="w-4 h-4 shrink-0" />
                        <span>پلن این درس رو هنوز پیشنهاد نمی‌ده — اول {{ $blockedBy->pluck('title')->join('، ') }} باید حداقل «آشنا» بشه. ولی می‌تونی همین الان آزادانه بخونیش.</span>
                    </div>
                @endif

                @if ($lesson->summary)
                    <div class="text-muted leading-[1.8] max-w-3xl">{{ $lesson->summary }}</div>
                @endif

                <x-lesson-content :lesson="$lesson" class="card" />

                <div class="card">
                    <div class="card-h"><h3>تمرین‌ها <span class="text-faint font-normal">· {{ fa_num($lesson->practices->count()) }}</span></h3></div>
                    @forelse ($lesson->practices as $practice)
                        @php $status = $practiceStatus[$practice->id] ?? null; @endphp
                        <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line last:border-b-0">
                            <span class="badge badge-ghost">{{ $practice->formLabel() }}</span>
                            <span @class(['badge', 'badge-ok' => $practice->payload['difficulty'] === 'intro', 'badge-warn' => $practice->payload['difficulty'] === 'core', 'badge-bad' => $practice->payload['difficulty'] === 'stretch'])>{{ $practice->difficultyLabel() }}</span>
                            <span class="flex-1 min-w-0 truncate font-medium">{{ $practice->title }}</span>
                            @if ($status === 'solved')
                                <span class="badge badge-ok"><x-icon name="check" class="w-3 h-3" /> حل‌شده</span>
                            @elseif ($status === 'in_progress')
                                <span class="badge badge-warn">در حال انجام</span>
                            @elseif ($status === 'unsolved')
                                <span class="badge badge-bad">تلاش ناموفق</span>
                            @endif
                            <span class="num">{{ $practice->estimated_minutes }}m</span>
                            <a href="{{ route('session.open', $practice) }}" class="btn btn-sm">
                                <x-icon :name="$status === 'in_progress' ? 'play' : ($status ? 'refresh' : 'play')" class="w-3.5 h-3.5" />
                                {{ $status === 'in_progress' ? 'ادامه بده' : ($status ? 'مشاهده' : 'شروع') }}
                            </a>
                        </div>
                    @empty
                        <div class="px-[18px] py-3 text-[13px] text-faint">این درس هنوز تمرین نداره.</div>
                    @endforelse
                </div>

                <div class="card p-4 flex items-center justify-between gap-4 flex-wrap" @if ($lessonDone) style="border-color: var(--ok); background: color-mix(in srgb, var(--ok) 8%, transparent);" @endif
                     @unless ($lessonDone)
                     x-data="{
                         watchedVideoIds: {{ Js::from($lesson->watchedVideoIdsBy($user)->values()) }},
                         videoIds: {{ Js::from($lesson->videos->pluck('id')) }},
                         warn: false,
                         get allVideosWatched() { return this.videoIds.every(id => this.watchedVideoIds.includes(id)); },
                         submit(event) { if (! this.allVideosWatched) { event.preventDefault(); this.warn = true; } }
                     }"
                     @video-watched.window="if (! watchedVideoIds.includes($event.detail.videoId)) watchedVideoIds.push($event.detail.videoId)"
                     @endunless>
                    @if ($lessonDone)
                        <div class="flex items-center gap-2.5 text-[13.5px]">
                            <span class="w-7 h-7 rounded-full grid place-items-center shrink-0" style="background: var(--ok); color: #06261a;"><x-icon name="check" class="w-4 h-4" /></span>
                            <span class="font-semibold">این درس رو انجام دادی.</span>
                        </div>
                    @else
                        <div class="min-w-0">
                            <div class="text-[13.5px] text-muted">
                                @if ($practicesPassed)
                                    ویدیو/متن رو دیدی و تمرین‌ها رو درست جواب دادی؟ همینجا علامتش بزن.
                                @else
                                    اول همه‌ی تمرین‌های بالا رو درست جواب بده، بعد می‌تونی این درس رو انجام‌شده علامت بزنی.
                                @endif
                            </div>
                            <div x-show="warn" x-cloak x-transition class="error mt-1.5">هنوز همه‌ی ویدیوهای این درس رو کامل ندیدی — اول اون‌ها رو تا آخر ببین.</div>
                        </div>
                        <form method="POST" action="{{ route('lessons.mark-done', $lesson) }}" @submit="submit">
                            @csrf
                            <button type="submit" class="btn btn-primary" @disabled(! $practicesPassed)><x-icon name="check" class="w-4 h-4" /> انجام دادم</button>
                        </form>
                    @endif
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <div class="card">
                        <div class="card-h"><h3>وضعیت من</h3></div>
                        <div class="px-[18px] py-1.5 text-[13.5px]">
                            <div class="flex justify-between items-center py-2 border-b border-line"><span class="text-muted">سطح</span><x-level-badge :level="$level" /></div>
                            <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">مرور بعدی</span><span class="font-semibold">{{ $record?->next_review_due_at ? fa_date($record->next_review_due_at, 'l j F') : '—' }}</span></div>
                            @if ($reviewCount > 0)
                                <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">نتیجه‌ی مرورها</span><span class="font-semibold">{{ fa_num($reviewSuccessCount) }} از {{ fa_num($reviewCount) }} بار بلد بودی</span></div>
                            @endif
                            <div class="flex justify-between py-2"><span class="text-muted">آخرین فعالیت</span><span class="font-semibold">{{ $record?->last_evaluated_at ? fa_date($record->last_evaluated_at, 'j F') : '—' }}</span></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-h"><h3>فایل‌های درس</h3></div>
                        @forelse ($lesson->attachments ?? [] as $file)
                            @php $ext = strtolower(pathinfo(parse_url($file['url'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)); $downloadSrc = media_download_url($file['url']); @endphp
                            <a href="{{ $downloadSrc ?? media_url($file['url']) }}" target="_blank" rel="noopener" download class="flex items-center gap-3 px-[18px] py-2.5 border-b border-line last:border-b-0 text-ink hover:bg-hover"
                               @if ($downloadSrc) data-fallback-src="{{ media_url($file['url']) }}" @endif>
                                <span class="badge badge-ghost uppercase">{{ $ext ?: 'file' }}</span>
                                <span class="text-[13px] flex-1 min-w-0 truncate">{{ $file['title'] }}</span>
                            </a>
                        @empty
                            <div class="px-[18px] py-3 text-[13px] text-faint">فایلی برای این درس نیست.</div>
                        @endforelse
                    </div>

                    <div class="card">
                        <div class="card-h"><h3>پیش‌نیازها</h3></div>
                        @forelse ($lesson->prerequisites as $prereq)
                            <a href="{{ $prereq->url() }}" class="flex items-center justify-between gap-3 px-[18px] py-2.5 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                                <span class="text-[13px] min-w-0 truncate">{{ $prereq->title }}</span>
                                <x-level-badge :level="$prereq->levelFor($user)" />
                            </a>
                        @empty
                            <div class="px-[18px] py-3 text-[13px] text-faint">این درس پیش‌نیاز نداره.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    @if ($previous)
                        <a href="{{ $previous->url() }}" class="btn btn-ghost min-w-0"><span class="truncate">→ {{ $previous->title }}</span></a>
                    @else<span></span>@endif
                    @if ($next)
                        <a href="{{ $next->url() }}" class="btn btn-primary min-w-0"><span class="truncate">درس بعدی: {{ $next->title }}</span> <x-icon name="arrow" class="w-4 h-4 shrink-0" /></a>
                    @else
                        <a href="{{ route('courses.show', $course) }}" class="btn">این آخرین درس بود — برگرد به دوره</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================= Curriculum sidebar (left side in RTL) ================= --}}
        <div x-show="sidebar" x-cloak class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebar = false"></div>
        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 left-0 z-40 w-[85vw] max-w-[340px] transition-transform lg:transition-none lg:w-auto lg:max-w-none lg:sticky lg:top-14 lg:h-[calc(100vh-3.5rem)] bg-surface border-e border-line flex flex-col"
               x-init="$nextTick(() => { const cur = $el.querySelector('[data-current]'); const box = $refs.list; if (cur && box) { box.scrollTop = Math.max(0, cur.offsetTop - box.clientHeight / 2); } })">
            <div class="px-4 py-3.5 border-b border-line flex items-start gap-3">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('courses.show', $course) }}" class="block font-bold text-[14px] text-ink hover:text-accent truncate">{{ $course->title }}</a>
                    <div class="text-[12px] text-muted mt-0.5">{{ fa_num($doneCount) }} از {{ fa_num($total) }} درس حداقل «آشنا»</div>
                    <div class="levelbar mt-2 !gap-0 bg-surface2" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $doneCount }}" aria-label="پیشرفت دوره"><span style="width: {{ $total ? round($doneCount / $total * 100, 1) : 0 }}%; background: var(--ok);"></span></div>
                </div>
                <button type="button" class="iconbtn lg:hidden shrink-0" @click="sidebar = false" title="بستن فهرست درس‌ها" aria-label="بستن فهرست درس‌ها"><x-icon name="x" class="w-4 h-4" /></button>
            </div>

            @unless ($enrollment)
                <form method="POST" action="{{ route('courses.enroll', $course) }}" class="px-4 py-3 border-b border-line">
                    @csrf
                    <x-primary-button class="w-full !h-9 text-[13px]"><x-icon name="plus" class="w-4 h-4" /> برداشتن دوره</x-primary-button>
                    <div class="text-[12px] text-muted mt-2 leading-[1.7]">تا برنداری، پیشرفتت ثبت نمی‌شه و توی پلن نمیاد.</div>
                </form>
            @endunless

            <div x-ref="list" class="relative flex-1 min-h-0 overflow-y-auto">
                @foreach ($sections as $sectionTitle => $items)
                    @php
                        $isCurrentSection = $items->contains('id', $lesson->id);
                        $sectionDone = $items->filter(fn ($l) => $levelIndexOrDone($l) >= 2)->count();
                        $sectionMinutes = $items->sum('estimated_minutes');
                    @endphp
                    <div x-data="{ open: {{ ($isCurrentSection || $sectionTitle === '') ? 'true' : 'false' }} }" class="border-b border-line">
                        @if ($sectionTitle !== '')
                            <button type="button" @click="open = !open" class="w-full flex items-center gap-2 px-4 py-3 text-start bg-surface2 hover:bg-hover">
                                <div class="min-w-0 flex-1">
                                    <div class="text-[13px] font-semibold truncate">{{ $sectionTitle }}</div>
                                    <div class="text-[11.5px] text-faint mt-0.5">{{ fa_num($sectionDone) }} / {{ fa_num($items->count()) }} · {{ fa_num($sectionMinutes) }} دقیقه</div>
                                </div>
                                <x-icon name="chevron" class="w-3.5 h-3.5 text-faint transition-transform" ::class="open ? '-rotate-90' : 'rotate-90'" />
                            </button>
                        @endif
                        <div x-show="open">
                            @foreach ($items as $item)
                                @php $i = $levelIndexOrDone($item); $isCurrent = $item->id === $lesson->id; @endphp
                                <a href="{{ $item->url() }}" @if ($isCurrent) data-current aria-current="page" @endif
                                   class="flex items-start gap-2.5 px-4 py-2.5 text-[13px] leading-[1.5] border-s-2 {{ $isCurrent ? 'bg-surface2 border-accent text-ink' : 'border-transparent text-ink hover:bg-hover' }}">
                                    <span class="mt-[3px] w-4 h-4 rounded-full grid place-items-center shrink-0 text-[10px]"
                                          style="{{ $i >= 2 ? 'background: var(--l'.$i.'); color: #fff;' : 'border: 1.5px solid var(--l'.$i.'); color: var(--l'.$i.');' }}">
                                        @if ($i >= 2)<x-icon name="check" class="w-2.5 h-2.5" />@endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate {{ $isCurrent ? 'font-semibold' : '' }}"><span class="num me-1">{{ $item->order + 1 }}.</span>{{ $item->title }}</span>
                                        <span class="block text-[11.5px] text-faint flex items-center gap-1.5">
                                            @if ($item->videos_count)<x-icon name="video" class="w-3 h-3" />@else<x-icon name="text" class="w-3 h-3" />@endif
                                            {{ fa_num($item->estimated_minutes) }} دقیقه
                                            @if ($i === 1)<span class="text-l1">· در حال یادگیری</span>@endif
                                        </span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</x-app-layout>
