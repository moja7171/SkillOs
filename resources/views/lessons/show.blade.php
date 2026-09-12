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

            <div class="card" x-data="{ tab: '{{ $hasVideo ? 'video' : 'text' }}' }">
                <div class="flex items-center gap-1.5 px-4 py-2.5 border-b border-line">
                    @if ($hasVideo)
                        <button type="button" class="btn btn-sm" :class="tab === 'video' ? '' : 'btn-ghost'" @click="tab = 'video'">
                            <x-icon name="video" class="w-3.5 h-3.5" /> ویدیو <span class="badge badge-warn ms-1">پیشنهادی</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm" :class="tab === 'text' ? '' : 'btn-ghost'" @click="tab = 'text'">
                        <x-icon name="text" class="w-3.5 h-3.5" /> متن
                        @unless ($hasVideo)<span class="badge badge-warn ms-1">پیشنهادی</span>@endunless
                    </button>
                    <span class="ms-auto text-[12.5px] text-faint flex items-center gap-1.5"><x-icon name="clock" class="w-3.5 h-3.5" /> حدود {{ fa_num($lesson->estimated_minutes) }} دقیقه</span>
                </div>

                @if ($hasVideo)
                    <div x-show="tab === 'video'" class="p-4 flex flex-col gap-4">
                        @foreach ($lesson->videos as $video)
                            @php $embed = $video->embed(); @endphp
                            <div>
                                @if ($lesson->videos->count() > 1)
                                    <div class="text-[13px] font-semibold mb-2" dir="auto">{{ $video->title ?? 'قسمت '.fa_num($loop->iteration) }}</div>
                                @endif
                                @if ($embed['kind'] === 'file')
                                    <video controls preload="metadata" class="w-full rounded-lg bg-black aspect-video" src="{{ $embed['src'] }}"></video>
                                @elseif ($embed['kind'] === 'link')
                                    <a href="{{ $embed['src'] }}" target="_blank" rel="noopener" class="btn">باز کردن ویدیو در تب جدید</a>
                                @else
                                    <iframe class="w-full rounded-lg aspect-video bg-black" src="{{ $embed['src'] }}" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div x-show="tab === 'text'" x-cloak class="px-6 py-4 prose-fa" dir="auto">
                    {!! Str::markdown($lesson->content ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>

                @if (! empty($lesson->key_points) || ! empty($lesson->common_mistakes))
                    <div class="mx-4 mb-4 rounded-lg bg-surface2 px-4 py-3">
                        @if (! empty($lesson->key_points))
                            <div class="text-[12.5px] font-semibold mb-1.5">نکته‌های کلیدی</div>
                            <ul class="m-0 ps-5 list-disc text-[13px] text-muted">
                                @foreach ($lesson->key_points as $point)<li dir="auto">{{ $point }}</li>@endforeach
                            </ul>
                        @endif
                        @if (! empty($lesson->common_mistakes))
                            <div class="text-[12.5px] font-semibold mt-3 mb-1.5 text-bad">اشتباهات رایج</div>
                            <ul class="m-0 ps-5 list-disc text-[13px] text-muted">
                                @foreach ($lesson->common_mistakes as $mistake)<li dir="auto">{{ $mistake }}</li>@endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-h"><h3>تمرین‌ها <span class="text-faint font-normal">· {{ fa_num($lesson->practices->count()) }}</span></h3></div>
                @forelse ($lesson->practices as $practice)
                    <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line last:border-b-0">
                        <span class="badge badge-ghost">{{ $practice->formLabel() }}</span>
                        <span @class(['badge', 'badge-ok' => $practice->payload['difficulty'] === 'intro', 'badge-warn' => $practice->payload['difficulty'] === 'core', 'badge-bad' => $practice->payload['difficulty'] === 'stretch'])>{{ $practice->difficultyLabel() }}</span>
                        <span class="flex-1 font-medium" dir="auto">{{ $practice->title }}</span>
                        <span class="num">{{ $practice->estimated_minutes }}m</span>
                        <button type="button" class="btn btn-sm" disabled title="جلسه‌ی تمرین به‌زودی">شروع</button>
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
