<section>
    <header class="mb-5">
        <h2 class="m-0 text-[16px] font-semibold">اطلاعات حساب</h2>
        <p class="m-0 mt-1 text-[13px] text-muted">نام و ایمیلت.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="نام" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="ایمیل" />
            <x-text-input id="email" name="email" type="email" dir="ltr" class="text-left" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4 justify-end">
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="m-0 text-[13px] text-ok">ذخیره شد.</p>
            @endif
            <x-primary-button>ذخیره</x-primary-button>
        </div>
    </form>
</section>
