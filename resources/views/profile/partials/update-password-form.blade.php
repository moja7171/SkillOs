<section>
    <header class="mb-5">
        <h2 class="m-0 text-[16px] font-semibold">تغییر رمز عبور</h2>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="رمز عبور فعلی" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" dir="ltr" class="text-left" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="رمز عبور جدید" />
            <x-text-input id="update_password_password" name="password" type="password" dir="ltr" class="text-left" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="تکرار رمز عبور جدید" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" dir="ltr" class="text-left" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="flex items-center gap-4 justify-end">
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="m-0 text-[13px] text-ok">ذخیره شد.</p>
            @endif
            <x-primary-button>ذخیره</x-primary-button>
        </div>
    </form>
</section>
