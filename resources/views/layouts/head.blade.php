<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
<meta name="theme-color" content="#f1f3f7">

<title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'SkillOS') }}</title>

{{-- Apply the saved theme before first paint. Light is the default (DECISIONS.md §70). --}}
<script>
    (function () {
        try {
            if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
        } catch (e) {}
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
