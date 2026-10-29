<?php

namespace App\Support;

/**
 * Signing used between the CRM and the Ecom Pilot WordPress plugin (both directions).
 *
 *   X-EP-Site:      the store's id in the CRM
 *   X-EP-Timestamp: unix seconds
 *   X-EP-Signature: hex HMAC-SHA256 of "{timestamp}.{raw body}" with the store's API key
 *
 * The key itself never travels over the wire. Requests older than 5 minutes are refused.
 */
class PluginSignature
{
    public const MAX_SKEW = 300;

    public static function sign(string $secret, string $body, ?int $timestamp = null): array
    {
        $timestamp ??= time();

        return [
            'timestamp' => $timestamp,
            'signature' => hash_hmac('sha256', $timestamp . '.' . $body, $secret),
        ];
    }

    public static function verify(string $secret, string $body, $timestamp, $signature): bool
    {
        if (! is_numeric($timestamp) || ! is_string($signature) || $signature === '') {
            return false;
        }
        if (abs(time() - (int) $timestamp) > self::MAX_SKEW) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp . '.' . $body, $secret), $signature);
    }

    /** What the shop owner pastes into the plugin: "{siteId}.{apiKey}". */
    public static function connectionKey(int $siteId, string $apiKey): string
    {
        return $siteId . '.' . $apiKey;
    }
}
