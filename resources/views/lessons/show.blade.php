@php
    use App\Models\MasteryRecord;

    $user = auth()->user();
    $level = $lesson->levelFor($user);
    $blockedBy = $lesson->prerequisites->filter(fn ($p) => MasteryRecord::LEVEL_INDEX[$p->levelFor($user)] < 2);
    $record = $lesson->masteryRecords->first();

    // Curriculum grouped by section, in course order.
    $sections = $course->lessons->groupBy(fn ($l) => $l->section ?? '');
    $doneCount = $course->lessons->filter(fn ($l) => MasteryRecord::LEVEL_INDEX[$l->levelFor($user)] >= 2)->count();
    $total = $course->lessons->count();
    $position = $course->lessons->search(fn ($l) => $l->id === $lesson->id) + 1;
@endphp
<x-app-layout :title="$lesson->title">
    <div x-data="{ sidebar: false }" class="lg:grid lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">

        {{-- ================= Lesson (right side in RTL) ================= --}}
        <div class="min-w-0">
            <div class="px-4 sm:px-6 pt-4 pb-3 border-b border-line bg-surface flex items-center gap-3">
                <button type="button" class="iconbtn lg:hidden shrink-0" @click="sidebar = true" title="فهرست درس‌ها"><x-icon name="text" class="w-4 h-4" /></button>
                <div class="min-w-0 flex-1 leading-[1.4]">
                    <div class="text-[12px] text-muted truncate">
                        <a href="{{ route('courses.show', $course) }}" class="text-muted hover:text-ink" dir="auto">{{ $course->title }}</a>
                        <span class="text-faint mx-1">/</span>
                        <span dir="auto">{{ $lesson->section }}</span>
                        <span class="text-faint mx-1">·</span>
                        <span>درس {{ fa_num($position) }} از {{ fa_num($total) }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="m-0 text-[20px] font-bold truncate" dir="auto">{{ $lesson->title }}</h1>
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
                    <div class="text-muted leading-[1.8] max-w-3xl" dir="auto">{{ $lesson->summary }}</div>
                @endif

                <x-lesson-content :lesson="$lesson" class="card" />

                <div class="card">
                    <div class="card-h"><h3>تمرین‌ها <span class="text-faint font-normal">· {{ fa_num($lesson->practices->count()) }}</span></h3></div>
                    @forelse ($lesson->practices as $practice)
                        <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line last:border-b-0">
                            <span class="badge badge-ghost">{{ $practice->formLabel() }}</span>
                            <span @class(['badge', 'badge-ok' => $practice->payload['difficulty'] === 'intro', 'badge-warn' => $practice->payload['difficulty'] === 'core', 'badge-bad' => $practice->payload['difficulty'] === 'stretch'])>{{ $practice->difficultyLabel() }}</span>
                            <span class="flex-1 min-w-0 truncate font-medium" dir="auto">{{ $practice->title }}</span>
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

                <div class="grid gap-5 md:grid-cols-3">
                    <div class="card">
                        <div class="card-h"><h3>وضعیت من</h3></div>
                        <div class="px-[18px] py-1.5 text-[13.5px]">
                            <div class="flex justify-between items-center py-2 border-b border-line"><span class="text-muted">سطح</span><x-level-badge :level="$level" /></div>
                            <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">مرور بعدی</span><span class="font-semibold">{{ $record?->next_review_due_at ? fa_date($record->next_review_due_at, 'l j F') : '—' }}</span></div>
                            <div class="flex justify-between py-2"><span class="text-muted">آخرین فعالیت</span><span class="font-semibold">{{ $record?->last_evaluated_at ? fa_date($record->last_evaluated_at, 'j F') : '—' }}</span></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-h"><h3>فایل‌های درس</h3></div>
                        @forelse ($lesson->attachments ?? [] as $file)
                            @php $ext = strtolower(pathinfo(parse_url($file['url'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)); @endphp
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener" download class="flex items-center gap-3 px-[18px] py-2.5 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                                <span class="badge badge-ghost uppercase">{{ $ext ?: 'file' }}</span>
                                <span class="text-[13px] flex-1 min-w-0 truncate" dir="auto">{{ $file['title'] }}</span>
                            </a>
                        @empty
                            <div class="px-[18px] py-3 text-[13px] text-faint">فایلی برای این درس نیست.</div>
                        @endforelse
                    </div>

                    <div class="card">
                        <div class="card-h"><h3>پیش‌نیازها</h3></div>
                        @forelse ($lesson->prerequisites as $prereq)
                            <a href="{{ $prereq->url() }}" class="flex items-center justify-between gap-3 px-[18px] py-2.5 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                                <span class="text-[13px] min-w-0 truncate" dir="auto">{{ $prereq->title }}</span>
                                <x-level-badge :level="$prereq->levelFor($user)" />
                            </a>
                        @empty
                            <div class="px-[18px] py-3 text-[13px] text-faint">این درس پیش‌نیاز نداره.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    @if ($previous)
                        <a href="{{ $previous->url() }}" class="btn btn-ghost min-w-0"><span class="truncate" dir="auto">→ {{ $previous->title }}</span></a>
                    @else<span></span>@endif
                    @if ($next)
                        <a href="{{ $next->url() }}" class="btn btn-primary min-w-0"><span class="truncate" dir="auto">درس بعدی: {{ $next->title }}</span> <x-icon name="arrow" class="w-4 h-4 shrink-0" /></a>
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
                    <a href="{{ route('courses.show', $course) }}" class="block font-bold text-[14px] text-ink hover:text-accent truncate" dir="auto">{{ $course->title }}</a>
                    <div class="text-[12px] text-muted mt-0.5">{{ fa_num($doneCount) }} از {{ fa_num($total) }} درس حداقل «آشنا»</div>
                    <div class="levelbar mt-2 !gap-0 bg-surface2"><span style="width: {{ $total ? round($doneCount / $total * 100, 1) : 0 }}%; background: var(--ok);"></span></div>
                </div>
                <button type="button" class="iconbtn lg:hidden shrink-0" @click="sidebar = false"><x-icon name="x" class="w-4 h-4" /></button>
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
                        $sectionDone = $items->filter(fn ($l) => MasteryRecord::LEVEL_INDEX[$l->levelFor($user)] >= 2)->count();
                        $sectionMinutes = $items->sum('estimated_minutes');
                    @endphp
                    <div x-data="{ open: {{ $isCurrentSection ? 'true' : 'false' }} }" class="border-b border-line">
                        @if ($sectionTitle !== '')
                            <button type="button" @click="open = !open" class="w-full flex items-center gap-2 px-4 py-3 text-start bg-surface2 hover:bg-hover">
                                <div class="min-w-0 flex-1">
                                    <div class="text-[13px] font-semibold truncate" dir="auto">{{ $sectionTitle }}</div>
                                    <div class="text-[11.5px] text-faint mt-0.5">{{ fa_num($sectionDone) }} / {{ fa_num($items->count()) }} · {{ fa_num($sectionMinutes) }} دقیقه</div>
                                </div>
                                <x-icon name="chevron" class="w-3.5 h-3.5 text-faint transition-transform" ::class="open ? '-rotate-90' : 'rotate-90'" />
                            </button>
                        @endif
                        <div x-show="open">
                            @foreach ($items as $item)
                                @php $i = MasteryRecord::LEVEL_INDEX[$item->levelFor($user)]; $isCurrent = $item->id === $lesson->id; @endphp
                                <a href="{{ $item->url() }}" @if ($isCurrent) data-current aria-current="page" @endif
                                   class="flex items-start gap-2.5 px-4 py-2.5 text-[13px] leading-[1.5] border-s-2 {{ $isCurrent ? 'bg-surface2 border-accent text-ink' : 'border-transparent text-ink hover:bg-hover' }}">
                                    <span class="mt-[3px] w-4 h-4 rounded-full grid place-items-center shrink-0 text-[10px]"
                                          style="{{ $i >= 2 ? 'background: var(--l'.$i.'); color: #fff;' : 'border: 1.5px solid var(--l'.$i.'); color: var(--l'.$i.');' }}">
                                        @if ($i >= 2)<x-icon name="check" class="w-2.5 h-2.5" />@endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate {{ $isCurrent ? 'font-semibold' : '' }}" dir="auto"><span class="num me-1">{{ $item->order + 1 }}.</span>{{ $item->title }}</span>
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
