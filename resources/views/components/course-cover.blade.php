@props(['course'])

@php
    $color = course_color($course->id);
    $gid = 'cc-'.$course->id;
    $pid = 'cp-'.$course->id;

    $icon = match ($course->category ?? '') {
        'مسیر رهبری فنی' => 'trophy',
        'اسکرام و اجایل' => 'refresh',
        'برنامه‌نویسی' => 'text',
        default => 'sparkle',
    };
@endphp

<div class="relative w-full h-full">
    <svg viewBox="0 0 400 225" class="w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="{{ $color }}" />
                <stop offset="100%" stop-color="color-mix(in srgb, {{ $color }} 55%, black)" />
            </linearGradient>
            <pattern id="{{ $pid }}" width="26" height="26" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.3" fill="#ffffff" fill-opacity="0.15" />
            </pattern>
        </defs>
        <rect width="400" height="225" fill="url(#{{ $gid }})" />
        <rect width="400" height="225" fill="url(#{{ $pid }})" />
        <circle cx="350" cy="15" r="130" fill="#ffffff" fill-opacity="0.07" />
    </svg>
    <div class="absolute inset-0 grid place-items-center">
        <x-icon :name="$icon" class="w-9 h-9 text-white/80" />
    </div>
</div>
