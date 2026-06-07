<?php
if (!defined('ABSPATH')) exit;

class Clothonic_API {

    public static function send_order(array $data): array
    {
        $token   = get_option('clothonic_access_token', '');
        $crm_url = rtrim(get_option('clothonic_crm_url', ''), '/');

        if (empty($token)) {
            return ['success' => false, 'message' => 'No access token configured'];
        }

        if (empty($crm_url)) {
            return ['success' => false, 'message' => 'No CRM URL configured'];
        }

        $endpoint = $crm_url . '/api/orders/receive';

        $response = wp_remote_post($endpoint, [
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
            'message' => $body['message'] ?? ('HTTP ' . $code),
        ];
    }

    public static function test_connection(string $token = '', string $crm_url = ''): array
    {
        if (empty($token)) {
            $token = get_option('clothonic_access_token', '');
        }
        if (empty($crm_url)) {
            $crm_url = rtrim(get_option('clothonic_crm_url', ''), '/');
        }

        if (empty($token)) {
            return ['success' => false, 'message' => 'Access token is empty'];
        }

        if (empty($crm_url)) {
            return ['success' => false, 'message' => 'CRM URL is empty'];
        }

        $endpoint = $crm_url . '/api/ping';

        $response = wp_remote_get($endpoint, [
            'timeout' => 10,
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
            return ['success' => true, 'message' => $body['message'] ?? 'Connected successfully!'];
        }

        return ['success' => false, 'message' => 'Connection failed: HTTP ' . $code];
    }
}
