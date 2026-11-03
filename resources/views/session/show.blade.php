@php
    $course = $lesson->course;
    $payload = $activity->payload ?? [];
    $evidence = $attempt->evidence ?? [];
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
@endphp
<x-app-layout :title="$activity->title">
    <x-slot name="nav">
        <nav class="h-14 border-b border-line bg-surface flex items-center px-4 sm:px-6 gap-4">
            @if ($open && ! $isReview)
                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'cancel-attempt')" class="iconbtn" title="خروج از تمرین" aria-label="خروج از تمرین"><x-icon name="arrow" class="w-4 h-4" /></button>
            @else
                <a href="{{ $isReview ? route('home') : $lesson->url() }}" class="iconbtn" title="خروج" aria-label="خروج"><x-icon name="arrow" class="w-4 h-4" /></a>
            @endif
            <div class="min-w-0 leading-[1.4]">
                <div class="text-[12px] text-muted truncate">{{ $course->title }} <span class="text-faint">/</span> {{ $lesson->title }}</div>
                <div class="font-bold text-[15px] truncate">{{ $isReview ? 'مرور' : 'تمرین: '.$activity->title }}</div>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                @if ($isReview)
                    <span class="badge badge-ghost"><x-icon name="refresh" class="w-3 h-3" /> مرور</span>
                @else
                    {{-- "تمرین" is implied by the form label below it (e.g. "چندگزینه‌ای"), so it isn't repeated here --}}
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

    @if ($isReview)
        @php
            $mdOpts = ['html_input' => 'strip', 'allow_unsafe_links' => false];
            $points = $lesson->key_points ?? [];
            $mistakes = $lesson->common_mistakes ?? [];
            $chosen = isset($evidence['response']) ? (int) $evidence['response'] : null;
            $correctOption = (int) ($payload['correct_option'] ?? -1);
            $passed = in_array($attempt->result_status, ['correct', 'correct_with_hint'], true);
        @endphp
        <div class="px-4 sm:px-8 py-8 max-w-2xl mx-auto flex flex-col gap-5" x-data="{ stage: {{ $open ? "'recap'" : "'question'" }}, sending: false }">

            {{-- Recap first (authored key points), then one question (DECISIONS.md §70). --}}
            <div x-show="stage === 'recap'" class="card rounded-[18px] p-6 flex flex-col gap-4">
                <div class="text-[12.5px] font-semibold text-muted">اول یه نگاه سریع به درس</div>
                <h2 class="m-0 text-[20px] font-bold" dir="auto">{{ $lesson->title }}</h2>
                @if (! empty($points))
                    <ul class="m-0 ps-5 list-disc text-[14px] leading-[1.9]">
                        @foreach ($points as $point)<li>{!! Str::inlineMarkdown($point, $mdOpts) !!}</li>@endforeach
                    </ul>
                @elseif ($lesson->summary)
                    <p class="m-0 text-[14px] leading-[1.9]">{{ $lesson->summary }}</p>
                @endif
                @if (! empty($mistakes))
                    <div>
                        <div class="text-[12.5px] font-semibold text-bad mb-1.5">اشتباهات رایج</div>
                        <ul class="m-0 ps-5 list-disc text-[13.5px] text-muted leading-[1.9]">
                            @foreach ($mistakes as $mistake)<li>{!! Str::inlineMarkdown($mistake, $mdOpts) !!}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <button type="button" class="btn btn-primary self-start" @click="stage = 'question'"><x-icon name="play" class="w-4 h-4" /> بزن بریم سوال</button>
            </div>

            <div x-show="stage === 'question'" @if ($open) x-cloak @endif class="card rounded-[18px] p-6 flex flex-col gap-5">
                <div class="prose-fa">{!! Str::markdown($payload['prompt'] ?? '', $mdOpts) !!}</div>

                @if (session('error'))
                    <div class="alert alert-bad">{{ session('error') }}</div>
                @endif

                @if ($open)
                    <form method="POST" action="{{ route('session.submit', $attempt) }}" class="flex flex-col gap-4" x-on:submit="sending = true">
                        @csrf
                        <div class="flex flex-col gap-2">
                            @foreach ($payload['options'] as $i => $option)
                                <label class="flex items-center gap-3 rounded-lg border border-line2 bg-surface2 px-4 py-3 cursor-pointer hover:border-faint has-[:checked]:border-accent">
                                    <input type="radio" name="response" value="{{ $i }}" class="form-radio text-accent focus:ring-accent bg-surface border-line2" required>
                                    <span class="text-[14px]">{!! Str::inlineMarkdown($option, $mdOpts) !!}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('response')" />
                        <button type="submit" class="btn btn-primary self-start" :disabled="sending"><x-icon name="check" class="w-4 h-4" /> ثبت پاسخ</button>
                    </form>
                @else
                    <div class="flex flex-col gap-2">
                        @foreach ($payload['options'] as $i => $option)
                            <div @if ($i === $correctOption) style="background: color-mix(in srgb, var(--ok) 10%, transparent);" @endif @class([
                                'flex items-center gap-3 rounded-lg border px-4 py-3 text-[14px]',
                                'border-ok' => $i === $correctOption,
                                'border-bad' => $i === $chosen && $i !== $correctOption,
                                'border-line2 bg-surface2' => $i !== $correctOption && $i !== $chosen,
                            ])>
                                @if ($i === $correctOption)<x-icon name="check" class="w-4 h-4 shrink-0 text-ok" />@elseif ($i === $chosen)<x-icon name="x" class="w-4 h-4 shrink-0 text-bad" />@endif
                                <span>{!! Str::inlineMarkdown($option, $mdOpts) !!}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="alert {{ $passed ? 'alert-ok' : 'alert-bad' }} flex items-start gap-2.5">
                        <x-icon :name="$passed ? 'check' : 'x'" class="w-4 h-4 mt-1 shrink-0" />
                        <span class="font-semibold text-[13.5px]">{{ $passed ? 'درسته.' : 'این بار نشد؛ جواب درست علامت خورده.' }}</span>
                    </div>

                    @if (! empty($payload['expected_outcome']))
                        <div class="rounded-lg bg-surface2 px-4 py-3">
                            <div class="text-[12.5px] font-semibold text-muted mb-1">توضیح</div>
                            <div class="prose-fa">{!! Str::markdown($payload['expected_outcome'], $mdOpts) !!}</div>
                        </div>
                    @endif

                    @if (! empty($evidence['level_change']))
                        <div class="flex items-center gap-2 text-[13.5px]">
                            <span class="text-muted">سطح این درس:</span>
                            <x-level-badge :level="$evidence['level_change']['from']" />
                            <span class="text-faint">←</span>
                            <x-level-badge :level="$evidence['level_change']['to']" />
                        </div>
                    @endif

                    <div class="flex items-center gap-3 flex-wrap pt-1">
                        @if ($reviewsLeft > 0)
                            <form method="POST" action="{{ route('reviews.start') }}">
                                @csrf
                                <x-primary-button><x-icon name="refresh" class="w-4 h-4" /> مرور بعدی ({{ fa_num($reviewsLeft) }} مونده)</x-primary-button>
                            </form>
                            <a href="{{ route('home') }}" class="btn btn-ghost">برگرد به خانه</a>
                        @else
                            <a href="{{ route('home') }}" class="btn btn-primary">مرورها تموم شد — برگرد به خانه</a>
                        @endif
                        @if ($activity->generated)
                            <form method="POST" action="{{ route('session.report', $attempt) }}" class="ms-auto">
                                @csrf
                                <button type="submit" class="text-[12.5px] text-faint hover:text-ink underline">این سوال مشکل داره</button>
                            </form>
                        @endif
                    </div>
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
            <div class="flex flex-col bg-surface lg:max-h-[calc(100vh-56px)] overflow-y-auto" x-data="{ sending: false }">
                <div class="px-6 pt-5 pb-4 border-b border-line">
                    <div class="text-[12.5px] font-semibold text-muted mb-1">صورت تمرین</div>
                    <div class="prose-fa">{!! Str::markdown($payload['prompt'] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                    @if (! empty($payload['attachments']))
                        <div class="flex flex-col gap-1.5 mt-3">
                            @foreach ($payload['attachments'] as $file)
                                @php $ext = strtolower(pathinfo(parse_url($file['url'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION)); $downloadSrc = media_download_url($file['url']); @endphp
                                <a href="{{ $downloadSrc ?? media_url($file['url']) }}" target="_blank" rel="noopener" download class="flex items-center gap-2.5 text-[13px] text-ink hover:text-accent-text"
                                   @if ($downloadSrc) data-fallback-src="{{ media_url($file['url']) }}" @endif>
                                    <span class="badge badge-ghost uppercase">{{ $ext ?: 'file' }}</span>
                                    <span class="truncate">{{ $file['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex-1 px-6 py-4 flex flex-col gap-4">
                    @if (session('error'))
                        <div class="alert alert-bad">{{ session('error') }}</div>
                    @endif

                    @if ($feedback && ! $done)
                        <div class="alert {{ $lastVerdict === 'partial' ? 'alert-warn' : 'alert-bad' }} flex items-start gap-2.5">
                            <x-icon :name="$lastVerdict === 'partial' ? 'bulb' : 'x'" class="w-4 h-4 mt-1 shrink-0" />
                            <div class="text-[13.5px]"><span class="font-semibold">{{ $lastVerdict === 'partial' ? 'نزدیکه، ولی کامل نیست.' : 'هنوز درست نیست.' }}</span> <span class="opacity-90">{!! Str::inlineMarkdown($feedback, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span></div>
                        </div>
                    @endif

                    @for ($i = 0; $i < $hintsShown; $i++)
                        <div class="rounded-lg px-4 py-3 flex items-start gap-2.5 text-[13.5px]" style="background: color-mix(in srgb, var(--accent) 10%, transparent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);">
                            <x-icon name="bulb" class="w-4 h-4 mt-1 shrink-0 text-accent-text" />
                            <div><span class="font-semibold text-accent-text">راهنمایی {{ fa_num($i + 1) }} از ۲ · </span><span class="text-muted">{!! Str::inlineMarkdown($payload['hints'][$i] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span></div>
                        </div>
                    @endfor

                    @if ($open)
                        <form method="POST" action="{{ route('session.submit', $attempt) }}" class="flex flex-col gap-4" id="answer-form" x-on:submit="sending = true">
                            @csrf
                            @if ($form === 'mcq')
                                <div class="flex flex-col gap-2">
                                    @foreach ($payload['options'] as $i => $option)
                                        <label class="flex items-center gap-3 rounded-lg border border-line2 bg-surface2 px-4 py-3 cursor-pointer hover:border-faint has-[:checked]:border-accent">
                                            <input type="radio" name="response" value="{{ $i }}" class="form-radio text-accent-text focus:ring-accent bg-surface border-line2" @checked(old('response', $evidence['response'] ?? null) == (string) $i) required>
                                            <span class="text-[14px]">{!! Str::inlineMarkdown($option, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span>
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
                            <div class="text-[13.5px]">
                                <span class="font-semibold">{{ $resultLabel }}.</span>
                                @if ($feedback)<span class="opacity-90">{!! Str::inlineMarkdown($feedback, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</span>@endif
                            </div>
                        </div>

                        @if (! empty($evidence['level_change']))
                            @php
                                $lc = $evidence['level_change'];
                                // Starting a lesson (not_started -> learning) happens on every lesson's first
                                // attempt; celebrate real, effortful progress past it instead.
                                $leveledUp = $lc['from'] !== 'not_started' && \App\Models\MasteryRecord::LEVEL_INDEX[$lc['to']] > \App\Models\MasteryRecord::LEVEL_INDEX[$lc['from']];
                            @endphp
                            @if ($leveledUp)
                                <div class="card p-4 flex items-center gap-3" style="border-color: var(--ok); background: color-mix(in srgb, var(--ok) 8%, transparent);">
                                    <x-icon name="sparkle" class="w-5 h-5 shrink-0" style="color: var(--ok);" />
                                    <div class="text-[13.5px] flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold">آفرین، رفتی یه سطح بالاتر!</span>
                                        <x-level-badge :level="$lc['from']" />
                                        <span class="text-faint">←</span>
                                        <x-level-badge :level="$lc['to']" />
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-[13.5px]">
                                    <span class="text-muted">سطح این درس:</span>
                                    <x-level-badge :level="$lc['from']" />
                                    <span class="text-faint">←</span>
                                    <x-level-badge :level="$lc['to']" />
                                </div>
                            @endif
                        @endif

                        @if ($revealed || in_array($attempt->result_status, ['correct', 'correct_with_hint']))
                            <div class="card p-4">
                                <div class="text-[12.5px] font-semibold text-muted mb-2">{{ $revealed ? 'پاسخ و توضیح' : 'برای مقایسه: پاسخ مرجع' }}</div>
                                <div class="prose-fa">{!! Str::markdown($payload['expected_outcome'] ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                            </div>
                        @endif

                        @if (! empty($evidence['response']))
                            <details class="text-[13px]">
                                <summary class="cursor-pointer text-muted">پاسخ آخر من</summary>
                                <pre class="mt-2 p-3 rounded-lg bg-code border border-line whitespace-pre-wrap text-[12.5px]" dir="auto">{{ $form === 'mcq' ? ($payload['options'][(int) $evidence['response']] ?? $evidence['response']) : $evidence['response'] }}</pre>
                            </details>
                        @endif

                        @if ($history->isNotEmpty())
                            <details class="text-[13px]">
                                <summary class="cursor-pointer text-muted">تلاش‌های قبلی من ({{ fa_num($history->count()) }})</summary>
                                <div class="mt-2 flex flex-col gap-2">
                                    @foreach ($history as $past)
                                        @php
                                            $pastLabel = match ($past->result_status) {
                                                'correct' => 'درست', 'correct_with_hint' => 'درست، با کمک', 'incorrect' => 'غلط', 'started' => 'رهاشده', default => $past->result_status,
                                            };
                                            $pastClass = match ($past->result_status) {
                                                'correct' => 'badge-ok', 'correct_with_hint' => 'badge-warn', 'incorrect' => 'badge-bad', default => 'badge-ghost',
                                            };
                                            $pastResponse = $past->evidence['response'] ?? null;
                                        @endphp
                                        <div class="p-3 rounded-lg bg-code border border-line flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-[12px] text-faint mb-1">{{ fa_date($past->completed_at ?? $past->started_at, 'j F · H:i') }}</div>
                                                @if ($pastResponse !== null)
                                                    <pre class="whitespace-pre-wrap text-[12.5px]" dir="auto">{{ $form === 'mcq' ? ($payload['options'][(int) $pastResponse] ?? $pastResponse) : $pastResponse }}</pre>
                                                @else
                                                    <span class="text-faint">بدون پاسخ ثبت‌شده</span>
                                                @endif
                                            </div>
                                            <span class="badge {{ $pastClass }} shrink-0">{{ $pastLabel }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @endif
                </div>

                <div class="border-t border-line px-6 py-4 flex items-center gap-3 flex-wrap">
                    @if ($open)
                        <button type="submit" form="answer-form" class="btn btn-primary" :disabled="sending">
                            <span x-show="! sending" class="inline-flex items-center gap-2"><x-icon name="check" class="w-4 h-4" /> ارسال پاسخ</span>
                            <span x-show="sending" x-cloak class="inline-flex items-center gap-2"><svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" /></svg> در حال ارسال…</span>
                        </button>
                        <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'give-up')" class="btn btn-ghost ms-auto">بی‌خیال، جواب رو نشون بده</button>
                    @else
                        @if ($next)
                            <form method="POST" action="{{ route('session.start', $next) }}">
                                @csrf
                                <x-primary-button><x-icon name="play" class="w-4 h-4" /> تمرین بعدی: {{ $next->title }}</x-primary-button>
                            </form>
                        @else
                            <a href="{{ $lesson->url() }}" class="btn btn-primary">برگرد به درس</a>
                        @endif
                        <form method="POST" action="{{ route('session.start', $activity) }}">
                            @csrf
                            <button type="submit" class="btn"><x-icon name="refresh" class="w-4 h-4" /> دوباره همین تمرین</button>
                        </form>
                        @if ($next)
                            <a href="{{ $lesson->url() }}" class="btn btn-ghost ms-auto">برگرد به درس</a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <x-modal name="cancel-attempt" maxWidth="sm">
            <div class="p-6">
                <h2 class="m-0 text-[16px] font-semibold">از این تمرین خارج بشی؟</h2>
                <p class="mt-1 mb-5 text-[13px] text-muted">پاسخت ذخیره نمی‌شه و این تلاش اصلاً ثبت نمی‌شه — انگار نه انگار که شروعش کرده بودی.</p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">بمونم</x-secondary-button>
                    <form method="POST" action="{{ route('session.cancel', $attempt) }}">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>بله، خارج شو</x-danger-button>
                    </form>
                </div>
            </div>
        </x-modal>

        <x-modal name="give-up" maxWidth="sm">
            <div class="p-6">
                <h2 class="m-0 text-[16px] font-semibold">جواب نشون داده بشه؟</h2>
                <p class="mt-1 mb-5 text-[13px] text-muted">این تمرین ناموفق ثبت می‌شه و پاسخ مرجع نشونت می‌دیم.</p>
                <div class="flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">بازم امتحان می‌کنم</x-secondary-button>
                    <form method="POST" action="{{ route('session.give-up', $attempt) }}">
                        @csrf
                        <x-danger-button>آره، نشونم بده</x-danger-button>
                    </form>
                </div>
            </div>
        </x-modal>
    @endif
</x-app-layout>
