<?php
if (!defined('ABSPATH')) exit;

class Clothonic_Duplicate_Check {

    public static function init()
    {
        if (get_option('clothonic_check_duplicate') !== '1') return;
        add_action('woocommerce_checkout_process', [__CLASS__, 'check_duplicate']);
    }

    public static function check_duplicate()
    {
        if (get_option('clothonic_check_duplicate') !== '1') return;

        $hours = (int) get_option('clothonic_duplicate_hours', 24);
        $phone = isset($_POST['billing_phone']) ? sanitize_text_field($_POST['billing_phone']) : '';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) > 11) {
            $phone = substr($phone, -11);
        }
        $ip    = self::get_client_ip();
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        global $wpdb;
        $table = $wpdb->prefix . 'clothonic_order_log';

        if (!empty($phone)) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE phone = %s AND synced_at > %s AND sync_status = 'success'",
                $phone,
                $since
            ));

            if ($count > 0) {
                wc_add_notice(
                    sprintf(
                        '⚠️ আপনি সম্প্রতি এই নম্বর দিয়ে অর্ডার করেছেন। অনুগ্রহ করে %d ঘন্টা পরে আবার চেষ্টা করুন।',
                        $hours
                    ),
                    'error'
                );
                return;
            }
        }

        if (!empty($ip)) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE ip_address = %s AND synced_at > %s AND sync_status = 'success'",
                $ip,
                $since
            ));

            if ($count > 0) {
                wc_add_notice(
                    sprintf(
                        '⚠️ আপনার ডিভাইস থেকে সম্প্রতি একটি অর্ডার করা হয়েছে। অনুগ্রহ করে %d ঘন্টা পরে আবার চেষ্টা করুন।',
                        $hours
                    ),
                    'error'
                );
                return;
            }
        }
    }

    private static function get_client_ip(): string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key]);
                return trim($ip[0]);
            }
        }

        return '';
    }
}
