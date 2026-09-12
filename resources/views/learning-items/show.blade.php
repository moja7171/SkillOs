@php
    $draft = $learningItem->design_draft;
    $draftSkillsByKey = collect($draft['skills'] ?? [])->keyBy('key');
    $levelOf = fn ($skill) => $skill->masteryRecords->first()?->level ?? 'not_started';
    $levelCounts = $learningItem->skills->countBy(fn ($s) => \App\Models\MasteryRecord::LEVEL_INDEX[$levelOf($s)])->all();
@endphp
<x-app-layout :title="$learningItem->title">
    <div class="px-4 sm:px-8 pt-6">
        <div class="text-[12.5px] text-muted flex items-center gap-2">
            <a href="{{ route('learning-items.index') }}" class="text-muted">یادگیری‌ها</a><span class="text-faint">/</span><span>{{ $learningItem->title }}</span>
        </div>
        <div class="flex flex-col gap-2 mt-2">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="m-0 text-[26px] font-bold" dir="auto">{{ $learningItem->title }}</h1>
                @if ($learningItem->isDesignApproved())
                    <span class="badge {{ $learningItem->status === 'active' ? 'badge-ok' : 'badge-ghost' }}"><span class="dot"></span>{{ $learningItem->statusLabel() }}</span>
                @else
                    <span class="badge {{ $learningItem->design_status === 'pending_review' ? 'badge-warn' : 'badge-ghost' }}">{{ $learningItem->designStatusLabel() }}</span>
                @endif
            </div>
            @if ($learningItem->outcome_statement)
                <div class="max-w-3xl text-muted leading-[1.8]" dir="auto">
                    <span class="text-[12.5px] font-semibold text-faint">هدف نهایی · </span>{{ $learningItem->outcome_statement }}
                </div>
            @endif
        </div>
    </div>

    <div class="page pt-5">
        <div class="flex flex-col gap-5">
            @if (session('status'))
                <div class="alert alert-ok">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-bad">{{ session('error') }}</div>
            @endif

            @if ($learningItem->design_status === 'draft')
                <div class="card p-10 text-center">
                    <div class="mx-auto mb-3 w-10 h-10 rounded-full grid place-items-center text-accent" style="background: color-mix(in srgb, var(--accent) 14%, transparent);"><x-icon name="sparkle" class="w-5 h-5" /></div>
                    <div class="text-[16px] font-semibold mb-1">هنوز طرحی برای این موضوع نیست</div>
                    <div class="text-muted mb-5">AI یه هدف نهایی و مهارت‌های لازم رو پیشنهاد می‌ده. تا تأیید نکنی هیچی فعال نمی‌شه.</div>
                    <form method="POST" action="{{ route('learning-items.generate-design', $learningItem) }}">
                        @csrf
                        <x-primary-button><x-icon name="sparkle" class="w-4 h-4" /> ساختن طرح با AI</x-primary-button>
                    </form>
                </div>
            @endif

            @if ($learningItem->design_status === 'pending_review')
                <div class="card" style="border-color: color-mix(in srgb, var(--warn) 50%, transparent);">
                    <div class="card-h">
                        <h3 class="text-warn">منتظر تأیید توئه</h3>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('learning-items.generate-design', $learningItem) }}">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm"><x-icon name="refresh" class="w-4 h-4" /> دوباره بساز</button>
                            </form>
                            <form method="POST" action="{{ route('learning-items.approve-design', $learningItem) }}">
                                @csrf
                                <x-primary-button class="btn-sm"><x-icon name="check" class="w-4 h-4" /> تأیید</x-primary-button>
                            </form>
                        </div>
                    </div>
                    <div class="p-[18px] border-b border-line">
                        <div class="text-[12px] font-semibold text-muted mb-1">هدف نهایی پیشنهادی</div>
                        <div class="leading-[1.8]" dir="auto">{{ $draft['outcome_statement'] ?? '' }}</div>
                    </div>
                    <table class="table">
                        <thead><tr><th class="w-11">#</th><th>مهارت پیشنهادی</th><th class="w-56">پیش‌نیاز</th></tr></thead>
                        <tbody>
                            @foreach ($draft['skills'] ?? [] as $i => $skill)
                                <tr>
                                    <td class="num">{{ $i + 1 }}</td>
                                    <td dir="auto"><div class="font-medium">{{ $skill['name'] }}</div><div class="text-[12.5px] text-muted">{{ $skill['description'] }}</div></td>
                                    <td class="text-[12.5px] text-muted">{{ collect($skill['prerequisite_keys'] ?? [])->map(fn ($k) => $draftSkillsByKey[$k]['name'] ?? $k)->join('، ') ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($learningItem->isDesignApproved())
                <div class="card">
                    <div class="card-h">
                        <h3>مهارت‌ها <span class="text-faint font-normal">· {{ fa_num($learningItem->skills->count()) }}</span></h3>
                        <x-level-bar :counts="$levelCounts" class="w-40" />
                    </div>
                    <table class="table">
                        <thead><tr><th class="w-11">#</th><th>مهارت</th><th class="w-56">پیش‌نیاز</th><th class="w-44">سطح</th></tr></thead>
                        <tbody>
                            @foreach ($learningItem->skills as $i => $skill)
                                <tr>
                                    <td class="num">{{ $i + 1 }}</td>
                                    <td dir="auto">
                                        <div class="font-medium">{{ $skill->name }}</div>
                                        @if ($skill->description)
                                            <div class="text-[12.5px] text-muted">{{ $skill->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-[12.5px] text-muted">{{ $skill->prerequisites->pluck('name')->join('، ') ?: '—' }}</td>
                                    <td><x-level-badge :level="$levelOf($skill)" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-5">
            @if ($learningItem->isDesignApproved())
                <div class="card">
                    <div class="card-h">
                        <h3>زمان‌بندی</h3>
                        <a href="{{ route('learning-items.edit', $learningItem) }}" class="iconbtn w-7 h-7" title="ویرایش"><x-icon name="gear" class="w-3.5 h-3.5" /></a>
                    </div>
                    <div class="px-[18px] py-1.5">
                        <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">اولویت</span><span class="font-semibold">{{ fa_num($learningItem->priority) }} از ۵</span></div>
                        <div class="flex justify-between py-2 border-b border-line"><span class="text-muted">زمان روزانه</span><span class="font-semibold">{{ $learningItem->daily_time_minutes ? fa_num($learningItem->daily_time_minutes).' دقیقه' : '—' }}</span></div>
                        <div class="flex justify-between py-2"><span class="text-muted">ساعت ترجیحی</span><span class="font-semibold">{{ $learningItem->preferred_time ? fa_num(substr($learningItem->preferred_time, 0, 5)) : '—' }}</span></div>
                    </div>
                    @unless ($learningItem->daily_time_minutes)
                        <div class="px-[18px] pb-3 text-[12.5px] text-warn">بدون زمان روزانه، این موضوع توی پلن نمیاد.</div>
                    @endunless
                </div>
            @endif

            @if ($learningItem->starting_point)
                <div class="card">
                    <div class="card-h"><h3>نقطه‌ی شروع</h3></div>
                    <div class="px-[18px] py-3 text-[13px] text-muted" dir="auto">{{ $learningItem->starting_point }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
