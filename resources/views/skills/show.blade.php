@php
    $item = $skill->learningItem;
    $level = $skill->masteryRecords->first()?->level ?? 'not_started';
    $text = $skill->resources->firstWhere('type', 'text');
    $video = $skill->resources->firstWhere('type', 'video');
    $learn = $skill->activities->firstWhere('type', 'learn');
    $practices = $skill->activities->where('type', 'practice');
    $hasContent = $skill->content_generated_at !== null;
@endphp
<x-app-layout :title="$skill->name">
    <div class="px-4 sm:px-8 pt-6">
        <div class="text-[12.5px] text-muted flex items-center gap-2 flex-wrap">
            <a href="{{ route('learning-items.index') }}" class="text-muted">یادگیری‌ها</a><span class="text-faint">/</span>
            <a href="{{ route('learning-items.show', $item) }}" class="text-muted" dir="auto">{{ $item->title }}</a><span class="text-faint">/</span>
            <span dir="auto">{{ $skill->name }}</span>
        </div>
        <div class="flex items-center gap-3 flex-wrap mt-2">
            <h1 class="m-0 text-[26px] font-bold" dir="auto">{{ $skill->name }}</h1>
            <x-level-badge :level="$level" />
        </div>
        @if ($skill->description)
            <div class="max-w-3xl text-muted leading-[1.8] mt-1" dir="auto">{{ $skill->description }}</div>
        @endif
    </div>

    <div class="page pt-5">
        <div class="flex flex-col gap-5">
            @if (session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-bad">{{ session('error') }}</div>
            @endif

            @unless ($hasContent)
                <div class="card p-10 text-center">
                    <div class="mx-auto mb-3 w-10 h-10 rounded-full grid place-items-center text-accent" style="background: color-mix(in srgb, var(--accent) 14%, transparent);"><x-icon name="sparkle" class="w-5 h-5" /></div>
                    <div class="text-[16px] font-semibold mb-1">این مهارت هنوز محتوا نداره</div>
                    <div class="text-muted mb-5">AI یه متن آموزشی و ۲ تا ۳ تمرین مخصوص همین مهارت می‌سازه. حدود نیم دقیقه طول می‌کشه.</div>
                    <form method="POST" action="{{ route('skills.generate-content', $skill) }}">
                        @csrf
                        <x-primary-button><x-icon name="sparkle" class="w-4 h-4" /> آماده‌سازی این مهارت</x-primary-button>
                    </form>
                </div>
            @else
                <div class="card">
                    <div class="card-h">
                        <h3 class="flex items-center gap-2"><x-icon name="text" class="w-4 h-4" /> متن آموزشی
                            @if ($text?->is_recommended)<span class="badge badge-warn">پیشنهادی</span>@endif
                        </h3>
                        @if ($learn)
                            <span class="text-[12.5px] text-faint flex items-center gap-1.5"><x-icon name="clock" class="w-3.5 h-3.5" /> حدود {{ fa_num($learn->estimated_minutes) }} دقیقه</span>
                        @endif
                    </div>
                    <div class="px-6 py-4 prose-fa" dir="auto">
                        {!! Str::markdown($text?->content ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                    @if (! empty($learn?->payload['key_points']))
                        <div class="mx-6 mb-6 rounded-lg bg-surface2 px-4 py-3">
                            <div class="text-[12.5px] font-semibold mb-1.5">نکته‌های کلیدی</div>
                            <ul class="m-0 ps-5 list-disc text-[13px] text-muted">
                                @foreach ($learn->payload['key_points'] as $point)
                                    <li dir="auto">{{ $point }}</li>
                                @endforeach
                            </ul>
                            @if (! empty($learn->payload['common_mistakes']))
                                <div class="text-[12.5px] font-semibold mt-3 mb-1.5 text-bad">اشتباهات رایج</div>
                                <ul class="m-0 ps-5 list-disc text-[13px] text-muted">
                                    @foreach ($learn->payload['common_mistakes'] as $mistake)
                                        <li dir="auto">{{ $mistake }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="card">
                    <div class="card-h"><h3>تمرین‌ها <span class="text-faint font-normal">· {{ fa_num($practices->count()) }}</span></h3></div>
                    @foreach ($practices as $practice)
                        <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line last:border-b-0">
                            <span class="badge badge-ghost">{{ $practice->formLabel() }}</span>
                            <span @class(['badge', 'badge-ok' => $practice->payload['difficulty'] === 'intro', 'badge-warn' => $practice->payload['difficulty'] === 'core', 'badge-bad' => $practice->payload['difficulty'] === 'stretch'])>{{ $practice->difficultyLabel() }}</span>
                            <span class="flex-1 font-medium" dir="auto">{{ $practice->title }}</span>
                            <span class="num">{{ $practice->estimated_minutes }}m</span>
                            <button type="button" class="btn btn-sm" disabled title="جلسه‌ی تمرین به‌زودی">شروع</button>
                        </div>
                    @endforeach
                </div>
            @endunless
        </div>

        <div class="flex flex-col gap-5">
            <div class="card">
                <div class="card-h"><h3>پیش‌نیازها</h3></div>
                @if ($skill->prerequisites->isEmpty())
                    <div class="px-[18px] py-3 text-[13px] text-faint">این مهارت پیش‌نیاز نداره.</div>
                @else
                    @foreach ($skill->prerequisites as $prereq)
                        <a href="{{ route('skills.show', $prereq) }}" class="flex items-center justify-between gap-3 px-[18px] py-2.5 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                            <span class="text-[13.5px]" dir="auto">{{ $prereq->name }}</span>
                            <x-level-badge :level="$prereq->masteryRecords->first()?->level ?? 'not_started'" />
                        </a>
                    @endforeach
                @endif
            </div>

            <div class="card">
                <div class="card-h"><h3>منابع</h3></div>
                <div class="flex items-center gap-3 px-[18px] py-3 border-b border-line">
                    <span class="iconbtn w-[30px] h-[30px]"><x-icon name="text" class="w-[15px] h-[15px]" /></span>
                    <div class="flex-1 min-w-0">
                        <div class="{{ $text ? '' : 'text-muted' }}">متن آموزشی AI</div>
                        <div class="text-[12.5px] text-faint">{{ $text ? 'آماده' : 'هنوز ساخته نشده' }}</div>
                    </div>
                    @if ($text?->is_recommended)<span class="badge badge-warn">پیشنهادی</span>@endif
                </div>
                <div class="px-[18px] py-3">
                    <div class="flex items-center gap-3">
                        <span class="iconbtn w-[30px] h-[30px]"><x-icon name="video" class="w-[15px] h-[15px]" /></span>
                        <div class="flex-1 min-w-0">
                            <div class="{{ $video ? '' : 'text-muted' }}">ویدیو</div>
                            @if ($video)
                                <a href="{{ $video->url }}" target="_blank" rel="noopener" class="text-[12.5px] block truncate" dir="ltr">{{ $video->url }}</a>
                            @else
                                <div class="text-[12.5px] text-faint">لینکی اضافه نشده</div>
                            @endif
                        </div>
                        @if ($video)
                            <span class="badge badge-warn">پیشنهادی</span>
                            <form method="POST" action="{{ route('skills.video.destroy', $skill) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="iconbtn w-7 h-7" title="حذف لینک"><x-icon name="x" class="w-3.5 h-3.5" /></button>
                            </form>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('skills.video.store', $skill) }}" class="mt-3 flex gap-2">
                        @csrf
                        <x-text-input name="url" type="url" dir="ltr" class="text-left text-[13px]" placeholder="https://youtube.com/…" :value="old('url')" required />
                        <button type="submit" class="btn btn-sm shrink-0">{{ $video ? 'تغییر' : 'افزودن' }}</button>
                    </form>
                    <x-input-error :messages="$errors->get('url')" />
                    <p class="help">اگه ویدیو بذاری، همون منبع پیشنهادی می‌شه؛ متن AI هم می‌مونه.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
