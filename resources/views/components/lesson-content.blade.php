{{-- Video/text tabs + key points for a lesson. Used by the lesson page and the session pane. --}}
@props(['lesson', 'compact' => false])
@php $hasVideo = $lesson->videos->isNotEmpty(); @endphp
<div x-data="{ tab: '{{ $hasVideo ? 'video' : 'text' }}' }" {{ $attributes }}>
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
        {{-- One player at a time, capped in width so the page stays scannable; fullscreen is still available from the player. --}}
        <div x-show="tab === 'video'" x-data="{ video: 0, pick(i) { this.video = i; $el.querySelectorAll('video').forEach(v => v.pause()); } }" class="p-4 flex flex-col gap-3">
            @if ($lesson->videos->count() > 1)
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach ($lesson->videos as $video)
                        <button type="button" class="btn btn-sm" :class="video === {{ $loop->index }} ? '' : 'btn-ghost'" @click="pick({{ $loop->index }})" dir="auto">
                            <span class="num">{{ $loop->iteration }}</span> {{ $video->title ?? 'قسمت '.fa_num($loop->iteration) }}
                        </button>
                    @endforeach
                </div>
            @endif
            @foreach ($lesson->videos as $video)
                @php $embed = $video->embed(); @endphp
                <div x-show="video === {{ $loop->index }}" class="w-full max-w-[720px] mx-auto">
                    @if ($embed['kind'] === 'file')
                        <div class="player-shell rounded-lg overflow-hidden bg-black" dir="ltr">
                            <video class="js-player" playsinline preload="metadata" crossorigin="anonymous"
                                   data-video-id="{{ $video->id }}" data-captions-default="{{ $video->subtitles[0]['lang'] ?? 'en' }}">
                                <source src="{{ $embed['src'] }}" type="video/mp4">
                                @foreach ($video->subtitles ?? [] as $track)
                                    <track kind="captions" src="{{ $track['url'] }}" srclang="{{ $track['lang'] }}" label="{{ $track['label'] }}" @if ($loop->first) default @endif>
                                @endforeach
                            </video>
                        </div>
                    @elseif ($embed['kind'] === 'youtube')
                        <div class="player-shell rounded-lg overflow-hidden bg-black" dir="ltr">
                            <div class="js-player" data-plyr-provider="youtube" data-plyr-embed-id="{{ basename($embed['src']) }}" data-video-id="{{ $video->id }}"></div>
                        </div>
                    @elseif ($embed['kind'] === 'link')
                        <a href="{{ $embed['src'] }}" target="_blank" rel="noopener" class="btn">باز کردن ویدیو در تب جدید</a>
                    @else
                        <iframe class="w-full rounded-lg aspect-video bg-black" src="{{ $embed['src'] }}" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div x-show="tab === 'text'" x-cloak class="{{ $compact ? 'px-5 py-3' : 'px-6 py-4' }} prose-fa" dir="auto">
        {!! Str::markdown($lesson->content ?? '', ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>

    @if (! empty($lesson->key_points) || ! empty($lesson->common_mistakes))
        <div class="mx-4 mb-4 rounded-lg bg-surface2 px-4 py-3">
            @if (! empty($lesson->key_points))
                <div class="text-[12.5px] font-semibold mb-1.5">نکته‌های کلیدی</div>
                <ul class="m-0 ps-5 list-disc text-[13px] text-muted">
                    @foreach ($lesson->key_points as $point)<li dir="auto">{!! Str::inlineMarkdown($point, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</li>@endforeach
                </ul>
            @endif
            @if (! empty($lesson->common_mistakes))
                <div class="text-[12.5px] font-semibold mt-3 mb-1.5 text-bad">اشتباهات رایج</div>
                <ul class="m-0 ps-5 list-disc text-[13px] text-muted">
                    @foreach ($lesson->common_mistakes as $mistake)<li dir="auto">{!! Str::inlineMarkdown($mistake, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</li>@endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
