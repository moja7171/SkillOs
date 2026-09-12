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
