<?php
/**
 * Plugin Name: Clothonic CRM Connector
 * Plugin URI: https://clothonic.com
 * Description: Connect your WooCommerce store with Clothonic CRM
 * Version: 1.0.0
 * Author: Clothonic
 * Text Domain: clothonic-connector
 */

if (!defined('ABSPATH')) exit;

define('CLOTHONIC_VERSION', '1.0.0');
define('CLOTHONIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLOTHONIC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CLOTHONIC_PLUGIN_DIR . 'includes/class-settings.php';
require_once CLOTHONIC_PLUGIN_DIR . 'includes/class-api.php';
require_once CLOTHONIC_PLUGIN_DIR . 'includes/class-order-sync.php';
require_once CLOTHONIC_PLUGIN_DIR . 'includes/class-duplicate-check.php';

add_action('plugins_loaded', function () {
    Clothonic_Settings::init();
    Clothonic_Order_Sync::init();
    Clothonic_Duplicate_Check::init();
});

register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table   = $wpdb->prefix . 'clothonic_order_log';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        phone varchar(20) NOT NULL DEFAULT '',
        ip_address varchar(45) NOT NULL DEFAULT '',
        synced_at datetime DEFAULT NULL,
        sync_status varchar(20) DEFAULT 'pending',
        clothonic_order_id varchar(50) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY phone (phone),
        KEY ip_address (ip_address)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    add_option('clothonic_crm_url', '');
    add_option('clothonic_access_token', '');
    add_option('clothonic_check_duplicate', '1');
    add_option('clothonic_duplicate_hours', '24');
});
