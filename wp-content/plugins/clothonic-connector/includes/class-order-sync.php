<?php
if (!defined('ABSPATH')) exit;

class Clothonic_Order_Sync {

    public static function init()
    {
        add_action('woocommerce_checkout_order_created', [__CLASS__, 'sync_order'], 10, 1);
        // Fallback for older WooCommerce versions
        add_action('woocommerce_new_order', [__CLASS__, 'sync_order_by_id'], 10, 1);
    }

    public static function sync_order($order)
    {
        if (!$order instanceof WC_Order) return;
        self::process_sync($order);
    }

    public static function sync_order_by_id($order_id)
    {
        // woocommerce_checkout_order_created fires on newer WC; skip double-sync
        $order = wc_get_order($order_id);
        if (!$order) return;
        // Only process if the newer hook hasn't already handled it
        if (get_post_meta($order_id, '_clothonic_synced', true)) return;
        self::process_sync($order);
    }

    private static function process_sync(WC_Order $order)
    {
        if (get_post_meta($order->get_id(), '_clothonic_synced', true)) return;

        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $qty     = $item->get_quantity();
            $subtotal = (float) $item->get_subtotal();
            $items[] = [
                'product_name' => $item->get_name(),
                'sku'          => $product ? $product->get_sku() : '',
                'qty'          => $qty,
                'unit_price'   => $qty > 0 ? round($subtotal / $qty, 2) : 0,
                'total'        => $subtotal,
            ];
        }

        // Normalise phone to 11-digit BD format
        $phone = preg_replace('/[^0-9]/', '', $order->get_billing_phone());
        if (strlen($phone) > 11) {
            $phone = substr($phone, -11);
        }

        $data = [
            'source_order_id' => (string) $order->get_id(),
            'source'          => 'wordpress',
            'customer_name'   => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'customer_phone'  => $phone,
            'customer_email'  => $order->get_billing_email(),
            'district'        => $order->get_billing_city(),
            'area'            => $order->get_billing_state(),
            'address'         => $order->get_billing_address_1(),
            'items'           => $items,
            'subtotal'        => (float) $order->get_subtotal(),
            'shipping'        => (float) $order->get_shipping_total(),
            'discount'        => (float) $order->get_discount_total(),
            'total'           => (float) $order->get_total(),
            'payment_method'  => $order->get_payment_method() === 'cod' ? 'cod' : 'online',
            'note'            => $order->get_customer_note(),
            'ip_address'      => $order->get_customer_ip_address(),
        ];

        $result = Clothonic_API::send_order($data);

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'clothonic_order_log',
            [
                'order_id'           => $order->get_id(),
                'phone'              => $phone,
                'ip_address'         => $order->get_customer_ip_address(),
                'synced_at'          => current_time('mysql'),
                'sync_status'        => $result['success'] ? 'success' : 'failed',
                'clothonic_order_id' => $result['data']['order_number'] ?? null,
            ]
        );

        if ($result['success']) {
            update_post_meta($order->get_id(), '_clothonic_synced', true);
            update_post_meta($order->get_id(), '_clothonic_order_number', $result['data']['order_number'] ?? '');
        }
    }
}
