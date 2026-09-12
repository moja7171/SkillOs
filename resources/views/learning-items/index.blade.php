<x-app-layout title="یادگیری‌ها">
    <div class="page-narrow max-w-3xl">
        <div class="flex items-center justify-between mb-5">
            <h1 class="m-0 text-[22px] font-bold">یادگیری‌ها</h1>
            <a href="{{ route('learning-items.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="w-4 h-4" /> موضوع جدید</a>
        </div>

        @if (session('status'))
            <div class="alert alert-ok mb-4">{{ session('status') }}</div>
        @endif

        @if ($learningItems->isEmpty())
            <div class="card p-10 text-center">
                <div class="text-[16px] font-semibold mb-1">هنوز چیزی برای یادگیری اضافه نکردی</div>
                <div class="text-muted mb-5">یه موضوع بنویس؛ AI هدف نهایی و مهارت‌هاش رو پیشنهاد می‌ده و تو تأیید می‌کنی.</div>
                <a href="{{ route('learning-items.create') }}" class="btn btn-primary">شروع کن</a>
            </div>
        @endif

        <div class="flex flex-col gap-3">
            @foreach ($learningItems as $item)
                <a href="{{ route('learning-items.show', $item) }}" class="card p-[18px] block text-ink hover:text-ink hover:border-line2 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0" dir="auto">
                            <div class="text-[16px] font-semibold">{{ $item->title }}</div>
                            @if ($item->outcome_statement)
                                <div class="text-[13px] text-muted mt-1 line-clamp-2">{{ $item->outcome_statement }}</div>
                            @elseif ($item->design_status === 'pending_review')
                                <div class="text-[13px] text-warn mt-1">طرح پیشنهادی AI منتظر تأیید توئه</div>
                            @else
                                <div class="text-[13px] text-faint mt-1">هنوز طرحی ساخته نشده</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                            @if ($item->design_status === 'pending_review')
                                <span class="badge badge-warn"><span class="dot"></span>{{ $item->designStatusLabel() }}</span>
                            @elseif ($item->design_status === 'draft')
                                <span class="badge badge-ghost">{{ $item->designStatusLabel() }}</span>
                            @else
                                <span class="badge {{ $item->status === 'active' ? 'badge-ok' : 'badge-ghost' }}"><span class="dot"></span>{{ $item->statusLabel() }}</span>
                            @endif
                            @if ($item->daily_time_minutes)
                                <span class="badge badge-ghost">{{ fa_num($item->daily_time_minutes) }} دقیقه در روز</span>
                            @endif
                        </div>
                    </div>
                    @if ($item->isDesignApproved())
                        <div class="text-[12.5px] text-faint mt-2">{{ fa_num($item->skills_count ?? $item->skills()->count()) }} مهارت · اولویت {{ fa_num($item->priority) }}</div>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
