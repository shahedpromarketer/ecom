<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Finds a product's size variant from what the customer chose on the website
 * ("L", "Large", "Size: l", "এল", "2XL" …). Used when the store sells the product
 * as a simple product (e.g. CartFlows landing pages with a billing_size field)
 * but the CRM keeps it with sizes.
 */
class SizeMatcher
{
    private const WORDS = [
        'extra extra extra large' => 'xxxl', 'extra extra large' => 'xxl', 'extra large' => 'xl', 'extra small' => 'xs',
        'x large' => 'xl', 'xx large' => 'xxl', 'xxx large' => 'xxxl', 'x small' => 'xs',
        'small' => 's', 'medium' => 'm', 'large' => 'l', 'free size' => 'free', 'freesize' => 'free', 'one size' => 'free',
        '2xl' => 'xxl', '3xl' => 'xxxl', '4xl' => 'xxxxl', '5xl' => 'xxxxxl',
        // Bengali spellings people type on landing pages
        'এক্সট্রা লার্জ' => 'xl', 'এক্সএল' => 'xl', 'ডাবল এক্সএল' => 'xxl', 'এক্সএস' => 'xs',
        'লার্জ' => 'l', 'মিডিয়াম' => 'm', 'স্মল' => 's', 'এল' => 'l', 'এম' => 'm', 'এস' => 's', 'ফ্রি সাইজ' => 'free',
    ];

    /** "Size: Large" → "l", "  2XL " → "xxl", "৪২" → "42". */
    public static function normalize(?string $value): string
    {
        $v = mb_strtolower(trim((string) $value));
        $v = strtr($v, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
        $v = preg_replace('/^(select\s+)?(size|সাইজ)\s*[:：-]?\s*/u', '', $v);   // "Size: L", "সাইজ: এল"
        $v = trim(preg_replace('/[\s\-_]+/u', ' ', $v));
        if (isset(self::WORDS[$v])) {
            return self::WORDS[$v];
        }
        $compact = str_replace(' ', '', $v);

        return self::WORDS[$compact] ?? $compact;
    }

    /**
     * The size the customer picked, from item attributes (["Size" => "L"] or ["Size: L"])
     * or an order-level field such as billing_size.
     */
    public static function chosenSize(array $attributes, ?string $orderSize = null): ?string
    {
        foreach ($attributes as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }
            $k = mb_strtolower((string) $key);
            if (is_string($key) && ! is_numeric($key)) {
                if (str_contains($k, 'size') || str_contains($k, 'সাইজ')) {
                    return trim($value);
                }
                continue;
            }
            // Unnamed values such as "Size: L", or just "L" / "XL" / "42" (API, Google Sheet)
            if (preg_match('/^(size|সাইজ)\s*[:：-]\s*(.+)$/iu', trim($value), $m)) {
                return trim($m[2]);
            }
            if (self::looksLikeSize($value)) {
                return trim($value);
            }
        }

        return $orderSize !== null && trim($orderSize) !== '' ? trim($orderSize) : null;
    }

    public static function looksLikeSize(string $value): bool
    {
        return (bool) preg_match('/^(x{0,5}[sml]|x{1,5}l|\d{1,2}xl|free|\d{2})$/', self::normalize($value));
    }

    /** The product's active variant of that size (sizes added in the CRM count too). */
    public static function find(Product $product, ?string $size): ?ProductVariant
    {
        $want = self::normalize($size);
        if ($want === '') {
            return null;
        }

        return $product->variants()->where('is_active', true)->get()
            ->first(fn (ProductVariant $v) => $v->size !== null && $v->size !== '' && self::normalize($v->size) === $want);
    }
}
