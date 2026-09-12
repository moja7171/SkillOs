<section>
    <header class="mb-4">
        <h2 class="m-0 text-[16px] font-semibold text-bad">حذف حساب</h2>
        <p class="m-0 mt-1 text-[13px] text-muted">همه‌ی موضوع‌ها، مهارت‌ها و سابقه‌ی یادگیری‌ت برای همیشه پاک می‌شه.</p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">حذف حساب</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="m-0 text-[16px] font-semibold">مطمئنی می‌خوای حسابت رو حذف کنی؟</h2>
            <p class="mt-1 mb-4 text-[13px] text-muted">برای تأیید، رمز عبورت رو وارد کن.</p>

            <x-input-label for="password" value="رمز عبور" class="sr-only" />
            <x-text-input id="password" name="password" type="password" dir="ltr" class="text-left" placeholder="رمز عبور" />
            <x-input-error :messages="$errors->userDeletion->get('password')" />

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">انصراف</x-secondary-button>
                <x-danger-button>حذف حساب</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
