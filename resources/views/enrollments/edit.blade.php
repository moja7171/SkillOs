<x-app-layout title="زمان‌بندی">
    <div class="page-narrow max-w-xl">
        <div class="text-[12.5px] text-muted flex items-center gap-2 mb-2">
            <a href="{{ route('courses.show', $enrollment->course) }}" class="text-muted" dir="auto">{{ $enrollment->course->title }}</a><span class="text-faint">/</span>
            <span>زمان‌بندی</span>
        </div>
        <h1 class="m-0 text-[22px] font-bold mb-5">زمان‌بندی و وضعیت</h1>

        <div class="card p-6">
            <form method="POST" action="{{ route('enrollments.update', $enrollment) }}" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="priority" value="اولویت" />
                    <select id="priority" name="priority" class="input">
                        @for ($p = 1; $p <= 5; $p++)
                            <option value="{{ $p }}" @selected(old('priority', $enrollment->priority) == $p)>{{ fa_num($p) }}{{ $p === 1 ? ' — بالاترین' : ($p === 5 ? ' — پایین‌ترین' : '') }}</option>
                        @endfor
                    </select>
                    <p class="help">وقتی چند دوره داری، اولویت تعیین می‌کنه کدوم اول توی «ادامه‌ی یادگیری» میاد.</p>
                    <x-input-error :messages="$errors->get('priority')" />
                </div>

                <div>
                    <x-input-label for="daily_time_minutes" value="زمان روزانه (دقیقه)" />
                    <x-text-input id="daily_time_minutes" name="daily_time_minutes" type="number" min="5" max="480" step="5" dir="ltr" class="text-left" :value="old('daily_time_minutes', $enrollment->daily_time_minutes)" placeholder="مثلاً 30" />
                    <p class="help">خالی بذاری یعنی این دوره توی پلن روزانه نمیاد ولی می‌تونی آزادانه تمرین کنی.</p>
                    <x-input-error :messages="$errors->get('daily_time_minutes')" />
                </div>

                <div>
                    <x-input-label for="preferred_time" value="ساعت ترجیحی (اختیاری)" />
                    <x-text-input id="preferred_time" name="preferred_time" type="time" dir="ltr" class="text-left" :value="old('preferred_time', $enrollment->preferred_time ? substr($enrollment->preferred_time, 0, 5) : null)" />
                    <x-input-error :messages="$errors->get('preferred_time')" />
                </div>

                <div>
                    <x-input-label for="status" value="وضعیت" />
                    <select id="status" name="status" class="input">
                        @foreach (\App\Models\Enrollment::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $enrollment->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="help">متوقف/بایگانی: از پلن خارج می‌شه ولی هیچ داده‌ای پاک نمی‌شه. نگه‌داری: فقط مرورها.</p>
                    <x-input-error :messages="$errors->get('status')" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('courses.show', $enrollment->course) }}" class="btn btn-ghost">انصراف</a>
                    <x-primary-button>ذخیره</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
