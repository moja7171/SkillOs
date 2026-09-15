<x-app-layout title="نقاط ضعف">
    <div class="page-narrow max-w-xl">
        <div class="text-[12.5px] text-muted flex items-center gap-2 mb-2">
            <a href="{{ route('home') }}" class="text-muted">خانه</a><span class="text-faint">/</span><span>نقاط ضعف</span>
        </div>
        <h1 class="m-0 text-[22px] font-bold mb-1.5">نقاط ضعف</h1>
        <p class="text-muted text-[13.5px] mb-5">درس‌هایی که مدام روشون غلط می‌زنی — جایی برای تمرکز مرور بعدی‌ت.</p>

        @if ($spots->isEmpty())
            <div class="card p-6 text-center text-muted text-[13.5px]">هنوز جایی که مدام اشتباه کرده باشی پیدا نشده — همینطوری ادامه بده.</div>
        @else
            <div class="card">
                @foreach ($spots as $s)
                    <a href="{{ $s['lesson']->url() }}" class="block px-[18px] py-3 border-b border-line last:border-b-0 text-ink hover:bg-hover">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold truncate" dir="auto">{{ $s['lesson']->title }}</span>
                            <span class="text-bad text-[12.5px] font-semibold shrink-0">{{ fa_num($s['incorrect']) }} از {{ fa_num($s['total']) }} غلط</span>
                        </div>
                        <span class="text-faint text-[12.5px]" dir="auto">{{ $s['lesson']->course->title }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
