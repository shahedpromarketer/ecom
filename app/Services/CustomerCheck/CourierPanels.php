<?php

namespace App\Services\CustomerCheck;

use App\Models\CourierSetting;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * A customer's delivery history as the couriers' merchant panels show it
 * (Pathao "Customer rating", Steadfast "Delivery Risk Check"). These are the panels'
 * own lookups, made with the shop's own courier login — not part of the couriers' public APIs,
 * so a courier can change or stop them; callers treat every failure as "not available".
 *
 * Returns ['total' => int|null, 'success' => int|null, 'failed' => int|null, 'rating' => ?string].
 */
class CourierPanels
{
    // ── Pathao ─────────────────────────────────────────────────────

    public static function pathao(CourierSetting $acc, string $phone): array
    {
        $token = self::pathaoToken($acc);
        $r = Http::withToken($token)->acceptJson()->timeout(20)
            ->post('https://merchant.pathao.com/api/v1/user/success', ['phone' => $phone]);
        if ($r->status() === 401) {
            Cache::forget(self::pathaoKey($acc));
            $r = Http::withToken(self::pathaoToken($acc))->acceptJson()->timeout(20)
                ->post('https://merchant.pathao.com/api/v1/user/success', ['phone' => $phone]);
        }
        if (! $r->successful()) {
            throw new RuntimeException('Pathao: ' . ($r->json('message') ?? 'no answer (' . $r->status() . ')'));
        }

        $d = $r->json('data') ?? [];
        $c = $d['customer'] ?? [];
        $total = $c['total_delivery'] ?? $d['total_delivery'] ?? null;
        $success = $c['successful_delivery'] ?? $d['successful_delivery'] ?? null;

        return [
            'total'   => $total !== null ? (int) $total : null,
            'success' => $success !== null ? (int) $success : null,
            'failed'  => $total !== null && $success !== null ? max(0, (int) $total - (int) $success) : null,
            'rating'  => self::pathaoRating($d['customer_rating'] ?? null),
        ];
    }

    private static function pathaoRating(?string $r): ?string
    {
        if (! $r) {
            return null;
        }

        return [
            'excellent_customer' => 'Excellent', 'good_customer' => 'Good', 'moderate_customer' => 'Moderate',
            'risky_customer' => 'Risky', 'new_customer' => 'New customer',
        ][$r] ?? ucwords(str_replace(['_customer', '_'], ['', ' '], $r));
    }

    private static function pathaoKey(CourierSetting $acc): string
    {
        return 'pathao_panel_token_' . $acc->id . '_' . md5((string) $acc->extra('username'));
    }

    private static function pathaoToken(CourierSetting $acc): string
    {
        return Cache::remember(self::pathaoKey($acc), now()->addHours(6), function () use ($acc) {
            if (! $acc->extra('username') || ! $acc->extra('password')) {
                throw new RuntimeException('Pathao: add the login email and password in Courier Setup.');
            }
            $r = Http::acceptJson()->timeout(20)->post('https://merchant.pathao.com/api/v1/login', [
                'username' => (string) $acc->extra('username'), 'password' => (string) $acc->extra('password'),
            ]);
            $token = $r->json('access_token') ?? $r->json('data.access_token');
            if (! $r->successful() || ! $token) {
                throw new RuntimeException('Pathao: login failed (' . ($r->json('message') ?? $r->status()) . ').');
            }

            return $token;
        });
    }

    // ── Steadfast ──────────────────────────────────────────────────

    public static function steadfast(CourierSetting $acc, string $phone): array
    {
        $email = (string) $acc->extra('panel_email');
        $password = (string) $acc->extra('panel_password');
        if ($email === '' || $password === '') {
            throw new RuntimeException('Steadfast: add the panel login (email + password) in Courier Setup to see this.');
        }

        $key = 'steadfast_panel_cookies_' . $acc->id . '_' . md5($email);
        $jar = Cache::get($key);
        $jar = $jar ? unserialize($jar, ['allowed_classes' => [CookieJar::class, \GuzzleHttp\Cookie\SetCookie::class]]) : null;

        $r = $jar ? self::steadfastCheck($jar, $phone) : null;
        if (! $r || in_array($r->status(), [301, 302, 401, 419], true) || str_contains((string) $r->header('Content-Type'), 'text/html')) {   // signed out
            $jar = self::steadfastLogin($email, $password);
            $r = self::steadfastCheck($jar, $phone);
        }
        Cache::put($key, serialize($jar), now()->addHours(2));

        $d = $r->json() ?? [];
        if (! $r->successful() || isset($d['error'])) {
            throw new RuntimeException('Steadfast: ' . ($d['error'] ?? $d['message'] ?? 'no answer (' . $r->status() . ')'));
        }

        // Current panel: delivered_count / cancelled_count (+ ratios, fraud_reports); older one: total_*.
        $total = $d['total_parcels'] ?? $d['total_parcel'] ?? $d['data']['total_parcels'] ?? null;
        $success = $d['delivered_count'] ?? $d['total_delivered'] ?? $d['delivered'] ?? $d['data']['total_delivered'] ?? null;
        $failed = $d['cancelled_count'] ?? $d['total_cancelled'] ?? $d['cancelled'] ?? $d['data']['total_cancelled'] ?? null;
        if ($total === null && $success !== null && $failed !== null) {
            $total = (int) $success + (int) $failed;
        }

        return [
            'total'   => $total !== null ? (int) $total : null,
            'success' => $success !== null ? (int) $success : null,
            'failed'  => $failed !== null ? (int) $failed : ($total !== null && $success !== null ? max(0, (int) $total - (int) $success) : null),
            'rating'  => null,
            'reports' => is_numeric($d['fraud_reports'] ?? null) ? (int) $d['fraud_reports'] : count($d['total_fraud_reports'] ?? $d['frauds'] ?? []),
        ];
    }

    private static function steadfastLogin(string $email, string $password): CookieJar
    {
        $jar = new CookieJar();
        $page = Http::withOptions(['cookies' => $jar])->timeout(20)->get('https://steadfast.com.bd/login');
        if (! preg_match('/name="_token"\s+value="([^"]+)"/', $page->body(), $m)) {
            throw new RuntimeException('Steadfast: the login page changed.');
        }
        $login = Http::withOptions(['cookies' => $jar, 'allow_redirects' => true])->asForm()->timeout(20)
            ->post('https://steadfast.com.bd/login', ['_token' => $m[1], 'email' => $email, 'password' => $password, 'remember' => 1]);
        if (str_contains($login->body(), 'name="password"') && str_contains($login->body(), 'sf-auth__fields')) {
            throw new RuntimeException('Steadfast: panel login failed — check the email and password.');
        }

        return $jar;
    }

    private static function steadfastCheck(CookieJar $jar, string $phone)
    {
        return Http::withOptions(['cookies' => $jar, 'allow_redirects' => false])->timeout(20)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->get('https://steadfast.com.bd/user/frauds/check/' . rawurlencode($phone));
    }
}
