<x-guest-layout>
    <h1 class="m-0 text-[18px] font-bold mb-1">ثبت‌نام</h1>
    <p class="text-muted text-[13px] mb-5">یه حساب بساز و اولین موضوعت رو اضافه کن.</p>

    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <div>
            <x-input-label for="name" value="نام" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" type="email" name="email" dir="ltr" class="text-left" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="رمز عبور" />
            <x-text-input id="password" type="password" name="password" dir="ltr" class="text-left" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="تکرار رمز عبور" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" dir="ltr" class="text-left" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-between mt-2">
            <a href="{{ route('login') }}" class="text-[13px] text-muted">قبلاً ثبت‌نام کردی؟ ورود</a>
            <x-primary-button>ثبت‌نام</x-primary-button>
        </div>
    </form>
</x-guest-layout>
