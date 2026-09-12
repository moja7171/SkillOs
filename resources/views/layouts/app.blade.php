<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-bg">
        @include('layouts.navigation')

        <main>
            {{ $slot }}
        </main>
    </body>
</html>
