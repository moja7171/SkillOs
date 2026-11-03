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
                        <x-course-card :course="$course" :href="route('courses.show', $course)" :enrolled="$enrolled"
                                       :step="$category !== '' ? $course->category_order : null" :step-title="'ترتیب پیشنهادی توی «'.$category.'»'"
                                       :can-enroll="! $enrolled" />
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
