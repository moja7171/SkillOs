<x-app-layout title="موضوع جدید">
    <div class="page-narrow max-w-xl">
        <h1 class="m-0 text-[22px] font-bold mb-1">چی می‌خوای یاد بگیری؟</h1>
        <p class="text-muted mb-5">یه موضوع بنویس. AI یه هدف نهایی و ۴ تا ۱۰ مهارت پیشنهاد می‌ده؛ تا تأیید نکنی هیچی فعال نمی‌شه.</p>

        <div class="card p-6">
            <form method="POST" action="{{ route('learning-items.store') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-input-label for="title" value="موضوع" />
                    <x-text-input id="title" name="title" type="text" placeholder="مثلاً پایتون، یادگیری ماشین، معماری نرم‌افزار" :value="old('title')" required autofocus />
                    <x-input-error :messages="$errors->get('title')" />
                </div>

                <div>
                    <x-input-label for="starting_point" value="الان کجایی؟ (اختیاری)" />
                    <textarea id="starting_point" name="starting_point" rows="3" class="input" placeholder="مثلاً سینتکس پایه و حلقه‌ها رو بلدم، کلاس و محیط مجازی نه">{{ old('starting_point') }}</textarea>
                    <p class="help">کمک می‌کنه AI از چیزهایی که بلدی رد بشه.</p>
                    <x-input-error :messages="$errors->get('starting_point')" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('learning-items.index') }}" class="btn btn-ghost">انصراف</a>
                    <x-primary-button>ساختن</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
