@php
    $course = $lesson->course;
    $payload = $activity->payload ?? [];
    $evidence = $attempt->evidence ?? [];
    $isLearn = $activity->isLearn();
    $form = $payload['form'] ?? null;
    $lastVerdict = $evidence['verdict'] ?? null;
    $feedback = $evidence['feedback'] ?? null;
    $hintsShown = (int) ($evidence['hints_shown'] ?? 0);
    $revealed = ! empty($evidence['revealed']);
    $done = ! $open;
    $resultLabel = match ($attempt->result_status) {
        'correct' => 'درست — بدون راهنمایی',
        'correct_with_hint' => 'درست — با کمک',
        'incorrect' => 'این بار نشد',
        'completed' => 'خوانده شد',
        default => null,
    };
    $resultClass = match ($attempt->result_status) {
        'correct' => 'badge-ok', 'correct_with_hint' => 'badge-warn', 'incorrect' => 'badge-bad', default => 'badge-ghost',
    };
    $nextLabel = $next ? ($isLearn ? 'برو سراغ تمرین: '.$next->title : 'تمرین بعدی: '.$next->title) : 'برگرد به درس';
@endphp
<x-app-layout :title="$activity->title">
    <x-slot name="nav">
        <nav class="h-14 border-b border-line bg-surface flex items-center px-4 sm:px-6 gap-4">
            <a href="{{ route('lessons.show', $lesson) }}" class="iconbtn" title="خروج از جلسه"><x-icon name="arrow" class="w-4 h-4" /></a>
            <div class="min-w-0 leading-[1.4]">
                <div class="text-[12px] text-muted truncate" dir="auto">{{ $course->title }} <span class="text-faint">/</span> {{ $lesson->title }}</div>
                <div class="font-bold text-[15px] truncate" dir="auto">{{ $isLearn ? 'یادگیری: ' : 'تمرین: ' }}{{ $activity->title }}</div>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                @if ($isLearn)
                    <span class="badge badge-l1">یادگیری</span>
                @else
                    <span class="badge badge-l3">تمرین</span>
                    <span class="badge badge-ghost">{{ $activity->formLabel() }}</span>
                    <span @class(['badge', 'badge-ok' => ($payload['difficulty'] ?? '') === 'intro', 'badge-warn' => ($payload['difficulty'] ?? '') === 'core', 'badge-bad' => ($payload['difficulty'] ?? '') === 'stretch'])>{{ $activity->difficultyLabel() }}</span>
                @endif
            </div>
            <div class="ms-auto flex items-center gap-3 text-[12.5px] text-muted">
                <span class="flex items-center gap-1.5"><x-icon name="clock" class="w-3.5 h-3.5" /> حدود {{ fa_num($activity->estimated_minutes) }} دقیقه</span>
                @if ($resultLabel)<span class="badge {{ $resultClass }}"><span class="dot"></span>{{ $resultLabel }}</span>@endif
            </div>
        </nav>
    </x-slot>

    @if ($isLearn)
        <div class="px-4 sm:px-8 py-6 max-w-4xl mx-auto flex flex-col gap-5">
            <x-lesson-content :lesson="$lesson" class="card" />
            <div class="card p-5 flex items-center justify-between gap-4 flex-wrap">
                @if ($open)
                    <div class="text-muted text-[13.5px]">وقتی ویدیو رو دیدی یا متن رو خوندی، بزن بریم تمرین.</div>
                    <form method="POST" action="{{ route('session.complete', $attempt) }}">
                        @csrf
                        <x-primary-button><x-icon name="check" class="w-4 h-4" /> آماده‌ام</x-primary-button>
                    </form>
                @else
                    <div class="text-[13.5px] flex items-center gap-2 flex-wrap">
                        <span>این درس رو خوندی. حالا امتحانش کن.</span>
                        @if (! empty($evidence['level_change']))
                            <x-level-badge :level="$evidence['level_change']['to']" />
                        @endif
                    </div>
                    @if ($next)
                        <form method="POST" action="{{ route('session.start', $next) }}">
                            @csrf
                            <x-primary-button><x-icon name="play" class="w-4 h-4" /> {{ $nextLabel }}</x-primary-button>
                        </form>
                    @else
                        <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-primary">برگرد به درس</a>
                    @endif
                @endif
            </div>
        </div>
    @else
        <div class="grid lg:grid-cols-2 min-h-[calc(100vh-56px)]">
            {{-- Learn pane --}}
            <div class="border-b lg:border-b-0 lg:border-inline-end border-line overflow-y-auto lg:max-h-[calc(100vh-56px)]">
                <x-lesson-content :lesson="$lesson" compact />
            </div>

            {{-- Practice pane --}}
            <div class="flex flex-col bg-surface lg:max-h-[calc(100vh-56px)] overflow-y-auto">
                <div class="px-6 pt-5 pb-4 border-b border-line">
                    <div class="text-[12.5px] font-semibold text-muted mb-1">صورت تمرین</div>
                    <div class="prose-fa" dir="auto">{!! Str::markdown($payload['prompt'] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                </div>

                <div class="flex-1 px-6 py-4 flex flex-col gap-4">
                    @if (session('error'))
                        <div class="alert alert-bad">{{ session('error') }}</div>
                    @endif

                    @if ($feedback && ! $done)
                        <div class="alert {{ $lastVerdict === 'partial' ? 'alert-warn' : 'alert-bad' }} flex items-start gap-2.5">
                            <x-icon :name="$lastVerdict === 'partial' ? 'bulb' : 'x'" class="w-4 h-4 mt-1 shrink-0" />
                            <div class="text-[13.5px]" dir="auto"><span class="font-semibold">{{ $lastVerdict === 'partial' ? 'نزدیکه، ولی کامل نیست.' : 'هنوز درست نیست.' }}</span> <span class="opacity-90">{!! Str::inlineMarkdown($feedback, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span></div>
                        </div>
                    @endif

                    @for ($i = 0; $i < $hintsShown; $i++)
                        <div class="rounded-lg px-4 py-3 flex items-start gap-2.5 text-[13.5px]" style="background: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <x-icon name="bulb" class="w-4 h-4 mt-1 shrink-0 text-accent" />
                            <div dir="auto"><span class="font-semibold text-accent">راهنمایی {{ fa_num($i + 1) }} از ۲ · </span><span class="text-muted">{!! Str::inlineMarkdown($payload['hints'][$i] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span></div>
                        </div>
                    @endfor

                    @if ($open)
                        <form method="POST" action="{{ route('session.submit', $attempt) }}" class="flex flex-col gap-4" id="answer-form">
                            @csrf
                            @if ($form === 'mcq')
                                <div class="flex flex-col gap-2">
                                    @foreach ($payload['options'] as $i => $option)
                                        <label class="flex items-center gap-3 rounded-lg border border-line2 bg-surface2 px-4 py-3 cursor-pointer hover:border-faint has-[:checked]:border-accent">
                                            <input type="radio" name="response" value="{{ $i }}" class="form-radio text-accent focus:ring-accent bg-surface border-line2" @checked(old('response', $evidence['response'] ?? null) == (string) $i) required>
                                            <span class="text-[14px]" dir="auto">{!! Str::inlineMarkdown($option, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($form === 'coding')
                                <div class="rounded-lg border border-line overflow-hidden">
                                    <div class="flex items-center px-3 py-1.5 border-b border-line bg-surface2 text-[11.5px] text-muted"><span class="mono">answer</span><span class="ms-auto text-faint">کد</span></div>
                                    <textarea name="response" rows="14" dir="ltr" spellcheck="false" class="block w-full border-0 bg-code text-ink font-mono text-[13px] leading-[1.65] p-4 focus:ring-0 resize-y" placeholder="# کدت رو اینجا بنویس" required>{{ old('response', $evidence['response'] ?? '') }}</textarea>
                                </div>
                            @else
                                <textarea name="response" rows="8" class="input leading-[1.8]" dir="auto" placeholder="پاسخت رو بنویس…" required>{{ old('response', $evidence['response'] ?? '') }}</textarea>
                            @endif
                            <x-input-error :messages="$errors->get('response')" />
                        </form>
                    @endif

                    @if ($done)
                        <div class="alert {{ in_array($attempt->result_status, ['correct', 'correct_with_hint']) ? 'alert-ok' : 'alert-bad' }} flex items-start gap-2.5">
                            <x-icon :name="in_array($attempt->result_status, ['correct', 'correct_with_hint']) ? 'check' : 'x'" class="w-4 h-4 mt-1 shrink-0" />
                            <div class="text-[13.5px]" dir="auto">
                                <span class="font-semibold">{{ $resultLabel }}.</span>
                                @if ($feedback)<span class="opacity-90">{!! Str::inlineMarkdown($feedback, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span>@endif
                            </div>
                        </div>

                        @if (! empty($evidence['level_change']))
                            @php $lc = $evidence['level_change']; @endphp
                            <div class="flex items-center gap-2 text-[13.5px]">
                                <span class="text-muted">سطح این درس:</span>
                                <x-level-badge :level="$lc['from']" />
                                <span class="text-faint">←</span>
                                <x-level-badge :level="$lc['to']" />
                            </div>
                        @endif

                        @if ($revealed || in_array($attempt->result_status, ['correct', 'correct_with_hint']))
                            <div class="card p-4">
                                <div class="text-[12.5px] font-semibold text-muted mb-2">{{ $revealed ? 'پاسخ و توضیح' : 'برای مقایسه: پاسخ مرجع' }}</div>
                                <div class="prose-fa" dir="auto">{!! Str::markdown($payload['expected_outcome'] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                            </div>
                        @endif

                        @if (! empty($evidence['response']))
                            <details class="text-[13px]">
                                <summary class="cursor-pointer text-muted">پاسخ آخر من</summary>
                                <pre class="mt-2 p-3 rounded-lg bg-code border border-line whitespace-pre-wrap text-[12.5px]" dir="auto">{{ $form === 'mcq' ? ($payload['options'][(int) $evidence['response']] ?? $evidence['response']) : $evidence['response'] }}</pre>
                            </details>
                        @endif
                    @endif
                </div>

                <div class="border-t border-line px-6 py-4 flex items-center gap-3 flex-wrap">
                    @if ($open)
                        <button type="submit" form="answer-form" class="btn btn-primary"><x-icon name="check" class="w-4 h-4" /> ارسال پاسخ</button>
                        <form method="POST" action="{{ route('session.give-up', $attempt) }}" class="ms-auto" onsubmit="return confirm('پاسخ نشون داده می‌شه و این تمرین ناموفق ثبت می‌شه. مطمئنی؟')">
                            @csrf
                            <button type="submit" class="btn btn-ghost">بی‌خیال، جواب رو نشون بده</button>
                        </form>
                    @else
                        @if ($next)
                            <form method="POST" action="{{ route('session.start', $next) }}">
                                @csrf
                                <x-primary-button><x-icon name="play" class="w-4 h-4" /> {{ $nextLabel }}</x-primary-button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('session.start', $activity) }}">
                            @csrf
                            <button type="submit" class="btn"><x-icon name="refresh" class="w-4 h-4" /> دوباره همین تمرین</button>
                        </form>
                        <a href="{{ route('lessons.show', $lesson) }}" class="btn btn-ghost ms-auto">برگرد به درس</a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
