@props(['course'])

@php
    $color = course_color($course->id);
    $gid = 'cc-'.$course->id;
    $letter = mb_substr($course->title, 0, 1);
    // Deterministic "random" offsets per course so covers don't all look identical.
    $seed = $course->id;
    $bx = 60 + ($seed * 37) % 80;
    $by = 40 + ($seed * 53) % 60;
@endphp

<svg viewBox="0 0 400 225" class="w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="{{ $color }}" stop-opacity="0.95" />
            <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0.55" />
        </linearGradient>
    </defs>
    <rect width="400" height="225" fill="url(#{{ $gid }})" />
    <circle cx="{{ $bx + 260 }}" cy="{{ $by - 10 }}" r="110" fill="#ffffff" fill-opacity="0.08" />
    <circle cx="{{ $bx - 40 }}" cy="{{ $by + 140 }}" r="70" fill="#000000" fill-opacity="0.10" />
    <text x="24" y="175" font-size="120" font-weight="700" fill="#ffffff" fill-opacity="0.16" font-family="inherit">{{ $letter }}</text>
</svg>
