<?php
if (!defined('ABSPATH')) exit;

class Clothonic_API {

    const ENDPOINT      = 'https://laravel.netpilotdigital.com/api/orders/receive';
    const PING_ENDPOINT = 'https://laravel.netpilotdigital.com/api/ping';
    const CRM_URL       = 'https://laravel.netpilotdigital.com';

    public static function get_token(): string
    {
        return get_option('clothonic_access_token', '');
    }

    public static function send_order(array $data): array
    {
        $token = self::get_token();

        if (empty($token)) {
            return ['success' => false, 'message' => 'No access token configured'];
        }

        $response = wp_remote_post(self::ENDPOINT, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'body' => wp_json_encode($data),
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 || $code === 201) {
            return ['success' => true, 'data' => $body];
        }

        return [
            'success' => false,
            'message' => $body['message'] ?? 'HTTP ' . $code,
        ];
    }

    public static function test_connection(string $token = ''): array
    {
        if (empty($token)) {
            $token = self::get_token();
        }

        if (empty($token)) {
            return ['success' => false, 'message' => 'Access token is empty'];
        }

        $response = wp_remote_get(self::PING_ENDPOINT, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200) {
            return ['success' => true, 'message' => 'Connected to Clothonic CRM ✅'];
        }

        return [
            'success' => false,
            'message' => 'Connection failed: ' . ($body['error'] ?? 'HTTP ' . $code),
        ];
    }
}
