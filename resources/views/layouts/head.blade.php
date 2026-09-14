<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
<meta name="theme-color" content="#0e1014">

<title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'SkillOS') }}</title>

{{-- Apply the saved theme before first paint. Dark is the default. --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t !== 'light') document.documentElement.classList.add('dark');
        } catch (e) { document.documentElement.classList.add('dark'); }
    })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
