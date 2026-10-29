<?php
/**
 * Plugin Name:       Ecom Pilot Connector
 * Plugin URI:        https://crm.netpilotdigital.com
 * Description:       Sends every WooCommerce order to your Ecom Pilot CRM, saves unfinished checkouts, checks blacklisted / duplicate orders at checkout, and receives status updates from the CRM.
 * Version:           2.2.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Ecom Pilot
 * License:           GPLv2 or later
 * Text Domain:       ecom-pilot
 */

if (! defined('ABSPATH')) {
    exit;
}

define('EP_VERSION', '2.2.0');
define('EP_OPTION', 'ecom_pilot_settings');
define('EP_RETRY_OPTION', 'ecom_pilot_retry_queue');

// Works with WooCommerce's High-Performance Order Storage.
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/* ───────────────────────── Settings ───────────────────────── */

function ep_settings(): array
{
    $s = get_option(EP_OPTION, []);

    return wp_parse_args(is_array($s) ? $s : [], [
        'crm_url'        => 'https://crm.netpilotdigital.com',
        'connection_key' => '',
        'checkout_check'     => 1,
        'capture_incomplete' => 1,
    ]);
}

/** "{siteId}.{secret}" → [siteId, secret] */
function ep_credentials(): ?array
{
    $key = trim((string) ep_settings()['connection_key']);
    if (! preg_match('/^(\d+)\.([A-Za-z0-9]{20,})$/', $key, $m)) {
        return null;
    }

    return [(int) $m[1], $m[2]];
}

/* ───────────────────────── Signed HTTP ───────────────────────── */

/** POST JSON to the CRM, signed. Returns ['ok' => bool, 'code' => int, 'body' => array|null, 'error' => string]. */
function ep_post(string $path, array $data, int $timeout = 8): array
{
    $cred = ep_credentials();
    if (! $cred) {
        return ['ok' => false, 'code' => 0, 'body' => null, 'error' => 'Connection key missing or invalid.'];
    }

    list($siteId, $secret) = $cred;
    $body = wp_json_encode($data);
    $ts = (string) time();

    $res = wp_remote_post(rtrim(ep_settings()['crm_url'], '/') . '/api/v1/store/' . ltrim($path, '/'), [
        'timeout' => $timeout,
        'headers' => [
            'Content-Type'   => 'application/json',
            'Accept'         => 'application/json',
            'X-EP-Site'      => (string) $siteId,
            'X-EP-Timestamp' => $ts,
            'X-EP-Signature' => hash_hmac('sha256', $ts . '.' . $body, $secret),
        ],
        'body' => $body,
    ]);

    if (is_wp_error($res)) {
        return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $res->get_error_message()];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $json = json_decode(wp_remote_retrieve_body($res), true);

    return [
        'ok'    => $code >= 200 && $code < 300,
        'code'  => $code,
        'body'  => is_array($json) ? $json : null,
        'error' => is_array($json) && isset($json['message']) ? (string) $json['message'] : ('HTTP ' . $code),
    ];
}

/** Check a request the CRM sent us (status updates). */
function ep_verify_incoming(WP_REST_Request $request): bool
{
    $cred = ep_credentials();
    if (! $cred) {
        return false;
    }
    list($siteId, $secret) = $cred;

    $ts  = (string) $request->get_header('x-ep-timestamp');
    $sig = (string) $request->get_header('x-ep-signature');

    if ((int) $request->get_header('x-ep-site') !== $siteId || ! ctype_digit($ts) || abs(time() - (int) $ts) > 300 || $sig === '') {
        return false;
    }

    return hash_equals(hash_hmac('sha256', $ts . '.' . $request->get_body(), $secret), $sig);
}

/* ───────────────────────── Order → CRM ───────────────────────── */

function ep_order_payload(WC_Order $order): array
{
    $states = WC()->countries ? WC()->countries->get_states($order->get_billing_country() ?: 'BD') : [];
    $address = function ($type) use ($order, $states) {
        $get = function ($field) use ($order, $type) {
            $method = "get_{$type}_{$field}";
            return method_exists($order, $method) ? (string) $order->$method() : '';
        };
        $state = $get('state');

        return [
            'first_name' => $get('first_name'),
            'last_name'  => $get('last_name'),
            'phone'      => $get('phone'),
            'email'      => $type === 'billing' ? $get('email') : '',
            'address_1'  => $get('address_1'),
            'address_2'  => $get('address_2'),
            'city'       => $get('city'),
            'state'      => $state,
            'state_name' => is_array($states) && isset($states[$state]) ? html_entity_decode($states[$state]) : '',
            'postcode'   => $get('postcode'),
        ];
    };

    $items = [];
    foreach ($order->get_items() as $item) {
        if (! $item instanceof WC_Order_Item_Product) {
            continue;
        }
        $product = $item->get_product();
        $attributes = [];
        foreach ($item->get_formatted_meta_data('_', true) as $meta) {
            $attributes[wc_clean($meta->display_key)] = wp_strip_all_tags($meta->display_value);
        }
        $qty = max(1, (int) $item->get_quantity());

        $items[] = [
            'product_id'   => (int) $item->get_product_id(),
            'variation_id' => (int) $item->get_variation_id(),
            'name'         => $item->get_name(),
            'sku'          => $product ? (string) $product->get_sku() : '',
            'qty'          => $qty,
            'price'        => round((float) $item->get_total() / $qty, 2),
            'subtotal'     => (float) $item->get_subtotal(),
            'total'        => (float) $item->get_total(),
            'attributes'   => $attributes,
        ];
    }

    $created = $order->get_date_created();

    return [
        'id'             => $order->get_id(),
        'number'         => (string) $order->get_order_number(),
        'status'         => $order->get_status(),
        'created_at'     => $created ? $created->format(DATE_ATOM) : null,
        'currency'       => $order->get_currency(),
        'total'          => (float) $order->get_total(),
        'subtotal'       => (float) $order->get_subtotal(),
        'discount_total' => (float) $order->get_discount_total(),
        'shipping_total' => (float) $order->get_shipping_total(),
        'payment_method' => (string) $order->get_payment_method(),
        'payment_title'  => (string) $order->get_payment_method_title(),
        'paid'           => (bool) $order->is_paid(),
        'customer_note'  => (string) $order->get_customer_note(),
        'customer_ip'    => (string) $order->get_customer_ip_address(),
        'user_agent'     => (string) $order->get_customer_user_agent(),
        'billing'        => $address('billing'),
        'shipping'       => $address('shipping'),
        'items'          => $items,
        'attribution'    => [
            'source_type'  => (string) $order->get_meta('_wc_order_attribution_source_type'),
            'utm_source'   => (string) $order->get_meta('_wc_order_attribution_utm_source'),
            'utm_medium'   => (string) $order->get_meta('_wc_order_attribution_utm_medium'),
            'utm_campaign' => (string) $order->get_meta('_wc_order_attribution_utm_campaign'),
            'referrer'     => (string) $order->get_meta('_wc_order_attribution_referrer'),
        ],
    ];
}

/** Orders created/changed during this request, sent once the request is done (items are saved by then). */
$GLOBALS['ep_pending_orders'] = [];
$GLOBALS['ep_from_crm'] = false;

function ep_queue_order($order_id)
{
    if (! empty($GLOBALS['ep_from_crm']) || ! ep_credentials()) {
        return;
    }
    $GLOBALS['ep_pending_orders'][(int) $order_id] = true;
}
add_action('woocommerce_new_order', 'ep_queue_order', 20);
add_action('woocommerce_update_order', 'ep_queue_order', 20);
add_action('woocommerce_order_status_changed', 'ep_queue_order', 20);

function ep_send_order($order_id): bool
{
    $order = wc_get_order($order_id);
    if (! $order || $order->get_type() !== 'shop_order' || in_array($order->get_status(), ['checkout-draft', 'auto-draft', 'trash'], true)) {
        return true;   // nothing to send
    }

    $res = ep_post('orders', ep_order_payload($order));
    if ($res['ok'] && ! empty($res['body']['order_number'])) {
        $order->update_meta_data('_ecom_pilot_order', $res['body']['order_number']);
        $GLOBALS['ep_from_crm'] = true;   // saving our own meta must not queue the order again
        $order->save_meta_data();
        $GLOBALS['ep_from_crm'] = false;
    }

    return $res['ok'];
}

add_action('shutdown', function () {
    $ids = array_keys($GLOBALS['ep_pending_orders'] ?? []);
    if (! $ids) {
        return;
    }
    $GLOBALS['ep_pending_orders'] = [];

    if (function_exists('fastcgi_finish_request')) {
        // Send the shopper their page first, then talk to the CRM.
        @fastcgi_finish_request();
    }

    foreach ($ids as $id) {
        if (! ep_send_order($id)) {
            ep_add_retry($id);
        }
    }
}, 20);

function ep_add_retry($order_id)
{
    $queue = (array) get_option(EP_RETRY_OPTION, []);
    $queue[(int) $order_id] = ($queue[(int) $order_id] ?? 0) + 1;
    update_option(EP_RETRY_OPTION, array_slice($queue, -500, null, true), false);
}

// Retry failed sends every 5 minutes (gives up after 50 tries).
add_filter('cron_schedules', function ($s) {
    $s['ep_five_minutes'] = ['interval' => 300, 'display' => 'Every 5 minutes (Ecom Pilot)'];
    return $s;
});
add_action('ep_retry_cron', function () {
    $queue = (array) get_option(EP_RETRY_OPTION, []);
    foreach ($queue as $id => $tries) {
        if (ep_send_order($id) || $tries >= 50) {
            unset($queue[$id]);
        } else {
            $queue[$id] = $tries + 1;
        }
    }
    update_option(EP_RETRY_OPTION, $queue, false);
});
register_activation_hook(__FILE__, function () {
    if (! wp_next_scheduled('ep_retry_cron')) {
        wp_schedule_event(time() + 300, 'ep_five_minutes', 'ep_retry_cron');
    }
});
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ep_retry_cron');
});

/* ───────────────────────── Products & stock → CRM ───────────────────────── */

function ep_product_payload(WC_Product $product): array
{
    $images = [];
    foreach (array_merge([$product->get_image_id()], $product->get_gallery_image_ids()) as $imageId) {
        $url = $imageId ? wp_get_attachment_image_url($imageId, 'large') : '';
        if ($url) {
            $images[] = $url;
        }
    }

    $data = [
        'id'                => $product->get_id(),
        'name'              => $product->get_name(),
        'slug'              => $product->get_slug(),
        'type'              => $product->get_type(),
        'status'            => $product->get_status(),
        'sku'               => (string) $product->get_sku(),
        'regular_price'     => (string) $product->get_regular_price(),
        'sale_price'        => (string) $product->get_sale_price(),
        'manage_stock'      => (bool) $product->get_manage_stock(),
        'stock_quantity'    => $product->get_stock_quantity(),
        'stock_status'      => $product->get_stock_status(),
        'short_description' => (string) $product->get_short_description(),
        'categories'        => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']),
        'images'            => array_values(array_unique($images)),
        'variations'        => [],
    ];

    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $childId) {
            $v = wc_get_product($childId);
            if (! $v) {
                continue;
            }
            $attributes = [];
            foreach ($v->get_variation_attributes(false) as $name => $value) {
                // Global attributes store the term slug ("l"); send the name customers see ("L").
                if ($value !== '' && taxonomy_exists($name)) {
                    $term = get_term_by('slug', $value, $name);
                    if ($term && ! is_wp_error($term)) {
                        $value = $term->name;
                    }
                }
                $attributes[wc_attribute_label($name, $product)] = $value;
            }
            $data['variations'][] = [
                'id'             => $v->get_id(),
                'sku'            => (string) $v->get_sku(),
                'status'         => $v->get_status(),
                'regular_price'  => (string) $v->get_regular_price(),
                'sale_price'     => (string) $v->get_sale_price(),
                'manage_stock'   => $v->get_manage_stock() === true,   // 'parent' means the product manages it
                'stock_quantity' => $v->get_stock_quantity(),
                'stock_status'   => $v->get_stock_status(),
                'attributes'     => $attributes,
            ];
        }
    }

    return $data;
}

$GLOBALS['ep_pending_products'] = [];
$GLOBALS['ep_pending_stock'] = [];

function ep_queue_product($product_id)
{
    if (! empty($GLOBALS['ep_from_crm']) || ! ep_credentials()) {
        return;
    }
    $product = wc_get_product($product_id);
    if ($product && $product->is_type('variation')) {
        $product_id = $product->get_parent_id();
    }
    $GLOBALS['ep_pending_products'][(int) $product_id] = true;
}
add_action('woocommerce_new_product', 'ep_queue_product', 20);
add_action('woocommerce_update_product', 'ep_queue_product', 20);
add_action('woocommerce_update_product_variation', 'ep_queue_product', 20);

function ep_queue_stock($product)
{
    if (! ep_credentials() || ! $product instanceof WC_Product) {
        return;
    }
    $isVariation = $product->is_type('variation');
    $GLOBALS['ep_pending_stock'][$product->get_id()] = [
        'product_id'     => $isVariation ? $product->get_parent_id() : $product->get_id(),
        'variation_id'   => $isVariation ? $product->get_id() : 0,
        'manage_stock'   => $product->get_manage_stock() === true,
        'stock_quantity' => $product->get_stock_quantity(),
        'stock_status'   => $product->get_stock_status(),
    ];
}
add_action('woocommerce_product_set_stock', 'ep_queue_stock', 20);
add_action('woocommerce_variation_set_stock', 'ep_queue_stock', 20);
add_action('woocommerce_product_set_stock_status', function ($id) { ep_queue_stock(wc_get_product($id)); }, 20);
add_action('woocommerce_variation_set_stock_status', function ($id) { ep_queue_stock(wc_get_product($id)); }, 20);

add_action('shutdown', function () {
    $products = array_keys($GLOBALS['ep_pending_products'] ?? []);
    $stock = array_values($GLOBALS['ep_pending_stock'] ?? []);
    $GLOBALS['ep_pending_products'] = [];
    $GLOBALS['ep_pending_stock'] = [];
    if (! $products && ! $stock) {
        return;
    }
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }
    foreach ($products as $id) {
        $p = wc_get_product($id);
        if ($p && ! $p->is_type('variation')) {
            ep_post('products', ep_product_payload($p), 15);
        }
    }
    foreach ($stock as $s) {
        ep_post('stock', $s, 5);
    }
}, 25);

/* ───────────────────────── Unfinished checkouts ("failed orders") ───────────────────────── */

// A tiny script on the checkout page: once a full phone number is typed, tell our own server
// (which signs and forwards it to the CRM — the key never reaches the browser).
add_action('wp_enqueue_scripts', function () {
    if (! function_exists('is_checkout') || ! is_checkout() || is_order_received_page() || ! ep_credentials()) {
        return;
    }
    if (empty(ep_settings()['capture_incomplete'])) {
        return;
    }

    wp_register_script('ecom-pilot-capture', '', [], EP_VERSION, true);
    wp_enqueue_script('ecom-pilot-capture');
    wp_add_inline_script('ecom-pilot-capture', 'window.EcomPilotCapture=' . wp_json_encode([
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ep_incomplete'),
    ]) . ';' . <<<'JS'
(function () {
  var cfg = window.EcomPilotCapture, timer = null, lastSent = '';
  var pick = function (sel) {
    var el = document.querySelector(sel);
    return el && el.value ? String(el.value).trim() : '';
  };
  var collect = function () {
    var phone = pick('#billing_phone, #billing-phone, #shipping-phone, input[name="billing_phone"]');
    var first = pick('#billing_first_name, #billing-first_name, #shipping-first_name');
    var last  = pick('#billing_last_name, #billing-last_name, #shipping-last_name');
    return {
      phone: phone,
      name: (first + ' ' + last).trim(),
      address: [pick('#billing_address_1, #billing-address_1, #shipping-address_1'), pick('#billing_address_2, #billing-address_2, #shipping-address_2'), pick('#billing_city, #billing-city, #shipping-city')].filter(Boolean).join(', '),
      email: pick('#billing_email, #email')
    };
  };
  var send = function () {
    var d = collect();
    if (d.phone.replace(/\D/g, '').length < 11) return;
    var key = JSON.stringify(d);
    if (key === lastSent) return;
    lastSent = key;
    var body = new URLSearchParams({ action: 'ep_incomplete', nonce: cfg.nonce, phone: d.phone, name: d.name, address: d.address, email: d.email, page_url: location.href });
    if (navigator.sendBeacon) { navigator.sendBeacon(cfg.url, body); }
    else { fetch(cfg.url, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true }); }
  };
  var watch = function (e) {
    if (!e.target || !e.target.matches || !e.target.matches('input, textarea, select')) return;
    clearTimeout(timer);
    timer = setTimeout(send, 1500);
  };
  document.addEventListener('input', watch, true);
  document.addEventListener('change', watch, true);
  window.addEventListener('pagehide', send);
})();
JS
    );
});

function ep_capture_incomplete()
{
    check_ajax_referer('ep_incomplete', 'nonce');

    $session = (function_exists('WC') && WC()->session) ? (string) WC()->session->get_customer_id() : '';
    $session = $session ?: 'ip:' . WC_Geolocation::get_ip_address();

    // At most one send every 10 seconds per visitor.
    $throttle = 'ep_inc_' . md5($session);
    if (get_transient($throttle)) {
        wp_send_json(['ok' => true, 'throttled' => true]);
    }
    set_transient($throttle, 1, 10);

    $items = [];
    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_cart() as $line) {
            $product = $line['data'] ?? null;
            if (! $product instanceof WC_Product) {
                continue;
            }
            $variant = [];
            foreach ((array) ($line['variation'] ?? []) as $attr => $value) {
                $variant[] = wc_attribute_label(str_replace('attribute_', '', $attr), $product) . ': ' . $value;
            }
            $items[] = [
                'name'    => $product->get_name(),
                'qty'     => (int) $line['quantity'],
                'price'   => (float) wc_get_price_to_display($product),
                'variant' => implode(', ', $variant),
            ];
        }
    }

    $res = ep_post('incomplete', [
        'session_id' => $session,
        'phone'      => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
        'name'       => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
        'email'      => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'address'    => sanitize_text_field(wp_unslash($_POST['address'] ?? '')),
        'page_url'   => esc_url_raw(wp_unslash($_POST['page_url'] ?? '')),
        'items'      => $items,
        'total'      => (function_exists('WC') && WC()->cart) ? (float) WC()->cart->get_total('edit') : 0,
        'ip'         => WC_Geolocation::get_ip_address(),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
    ], 4);

    wp_send_json(['ok' => $res['ok']]);
}
add_action('wp_ajax_ep_incomplete', 'ep_capture_incomplete');
add_action('wp_ajax_nopriv_ep_incomplete', 'ep_capture_incomplete');

/* ───────────────────────── Checkout check ───────────────────────── */

/**
 * Ask the CRM whether this phone/IP may order (blacklist, blocked IP, repeat orders).
 * Returns an error message to show, or '' to allow. Fails open if the CRM is unreachable.
 * Headless / custom checkouts can call this too: ecom_pilot_check_order($phone, $ip).
 */
function ecom_pilot_check_order(string $phone, string $ip = ''): string
{
    if (! ep_settings()['checkout_check'] || ! ep_credentials() || $phone === '') {
        return '';
    }

    $res = ep_post('validate', ['phone' => $phone, 'ip' => $ip ?: WC_Geolocation::get_ip_address()], 4);
    if ($res['ok'] && isset($res['body']['allowed']) && $res['body']['allowed'] === false) {
        return (string) ($res['body']['message'] ?? __('Sorry, this order cannot be placed.', 'ecom-pilot'));
    }

    return '';
}

// Classic checkout
add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
    if ($errors->has_errors()) {
        return;
    }
    $message = ecom_pilot_check_order((string) ($data['billing_phone'] ?? ''));
    if ($message !== '') {
        $errors->add('ecom_pilot_blocked', $message);
    }
}, 20, 2);

// Block (Store API) checkout
add_action('woocommerce_store_api_checkout_update_order_from_request', function ($order) {
    $message = ecom_pilot_check_order((string) $order->get_billing_phone());
    if ($message !== '' && class_exists('\Automattic\WooCommerce\StoreApi\Exceptions\RouteException')) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('ecom_pilot_blocked', $message, 400);
    }
}, 20, 1);

/* ───────────────────────── Status from CRM ───────────────────────── */

// Two extra statuses the CRM uses.
add_action('init', function () {
    register_post_status('wc-ep-in-courier', [
        'label' => 'In Courier', 'public' => true, 'show_in_admin_status_list' => true, 'show_in_admin_all_list' => true,
        'label_count' => _n_noop('In Courier <span class="count">(%s)</span>', 'In Courier <span class="count">(%s)</span>'),
    ]);
    register_post_status('wc-ep-returned', [
        'label' => 'Returned', 'public' => true, 'show_in_admin_status_list' => true, 'show_in_admin_all_list' => true,
        'label_count' => _n_noop('Returned <span class="count">(%s)</span>', 'Returned <span class="count">(%s)</span>'),
    ]);
});
add_filter('wc_order_statuses', function ($statuses) {
    $statuses['wc-ep-in-courier'] = 'In Courier';
    $statuses['wc-ep-returned'] = 'Returned';
    return $statuses;
});

add_action('rest_api_init', function () {
    register_rest_route('ecom-pilot/v1', '/order-status', [
        'methods'             => 'POST',
        'permission_callback' => 'ep_verify_incoming',
        'callback'            => function (WP_REST_Request $request) {
            $order = wc_get_order((int) $request->get_param('order_id'));
            $status = sanitize_key((string) $request->get_param('status'));

            if (! $order) {
                return new WP_REST_Response(['ok' => false, 'error' => 'order_not_found'], 404);
            }
            if (! array_key_exists('wc-' . $status, wc_get_order_statuses())) {
                return new WP_REST_Response(['ok' => false, 'error' => 'unknown_status'], 422);
            }

            $GLOBALS['ep_from_crm'] = true;   // do not send this change back to the CRM
            if ($order->get_status() !== $status) {
                $note = sanitize_text_field((string) $request->get_param('note'));
                $tracking = sanitize_text_field((string) $request->get_param('tracking'));
                $order->update_status($status, trim($note . ($tracking ? ' — tracking ' . $tracking : '')));
            }
            $GLOBALS['ep_from_crm'] = false;

            return new WP_REST_Response(['ok' => true, 'status' => $order->get_status()], 200);
        },
    ]);

    // The CRM pulls the catalogue page by page ("Sync products").
    register_rest_route('ecom-pilot/v1', '/products', [
        'methods'             => 'POST',
        'permission_callback' => 'ep_verify_incoming',
        'callback'            => function (WP_REST_Request $request) {
            $perPage = max(1, min(50, (int) ($request->get_param('per_page') ?: 25)));
            $page = max(1, (int) ($request->get_param('page') ?: 1));
            $result = wc_get_products([
                'status'   => ['publish', 'private', 'draft'],
                'type'     => ['simple', 'variable'],
                'limit'    => $perPage,
                'page'     => $page,
                'paginate' => true,
                'orderby'  => 'ID',
                'order'    => 'ASC',
            ]);

            return new WP_REST_Response([
                'ok'          => true,
                'page'        => $page,
                'total'       => (int) $result->total,
                'total_pages' => (int) $result->max_num_pages,
                'products'    => array_map('ep_product_payload', $result->products),
            ], 200);
        },
    ]);

    // Lets the CRM check the plugin is installed and the key matches.
    register_rest_route('ecom-pilot/v1', '/ping', [
        'methods'             => 'POST',
        'permission_callback' => 'ep_verify_incoming',
        'callback'            => function () {
            return new WP_REST_Response(['ok' => true, 'version' => EP_VERSION, 'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null], 200);
        },
    ]);
});

/* ───────────────────────── Admin page ───────────────────────── */

add_action('admin_menu', function () {
    add_submenu_page('woocommerce', 'Ecom Pilot', 'Ecom Pilot', 'manage_woocommerce', 'ecom-pilot', 'ep_settings_page');
}, 99);

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=ecom-pilot')) . '">Settings</a>');
    return $links;
});

function ep_connect(): array
{
    return ep_post('connect', [
        'plugin_version' => EP_VERSION,
        'rest_url'       => rest_url(),
        'site_url'       => home_url(),
        'woocommerce'    => defined('WC_VERSION') ? WC_VERSION : null,
    ]);
}

function ep_settings_page()
{
    if (! current_user_can('manage_woocommerce')) {
        return;
    }

    $notice = null;

    if (isset($_POST['ep_save']) && check_admin_referer('ep_settings')) {
        $crmUrl = esc_url_raw(trim(wp_unslash($_POST['crm_url'] ?? '')));
        update_option(EP_OPTION, [
            'crm_url'        => $crmUrl ?: 'https://crm.netpilotdigital.com',
            'connection_key' => sanitize_text_field(wp_unslash($_POST['connection_key'] ?? '')),
            'checkout_check'     => empty($_POST['checkout_check']) ? 0 : 1,
            'capture_incomplete' => empty($_POST['capture_incomplete']) ? 0 : 1,
        ], false);
        if (! wp_next_scheduled('ep_retry_cron')) {
            wp_schedule_event(time() + 300, 'ep_five_minutes', 'ep_retry_cron');
        }

        $res = ep_connect();
        $notice = $res['ok']
            ? ['success', 'Connected to Ecom Pilot' . (! empty($res['body']['shop']) ? ' — shop: ' . esc_html($res['body']['shop']) : '') . '. New orders will now be sent automatically.']
            : ['error', 'Saved, but could not connect: ' . esc_html($res['error'])];
    }

    if (isset($_POST['ep_resend']) && check_admin_referer('ep_settings')) {
        $sent = 0;
        foreach (wc_get_orders(['limit' => 20, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order']) as $order) {
            $sent += ep_send_order($order->get_id()) ? 1 : 0;
        }
        $notice = ['success', "Sent {$sent} recent order(s) to Ecom Pilot."];
    }

    $s = ep_settings();
    $queued = count((array) get_option(EP_RETRY_OPTION, []));
    ?>
    <div class="wrap">
        <h1>Ecom Pilot</h1>
        <?php if ($notice) : ?>
            <div class="notice notice-<?php echo esc_attr($notice[0]); ?>"><p><?php echo wp_kses_post($notice[1]); ?></p></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('ep_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ep_crm_url">CRM address</label></th>
                    <td><input type="url" id="ep_crm_url" name="crm_url" class="regular-text" value="<?php echo esc_attr($s['crm_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ep_key">Connection key</label></th>
                    <td>
                        <input type="password" id="ep_key" name="connection_key" class="regular-text" autocomplete="off" value="<?php echo esc_attr($s['connection_key']); ?>">
                        <p class="description">In Ecom Pilot: Integrations → your store → "Copy connection key".</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Checkout check</th>
                    <td>
                        <label><input type="checkbox" name="checkout_check" value="1" <?php checked($s['checkout_check']); ?>>
                            Refuse orders from blacklisted numbers / IPs and too many repeat orders (rules are set in Ecom Pilot)</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Unfinished checkouts</th>
                    <td>
                        <label><input type="checkbox" name="capture_incomplete" value="1" <?php checked($s['capture_incomplete']); ?>>
                            When a visitor types their phone number at checkout but does not order, save them under "Failed Orders" in Ecom Pilot so your team can call</label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" name="ep_save" value="1" class="button button-primary">Save &amp; test connection</button>
                <button type="submit" name="ep_resend" value="1" class="button">Send last 20 orders again</button>
            </p>
        </form>
        <p>Plugin version <?php echo esc_html(EP_VERSION); ?>.
            <?php echo $queued ? esc_html($queued . ' order(s) waiting to be re-sent (retried every 5 minutes).') : 'No orders waiting.'; ?></p>
    </div>
    <?php
}
