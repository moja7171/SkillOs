<x-app-layout title="جست‌وجو">
    <div class="page-narrow max-w-2xl">
        <form method="GET" action="{{ route('search') }}" class="flex items-center gap-2 mb-6">
            <div class="relative flex-1">
                <x-icon name="search" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3.5 text-faint" />
                <input type="text" name="q" value="{{ $query }}" autofocus placeholder="اسم درس، دوره یا یه کلمه‌ی داخل متنش…" dir="auto" class="input ps-10">
            </div>
            <button type="submit" class="btn btn-primary">جست‌وجو</button>
        </form>

        @if ($query !== '' && mb_strlen($query) < 2)
            <div class="text-muted text-[13.5px]">حداقل ۲ حرف بنویس.</div>
        @elseif ($query !== '' && $results->isEmpty())
            <div class="text-muted text-[13.5px]">چیزی برای «{{ $query }}» پیدا نشد.</div>
        @elseif ($results->isNotEmpty())
            <div class="text-[12.5px] text-muted mb-3">{{ fa_num($results->count()) }} نتیجه</div>
            <div class="card">
                @foreach ($results as $r)
                    <a href="{{ $r['lesson']->url() }}" class="block px-[18px] py-3 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold" dir="auto">{{ $r['lesson']->title }}</span>
                            <span class="text-faint text-[12.5px]" dir="auto">· {{ $r['lesson']->course->title }}</span>
                        </div>
                        @if ($r['snippet'])
                            <div class="text-[12.5px] text-muted mt-1 truncate" dir="auto">{{ $r['snippet'] }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
