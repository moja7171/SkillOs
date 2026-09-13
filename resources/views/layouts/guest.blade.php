<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-bg">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <a href="/" class="flex items-center gap-2.5 font-bold text-[18px] text-ink hover:text-ink mb-6">
                <x-application-logo class="w-9 h-9" />
                <span class="tracking-tight">Skill<span class="text-accent">OS</span></span>
            </a>

            <div class="w-full sm:max-w-md card p-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
