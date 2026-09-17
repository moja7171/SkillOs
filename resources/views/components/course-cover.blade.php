@props(['course'])

@php
    // Deterministic per-course color — same course always renders the same cover,
    // but every course gets its own hue instead of sharing a small palette.
    $seed = crc32($course->slug);
    $hue = $seed % 360;
    $gid = 'cc-'.$course->id;
    $mid = 'cm-'.$course->id;

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
    $lines = array_slice($lines, 0, 2);
    $startY = 130 - (count($lines) - 1) * 17;
@endphp

<div class="relative w-full h-full">
    <svg viewBox="0 0 400 225" class="w-full h-full" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="hsl({{ $hue }} 42% 34%)" />
                <stop offset="100%" stop-color="hsl({{ $hue }} 38% 18%)" />
            </linearGradient>
        </defs>

        <rect width="400" height="225" fill="url(#{{ $gid }})" />

        <text x="24" y="{{ $startY }}" font-size="24" font-weight="700" fill="#ffffff" fill-opacity="0.92" font-family="inherit"
              direction="ltr" text-anchor="start" style="letter-spacing: -.2px; direction: ltr; unicode-bidi: bidi-override;">
            @foreach ($lines as $i => $l)
                <tspan x="24" dy="{{ $i === 0 ? 0 : 30 }}">{{ $l }}</tspan>
            @endforeach
        </text>
    </svg>
</div>
