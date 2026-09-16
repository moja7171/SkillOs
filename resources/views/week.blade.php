<x-app-layout title="هفته">
    <div class="page-narrow max-w-5xl">
        <h1 class="m-0 text-[22px] font-bold mb-1">این هفته</h1>
        <p class="text-muted mb-5 text-[13.5px]">امروز از پلن میاد؛ روزهای بعد فقط مرورهایی که سررسیدشون معلومه. بقیه هر روز از وضعیت همون روز ساخته می‌شه.</p>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($week as $day)
                @php $isToday = $loop->first; $count = $day['items']->count() + $day['reviews']->count(); @endphp
                <div class="card flex flex-col {{ $isToday ? 'lg:col-span-2' : '' }}" @if ($isToday) style="border-color: var(--accent);" @endif>
                    <div class="card-h">
                        <h3 class="{{ $isToday ? 'text-accent' : '' }}">{{ $isToday ? 'امروز · ' : '' }}{{ fa_date($day['date'], 'l j F') }}</h3>
                        <span class="text-[12px] text-faint">{{ $count ? fa_num($count).' مورد' : '—' }}</span>
                    </div>
                    <div class="flex flex-col">
                        @foreach ($day['items'] as $item)
                            <div class="flex items-center gap-2.5 px-4 py-2.5 border-b border-line last:border-b-0 text-[13.5px]">
                                <x-plan-item-badge :item="$item" />
                                <span class="flex-1 min-w-0 truncate {{ $item->status === 'completed' ? 'text-muted line-through' : ($item->status === 'skipped' ? 'text-faint' : '') }}">{{ $item->activity->title }}</span>
                                <span class="num">{{ $item->duration_minutes }}m</span>
                            </div>
                        @endforeach
                        @foreach ($day['reviews'] as $record)
                            <a href="{{ $record->lesson->url() }}" class="flex items-center gap-2.5 px-4 py-2.5 border-b border-line last:border-b-0 text-[13.5px] text-ink hover:bg-hover">
                                <span class="badge badge-ghost"><x-icon name="refresh" class="w-3 h-3" /> مرور</span>
                                <span class="flex-1 min-w-0 truncate">{{ $record->lesson->title }} <span class="text-faint">· {{ $record->lesson->course->title }}</span></span>
                                <x-level-badge :level="$record->level" />
                            </a>
                        @endforeach
                        @if ($count === 0)
                            <div class="px-4 py-4 text-[12.5px] text-faint">{{ $isToday ? 'چیزی برای امروز نیست.' : 'فعلاً مروری نیست.' }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
