<?php
if (!defined('ABSPATH')) exit;

class Clothonic_Settings {

    public static function init()
    {
        add_action('admin_menu',            [__CLASS__, 'add_menu']);
        add_action('admin_init',            [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_clothonic_test_connection', [__CLASS__, 'ajax_test_connection']);
    }

    public static function add_menu()
    {
        add_menu_page(
            'Clothonic CRM',
            'Clothonic',
            'manage_options',
            'clothonic-settings',
            [__CLASS__, 'render_page'],
            'dashicons-chart-bar',
            58
        );
    }

    public static function register_settings()
    {
        register_setting('clothonic_options', 'clothonic_crm_url', [
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_setting('clothonic_options', 'clothonic_access_token', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('clothonic_options', 'clothonic_check_duplicate', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting('clothonic_options', 'clothonic_duplicate_hours', [
            'sanitize_callback' => 'absint',
        ]);
    }

    public static function enqueue_scripts($hook)
    {
        if ($hook !== 'toplevel_page_clothonic-settings') return;

        wp_enqueue_style(
            'clothonic-admin',
            CLOTHONIC_PLUGIN_URL . 'assets/admin.css',
            [],
            CLOTHONIC_VERSION
        );
        wp_enqueue_script(
            'clothonic-admin',
            CLOTHONIC_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            CLOTHONIC_VERSION,
            true
        );
        wp_localize_script('clothonic-admin', 'clothonic_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('clothonic_nonce'),
        ]);
    }

    public static function ajax_test_connection()
    {
        check_ajax_referer('clothonic_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $token   = sanitize_text_field($_POST['token'] ?? '');
        $crm_url = esc_url_raw($_POST['crm_url'] ?? '');

        $result = Clothonic_API::test_connection($token, $crm_url);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    public static function render_page()
    {
        $crm_url   = get_option('clothonic_crm_url', '');
        $token     = get_option('clothonic_access_token', '');
        $duplicate = get_option('clothonic_check_duplicate', '1');
        $hours     = get_option('clothonic_duplicate_hours', '24');
        ?>
        <div class="wrap clothonic-wrap">

            <div class="clothonic-header">
                <div class="clothonic-logo">
                    <span class="clothonic-logo-text">Clothonic</span>
                    <span class="clothonic-badge">CRM Connector</span>
                </div>
                <p class="clothonic-version">v<?php echo esc_html(CLOTHONIC_VERSION); ?></p>
            </div>

            <?php settings_errors('clothonic_messages'); ?>

            <form method="post" action="options.php" id="clothonic-form">
                <?php settings_fields('clothonic_options'); ?>

                <!-- CONNECTION CARD -->
                <div class="clothonic-card">
                    <div class="clothonic-card-header">
                        <div class="clothonic-card-icon">🔗</div>
                        <div>
                            <h3>CRM Connection</h3>
                            <p>Enter your Clothonic CRM URL and Access Token</p>
                        </div>
                    </div>
                    <div class="clothonic-card-body">

                        <div class="clothonic-field">
                            <label for="clothonic_crm_url">CRM URL *</label>
                            <input type="url"
                                   id="clothonic_crm_url"
                                   name="clothonic_crm_url"
                                   value="<?php echo esc_attr($crm_url); ?>"
                                   placeholder="https://your-crm-domain.com"
                                   class="clothonic-input">
                            <span class="clothonic-help">
                                Your Clothonic CRM domain (without trailing slash)
                            </span>
                        </div>

                        <div class="clothonic-field">
                            <label for="clothonic_token_input">Access Token *</label>
                            <div class="clothonic-input-group">
                                <input type="password"
                                       id="clothonic_token_input"
                                       name="clothonic_access_token"
                                       value="<?php echo esc_attr($token); ?>"
                                       placeholder="Paste your Clothonic access token"
                                       class="clothonic-input">
                                <button type="button"
                                        onclick="toggleTokenVisibility()"
                                        class="clothonic-btn-icon"
                                        id="toggle-token"
                                        title="Show / hide token">👁</button>
                            </div>
                            <span class="clothonic-help">
                                Found in: Clothonic Admin → Settings → API Token
                            </span>
                        </div>

                        <div class="clothonic-test-row">
                            <button type="button"
                                    id="test-connection-btn"
                                    class="clothonic-btn clothonic-btn-outline"
                                    onclick="testConnection()">
                                🔗 Test Connection
                            </button>
                            <span id="connection-status"></span>
                        </div>

                    </div>
                </div>

                <!-- DUPLICATE CHECK CARD -->
                <div class="clothonic-card">
                    <div class="clothonic-card-header">
                        <div class="clothonic-card-icon">🛡️</div>
                        <div>
                            <h3>Duplicate Order Protection</h3>
                            <p>Block customers from placing duplicate orders</p>
                        </div>
                    </div>
                    <div class="clothonic-card-body">

                        <div class="clothonic-toggle-row">
                            <div>
                                <strong>Check Duplicate Orders</strong>
                                <p>Block same phone number or IP address from ordering within the time limit</p>
                            </div>
                            <label class="clothonic-toggle">
                                <input type="checkbox"
                                       name="clothonic_check_duplicate"
                                       value="1"
                                       id="duplicate-toggle"
                                       <?php checked($duplicate, '1'); ?>>
                                <span class="clothonic-toggle-slider"></span>
                            </label>
                        </div>

                        <div class="clothonic-field" id="hours-field"
                             style="<?php echo $duplicate !== '1' ? 'display:none' : ''; ?>">
                            <label>Block Duration (Hours)</label>
                            <div class="clothonic-hours-row">
                                <input type="number"
                                       name="clothonic_duplicate_hours"
                                       value="<?php echo esc_attr($hours); ?>"
                                       min="1" max="168"
                                       class="clothonic-input clothonic-input-sm">
                                <span class="clothonic-help-inline">hours after last order</span>
                            </div>
                            <span class="clothonic-help">
                                Recommended: 24 hours. Customer sees an error if they try to order again.
                            </span>
                        </div>

                        <div class="clothonic-info-box">
                            <strong>What gets checked:</strong>
                            <ul>
                                <li>📱 Phone number (billing phone)</li>
                                <li>🌐 IP address</li>
                            </ul>
                            <p>If either matches a recent successful order, checkout will be blocked.</p>
                        </div>

                    </div>
                </div>

                <!-- SAVE -->
                <div class="clothonic-footer">
                    <?php submit_button('Save Settings', 'primary', 'submit', false,
                        ['class' => 'clothonic-btn clothonic-btn-primary']); ?>
                </div>

            </form>

            <!-- SYNC LOG -->
            <div class="clothonic-card">
                <div class="clothonic-card-header">
                    <div class="clothonic-card-icon">📋</div>
                    <div>
                        <h3>Recent Sync Log</h3>
                        <p>Last 10 orders sent to Clothonic CRM</p>
                    </div>
                </div>
                <div class="clothonic-card-body">
                    <?php self::render_sync_log(); ?>
                </div>
            </div>

        </div>
        <?php
    }

    private static function render_sync_log()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'clothonic_order_log';
        $logs  = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 10");

        if (empty($logs)) {
            echo '<p class="clothonic-empty">No orders synced yet.</p>';
            return;
        }
        ?>
        <table class="clothonic-table">
            <thead>
                <tr>
                    <th>WC Order</th>
                    <th>Phone</th>
                    <th>CRM Order</th>
                    <th>Status</th>
                    <th>Synced At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log) : ?>
                <tr>
                    <td>
                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $log->order_id . '&action=edit')); ?>">
                            #<?php echo esc_html($log->order_id); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($log->phone); ?></td>
                    <td><?php echo esc_html($log->clothonic_order_id ?: '—'); ?></td>
                    <td>
                        <span class="clothonic-status clothonic-status-<?php echo esc_attr($log->sync_status); ?>">
                            <?php echo esc_html(ucfirst($log->sync_status)); ?>
                        </span>
                    </td>
                    <td><?php echo $log->synced_at ? esc_html(date('d M H:i', strtotime($log->synced_at))) : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
