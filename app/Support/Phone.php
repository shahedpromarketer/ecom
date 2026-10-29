<?php

namespace App\Support;

class Phone
{
    /**
     * Bangladeshi mobile numbers in one shape: 01XXXXXXXXX.
     * "+880 1712-345678", "8801712345678" and "01712345678" all become "01712345678".
     * Anything that does not look like a BD mobile is returned as digits only.
     */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '1') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /** For "contains" searches on a partly typed number: "+880 1912" → "01912". */
    public static function searchFragment(?string $input): string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        return str_starts_with($digits, '880') ? substr($digits, 2) : $digits;
    }

    public static function isValidBdMobile(?string $phone): bool
    {
        return (bool) preg_match('/^01[3-9]\d{8}$/', self::normalize($phone));
    }
}
