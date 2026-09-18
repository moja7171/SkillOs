<?php

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Morilog\Jalali\Jalalian;

if (! function_exists('fa_num')) {
    /**
     * Render a number (or any string containing digits) with Persian digits.
     */
    function fa_num(string|int|float|null $value): string
    {
        return str_replace(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], (string) $value);
    }
}

if (! function_exists('fa_date')) {
    /**
     * Format a date in the Jalali calendar with Persian digits. Storage stays Gregorian.
     */
    function fa_date(CarbonInterface|string|null $date, string $format = 'j F Y'): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return fa_num(Jalalian::fromCarbon($carbon)->format($format));
    }
}

if (! function_exists('media_url')) {
    /**
     * Resolve a stored media URL for the current environment: `/media/...` paths are
     * re-based onto MEDIA_BASE_URL when it is set (see config/media.php), anything else
     * (absolute URLs, YouTube, ...) is returned untouched.
     */
    function media_url(?string $url): ?string
    {
        $base = config('media.base_url');

        if ($url === null || ! $base || ! str_starts_with($url, '/media/')) {
            return $url;
        }

        return rtrim($base, '/').substr($url, strlen('/media'));
    }
}

if (! function_exists('media_download_url')) {
    /**
     * Resolve a stored media URL onto the download host (config('media.download_base_url')),
     * or null when that host isn't configured or the URL isn't a `/media/...` path. Views use
     * this as the primary `src` with media_url() as the browser-side fallback — see
     * resources/js/player.js.
     */
    function media_download_url(?string $url): ?string
    {
        $base = config('media.download_base_url');

        if ($url === null || ! $base || ! str_starts_with($url, '/media/')) {
            return null;
        }

        return rtrim($base, '/').substr($url, strlen('/media'));
    }
}

if (! function_exists('course_color')) {
    /**
     * A stable, distinctive accent color for a course's monogram avatar (catalog cards,
     * Home's "my courses" list). A curated palette rather than a generated hsl() — hand-
     * picked so every hue stays vivid on the dark surface instead of risking a muddy one.
     */
    function course_color(int $id): string
    {
        // No orange/amber tone here on purpose -- that's --accent/--warn, already loaded
        // with meaning (primary actions, streak) elsewhere in the app.
        static $palette = ['#5b8def', '#2fbf8f', '#b58cff', '#ef5a6f', '#06b6d4', '#ec4899', '#84cc16', '#8b5cf6'];

        return $palette[$id % count($palette)];
    }
}
