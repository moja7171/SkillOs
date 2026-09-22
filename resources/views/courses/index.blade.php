<x-app-layout :title="$mine ? 'دوره‌های من' : 'همه‌ی دوره‌ها'">
    <div class="px-4 sm:px-8 py-7 max-w-[1600px] mx-auto" x-data="{ active: @js($activeCategory) }">
        <h1 class="m-0 text-[22px] font-bold mb-1">{{ $mine ? 'دوره‌های من' : 'همه‌ی دوره‌ها' }}</h1>
        <p class="text-muted mb-5">{{ $mine ? 'دوره‌هایی که برداشتی.' : 'دوره‌ای که می‌خوای رو بردار؛ پیشرفتت فقط برای خودته.' }}</p>

        @if ($courses->isEmpty())
            <div class="card p-10 text-center text-muted">
                @if ($mine)
                    هنوز هیچ دوره‌ای برنداشتی. <a href="{{ route('courses.index') }}" class="text-ink underline">از بین همه‌ی دوره‌ها</a> یکی رو بردار.
                @else
                    هنوز دوره‌ای وارد نشده. <span class="mono text-[12px]">php artisan content:import &lt;slug&gt;</span>
                @endif
            </div>
        @endif

        @foreach ($coursesByCategory as $category => $group)
            <div class="mb-7"
                 @if ($category !== '')
                     x-show="active === 'all' || active === @js($category)"
                 @else
                     x-show="active === 'all'"
                 @endif>
                @if ($category !== '')
                    <button type="button"
                            @click="active = (active === @js($category)) ? 'all' : @js($category)"
                            class="text-[15px] font-bold mb-3 hover:text-ink transition-colors"
                            :class="active === @js($category) ? 'text-ink' : 'text-muted'">
                        {{ $category }}
                    </button>
                @endif
                <div class="grid gap-3.5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($group as $course)
                        @php $enrolled = $course->enrollments->isNotEmpty(); @endphp
                        <div class="card overflow-hidden flex flex-col">
                            <a href="{{ route('courses.show', $course) }}" class="flex-1 flex flex-col text-ink hover:text-ink">
                                <div class="relative aspect-[16/9] shrink-0">
                                    <x-course-cover :course="$course" />
                                    <div class="absolute inset-x-2 top-2 flex items-start justify-between gap-2">
                                        @if ($category !== '' && $course->category_order)
                                            <span class="badge bg-surface/90 backdrop-blur-sm shadow-sm" title="ترتیب پیشنهادی توی «{{ $category }}»">قدم {{ fa_num($course->category_order) }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                        @if ($enrolled)
                                            <span class="badge badge-ok bg-surface/90 backdrop-blur-sm shadow-sm"><span class="dot"></span>برداشته‌شده</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="p-3.5 pb-2 flex-1 flex flex-col gap-1.5">
                                    <div class="text-[14px] font-semibold leading-[1.4] line-clamp-2">{{ $course->title }}</div>
                                    <div class="text-[12px] text-muted line-clamp-2">{{ $course->description ?? $course->outcome_statement }}</div>
                                </div>
                            </a>
                            <div class="flex items-center justify-between gap-2 px-3.5 pb-3.5 pt-2 mt-auto border-t border-line">
                                <div class="flex items-center gap-2 text-[11.5px] text-faint">
                                    <span>{{ fa_num($course->lessons_count) }} درس</span>
                                    @if ($course->lessons_minutes_sum)
                                        <span>· {{ fa_num((int) round($course->lessons_minutes_sum / 60)) }} ساعت</span>
                                    @endif
                                </div>
                                @unless ($enrolled)
                                    <form method="POST" action="{{ route('courses.enroll', $course) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm"><x-icon name="plus" class="w-3 h-3" /> برداشتن</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
