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
