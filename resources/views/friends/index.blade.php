<x-app-layout title="دوستان">
    <div class="page-narrow max-w-xl">
        <h1 class="m-0 text-[22px] font-bold mb-5">دوستان</h1>

        <div class="card">
            @foreach ($users as $row)
                @php $u = $row['user']; @endphp
                <div class="flex items-center gap-3.5 px-[18px] py-3 border-b border-line last:border-b-0 {{ $u->is($me) ? 'bg-hover' : '' }}">
                    <span class="w-8 h-8 rounded-full bg-surface2 border border-line2 grid place-items-center font-semibold text-[13px] shrink-0">
                        {{ mb_substr($u->name, 0, 1) }}
                    </span>
                    <span class="flex-1 min-w-0 truncate font-semibold" dir="auto">{{ $u->name }}{{ $u->is($me) ? ' (خودت)' : '' }}</span>
                    @if ($u->streak_count > 0)
                        <span class="flex items-center gap-1 text-[13px] font-semibold text-warn shrink-0">
                            <x-icon name="flame" class="w-4 h-4" /> {{ fa_num($u->streak_count) }}
                        </span>
                    @endif
                    <span class="text-[12.5px] text-muted shrink-0">{{ fa_num($row['week']['count']) }} تمرین این هفته</span>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
