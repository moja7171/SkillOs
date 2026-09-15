<x-app-layout title="نقاط ضعف">
    <div class="page-narrow max-w-xl">
        <div class="text-[12.5px] text-muted flex items-center gap-2 mb-2">
            <a href="{{ route('home') }}" class="text-muted">خانه</a><span class="text-faint">/</span><span>نقاط ضعف</span>
        </div>
        <h1 class="m-0 text-[22px] font-bold mb-1.5">نقاط ضعف</h1>
        <p class="text-muted text-[13.5px] mb-5">درس‌هایی که مدام روشون غلط می‌زنی — جایی برای تمرکز مرور بعدی‌ت.</p>

        @if ($spots->isEmpty())
            <div class="card p-6 flex flex-col items-center text-center gap-3">
                <span class="w-12 h-12 rounded-full grid place-items-center shrink-0" style="background: color-mix(in srgb, var(--ok) 16%, transparent); color: var(--ok);">
                    <x-icon name="trophy" class="w-6 h-6" />
                </span>
                <div>
                    <div class="font-semibold mb-1">فعلاً جای ضعفی پیدا نشده</div>
                    <div class="text-muted text-[13.5px]">همینطوری ادامه بده — هر جا مدام روش غلط بزنی، همین‌جا نشونت می‌دیم.</div>
                </div>
                <a href="{{ route('home') }}" class="btn btn-sm mt-1"><x-icon name="play" class="w-3.5 h-3.5" /> ادامه‌ی یادگیری</a>
            </div>
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
