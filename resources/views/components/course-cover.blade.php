@props(['course'])

@php
    // Deterministic per-course "identicon" — same course always renders the same
    // cover, but every course looks distinct (hue, pattern and glow all derive
    // from the slug, not from a small shared palette).
    $seed = crc32($course->slug);
    $hue = $seed % 360;
    $hue2 = ($hue + 40) % 360;
    $gid = 'cc-'.$course->id;
    $pid = 'cp-'.$course->id;
    $mid = 'cm-'.$course->id;

    $icon = match ($course->category ?? '') {
        'مسیر رهبری فنی' => 'trophy',
        'اسکرام و اجایل' => 'refresh',
        'برنامه‌نویسی' => 'text',
        default => 'sparkle',
    };

    $patternType = $seed % 3;
    $rot = ($seed % 60) - 30;
    $glowX = 240 + ($seed % 100);
    $glowY = -10 + (intdiv($seed, 7) % 50);

    // English wordmark, derived from the (always-English) slug — e.g.
    // "requirements-engineering" -> "Requirements Engineering". Wrapped by hand
    // into at most 3 lines since SVG <text> doesn't auto-wrap.
    $english = ucwords(str_replace('-', ' ', $course->slug));
    $words = explode(' ', $english);
    $lines = [];
    $line = '';
    foreach ($words as $w) {
        $candidate = trim($line.' '.$w);
        if (mb_strlen($candidate) > 17 && $line !== '') {
            $lines[] = $line;
            $line = $w;
        } else {
            $line = $candidate;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }
    $lines = array_slice($lines, 0, 3);
    $startY = 195 - (count($lines) - 1) * 30;
@endphp

<div class="relative w-full h-full">
    <svg viewBox="0 0 400 225" class="w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="hsl({{ $hue }} 72% 45%)" />
                <stop offset="100%" stop-color="hsl({{ $hue2 }} 65% 22%)" />
            </linearGradient>

            <linearGradient id="{{ $mid }}" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#000000" stop-opacity="0" />
                <stop offset="55%" stop-color="#000000" stop-opacity="0.15" />
                <stop offset="100%" stop-color="#000000" stop-opacity="0.62" />
            </linearGradient>

            @if ($patternType === 0)
                <pattern id="{{ $pid }}" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.4" fill="#ffffff" fill-opacity="0.16" />
                </pattern>
            @elseif ($patternType === 1)
                <pattern id="{{ $pid }}" width="22" height="22" patternUnits="userSpaceOnUse" patternTransform="rotate({{ $rot }})">
                    <line x1="0" y1="0" x2="0" y2="22" stroke="#ffffff" stroke-opacity="0.10" stroke-width="7" />
                </pattern>
            @else
                <pattern id="{{ $pid }}" width="46" height="46" patternUnits="userSpaceOnUse">
                    <circle cx="23" cy="23" r="16" fill="none" stroke="#ffffff" stroke-opacity="0.13" stroke-width="2" />
                </pattern>
            @endif
        </defs>

        <rect width="400" height="225" fill="url(#{{ $gid }})" />
        <rect width="400" height="225" fill="url(#{{ $pid }})" />
        <circle cx="{{ $glowX }}" cy="{{ $glowY }}" r="130" fill="#ffffff" fill-opacity="0.08" />
        <rect width="400" height="225" fill="url(#{{ $mid }})" />

        <text x="24" y="{{ $startY }}" font-size="26" font-weight="700" fill="#ffffff" font-family="inherit" style="letter-spacing: -.2px">
            @foreach ($lines as $i => $l)
                <tspan x="24" dy="{{ $i === 0 ? 0 : 30 }}">{{ $l }}</tspan>
            @endforeach
        </text>
    </svg>
    <div class="absolute bottom-2.5 end-3">
        <x-icon :name="$icon" class="w-5 h-5 text-white/55" />
    </div>
</div>
