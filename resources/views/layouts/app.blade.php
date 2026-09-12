<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-bg">
        @isset($nav)
            {{ $nav }}
        @else
            @include('layouts.navigation')
        @endisset

        <main>
            {{ $slot }}
        </main>
    </body>
</html>
