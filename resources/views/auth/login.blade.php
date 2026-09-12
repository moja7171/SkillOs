<x-guest-layout>
    <h1 class="m-0 text-[18px] font-bold mb-1">ورود</h1>
    <p class="text-muted text-[13px] mb-5">خوش برگشتی.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" type="email" name="email" dir="ltr" class="text-left" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="رمز عبور" />
            <x-text-input id="password" type="password" name="password" dir="ltr" class="text-left" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-[13px] text-muted">
            <input id="remember_me" type="checkbox" name="remember" class="form-checkbox rounded border-line2 bg-surface2 text-accent focus:ring-accent">
            <span>مرا به خاطر بسپار</span>
        </label>

        <div class="flex items-center justify-between mt-2">
            <a href="{{ route('register') }}" class="text-[13px] text-muted">حساب نداری؟ ثبت‌نام</a>
            <x-primary-button>ورود</x-primary-button>
        </div>
    </form>
</x-guest-layout>
