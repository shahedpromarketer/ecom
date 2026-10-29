<?php

namespace App\Support;

/**
 * Code 128 (set B) barcodes as inline SVG, for invoices and parcel labels.
 * Any USB barcode scanner or the phone camera on the scan pages can read them.
 */
class Barcode
{
    /** Bar/space widths for symbols 0–106 (106 = stop, which has a 7th element). */
    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const START_B = 104;
    private const STOP = 106;

    /** @return int[] the symbol values, start and checksum included */
    public static function encode(string $text): array
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        $codes = [self::START_B];
        $sum = self::START_B;
        foreach (str_split($text) as $i => $ch) {
            $v = ord($ch) - 32;
            $codes[] = $v;
            $sum += $v * ($i + 1);
        }
        $codes[] = $sum % 103;
        $codes[] = self::STOP;

        return $codes;
    }

    public static function svg(string $text, int $height = 40, float $module = 1.4, bool $label = true): string
    {
        if (trim($text) === '') {
            return '';
        }
        $x = 10 * $module;   // quiet zone
        $bars = '';
        foreach (self::encode($text) as $code) {
            foreach (str_split(self::PATTERNS[$code]) as $i => $w) {
                $w = (int) $w * $module;
                if ($i % 2 === 0) {
                    $bars .= sprintf('<rect x="%.2f" y="0" width="%.2f" height="%d"/>', $x, $w, $height);
                }
                $x += $w;
            }
        }
        $width = $x + 10 * $module;
        $total = $height + ($label ? 14 : 0);
        $caption = $label
            ? sprintf('<text x="%.2f" y="%d" font-family="monospace" font-size="11" text-anchor="middle" fill="#000">%s</text>', $width / 2, $height + 12, e($text))
            : '';

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" class="ep-barcode" width="%.0f" height="%d" viewBox="0 0 %.2f %d" role="img" aria-label="%s"><rect width="100%%" height="100%%" fill="#fff"/><g fill="#000">%s</g>%s</svg>',
            $width, $total, $width, $total, e($text), $bars, $caption
        );
    }
}
