<nav class="sticky top-0 z-20 h-14 border-b border-line bg-surface flex items-center px-4 sm:px-8 gap-4 sm:gap-7">
    <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-[16px] text-ink hover:text-ink">
        <x-application-logo class="w-7 h-7" />
        <span class="hidden sm:inline tracking-tight">Skill<span class="text-accent">OS</span></span>
    </a>

    @php
        $coursesActive = request()->routeIs('courses.*') || request()->routeIs('lessons.*') || request()->routeIs('enrollments.*');
    @endphp

    <div class="hidden sm:flex gap-1 items-center">
        <x-nav-link :href="route('home')" :active="request()->routeIs('home')">خانه</x-nav-link>

        <x-dropdown align="right" width="w-56">
            <x-slot name="trigger">
                <button type="button" class="flex items-center gap-1 {{ $coursesActive ? 'px-3 py-1.5 rounded-md text-[14px] font-medium text-ink bg-surface2 hover:text-ink' : 'px-3 py-1.5 rounded-md text-[14px] font-medium text-muted hover:text-ink hover:bg-surface2 transition' }}"
                        aria-haspopup="true" :aria-expanded="open.toString()">
                    دوره‌ها
                    <x-icon name="chevron" class="w-3 h-3 rotate-90" />
                </button>
            </x-slot>
            <x-slot name="content">
                <x-dropdown-link :href="route('courses.index')" @class(['bg-surface2' => $coursesActive && ! request()->query('category')])>همه‌ی دوره‌ها</x-dropdown-link>
                <div class="my-1 border-t border-line"></div>
                @foreach (\App\Models\Course::CATEGORIES as $cat)
                    <x-dropdown-link :href="route('courses.index', ['category' => $cat])" @class(['bg-surface2' => request()->query('category') === $cat])>{{ $cat }}</x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>

        <x-nav-link :href="route('week')" :active="request()->routeIs('week')">هفته</x-nav-link>
        @if (auth()->user()->is_admin)
            <x-nav-link :href="route('friends')" :active="request()->routeIs('friends')">دوستان</x-nav-link>
        @endif
    </div>

    <div class="ms-auto flex items-center gap-3">
        <a href="{{ route('search') }}" class="iconbtn hidden sm:grid" title="جست‌وجو" aria-label="جست‌وجو"><x-icon name="search" class="w-4 h-4" /></a>

        <button type="button" class="iconbtn" title="تغییر تم" aria-label="تغییر تم" onclick="window.toggleTheme()">
            <x-icon name="sun" class="w-4 h-4 hidden dark:block" />
            <x-icon name="moon" class="w-4 h-4 dark:hidden" />
        </button>

        {{-- Below sm: everything else (links + search) folds into this menu instead of
             overflowing the bar — see DECISIONS.md §30. --}}
        <div class="sm:hidden">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button type="button" class="iconbtn" title="منو" aria-label="منو" aria-haspopup="true" :aria-expanded="open.toString()"><x-icon name="menu" class="w-4 h-4" /></button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('home')" @class(['bg-surface2' => request()->routeIs('home')])>خانه</x-dropdown-link>
                    <x-dropdown-link :href="route('courses.index')" @class(['bg-surface2' => $coursesActive && ! request()->query('category')])>همه‌ی دوره‌ها</x-dropdown-link>
                    @foreach (\App\Models\Course::CATEGORIES as $cat)
                        <x-dropdown-link :href="route('courses.index', ['category' => $cat])" @class(['ps-6 text-[12.5px] text-muted', 'bg-surface2' => request()->query('category') === $cat])>{{ $cat }}</x-dropdown-link>
                    @endforeach
                    <x-dropdown-link :href="route('week')" @class(['bg-surface2' => request()->routeIs('week')])>هفته</x-dropdown-link>
                    @if (auth()->user()->is_admin)
                        <x-dropdown-link :href="route('friends')" @class(['bg-surface2' => request()->routeIs('friends')])>دوستان</x-dropdown-link>
                    @endif
                    <x-dropdown-link :href="route('search')" @class(['bg-surface2' => request()->routeIs('search')])>جست‌وجو</x-dropdown-link>
                </x-slot>
            </x-dropdown>
        </div>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button type="button" class="w-8 h-8 rounded-full bg-surface2 border border-line2 grid place-items-center font-semibold text-[13px] text-ink" aria-label="منوی حساب کاربری" aria-haspopup="true" :aria-expanded="open.toString()">
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
