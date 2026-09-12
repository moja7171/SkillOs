<nav class="h-14 border-b border-line bg-surface flex items-center px-4 sm:px-8 gap-4 sm:gap-7">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 font-bold text-[16px] text-ink hover:text-ink">
        <x-application-logo />
        <span class="hidden sm:inline">SkillOS</span>
    </a>

    <div class="flex gap-1">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">خانه</x-nav-link>
        <x-nav-link :href="route('learning-items.index')" :active="request()->routeIs('learning-items.*')">یادگیری‌ها</x-nav-link>
    </div>

    <div class="ms-auto flex items-center gap-3">
        <button type="button" class="iconbtn" title="تغییر تم" onclick="window.toggleTheme()">
            <x-icon name="sun" class="w-4 h-4 hidden dark:block" />
            <x-icon name="moon" class="w-4 h-4 dark:hidden" />
        </button>

        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <button type="button" class="w-8 h-8 rounded-full bg-surface2 border border-line2 grid place-items-center font-semibold text-[13px] text-ink">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 text-[12.5px] text-faint border-b border-line">{{ auth()->user()->name }}</div>
                <x-dropdown-link :href="route('profile.edit')">پروفایل</x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">خروج</x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</nav>
