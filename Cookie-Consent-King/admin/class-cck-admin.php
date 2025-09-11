<?php
class CCK_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_cck_log_consent', [$this, 'log_consent']);
        add_action('wp_ajax_nopriv_cck_log_consent', [$this, 'log_consent']);
        add_action('admin_post_cck_export_logs', [$this, 'export_logs']);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'cck-dashboard') === false) return;
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('cck-dashboard-chart', plugin_dir_url(__FILE__) . 'cck-dashboard-chart.js', ['chart-js'], CCK_VERSION, true);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cck_consent_logs';
        $chart_data = $wpdb->get_results("SELECT action, COUNT(id) as count FROM $table_name GROUP BY action");
        $labels = []; $data = [];
        foreach ($chart_data as $row) {
            $labels[] = ucwords(str_replace('_', ' ', $row->action));
            $data[] = $row->count;
        }
        wp_localize_script('cck-dashboard-chart', 'cckChartData', ['labels' => $labels, 'data' => $data]);
    }

    public function register_admin_menu() {
        add_menu_page(__('Consent King', 'cookie-consent-king'), __('Consent King', 'cookie-consent-king'), 'manage_options', 'cck-dashboard', [$this, 'render_dashboard'], 'dashicons-shield-alt');
        add_submenu_page('cck-dashboard', __('Dashboard', 'cookie-consent-king'), __('Dashboard', 'cookie-consent-king'), 'manage_options', 'cck-dashboard');
        add_submenu_page('cck-dashboard', __('Banner Settings', 'cookie-consent-king'), __('Banner Settings', 'cookie-consent-king'), 'manage_options', 'cck-settings', [$this, 'render_settings_page']);
    }

    public function settings_init() {
        register_setting('cck_settings_group', 'cck_options', [$this, 'sanitize_options']);

        add_settings_section('cck_content_section', __('Content', 'cookie-consent-king'), null, 'cck-settings');
        add_settings_field('title', __('Title', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_content_section', ['name' => 'title', 'default' => __('Política de Cookies', 'cookie-consent-king')]);
        add_settings_field('message', __('Message', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_content_section', ['name' => 'message', 'type' => 'textarea', 'default' => __('Utilizamos cookies esenciales para el funcionamiento del sitio y cookies de análisis para mejorar tu experiencia. Puedes aceptar todas, rechazarlas o personalizar tus preferencias.', 'cookie-consent-king')]);

        add_settings_section('cck_style_section', __('Appearance', 'cookie-consent-king'), null, 'cck-settings');
        add_settings_field('icon_url', __('Icon URL', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_style_section', ['name' => 'icon_url', 'placeholder' => 'https://example.com/icon.svg']);
        add_settings_field('colors', __('Colors', 'cookie-consent-king'), [$this, 'render_color_fields'], 'cck-settings', 'cck_style_section');
    }

    public function sanitize_options($input) {
        $sanitized = [];
        if (isset($input['title'])) $sanitized['title'] = sanitize_text_field($input['title']);
        if (isset($input['message'])) $sanitized['message'] = sanitize_textarea_field($input['message']);
        if (isset($input['icon_url'])) $sanitized['icon_url'] = esc_url_raw($input['icon_url']);
        if (isset($input['bg_color'])) $sanitized['bg_color'] = sanitize_hex_color($input['bg_color']);
        if (isset($input['text_color'])) $sanitized['text_color'] = sanitize_hex_color($input['text_color']);
        if (isset($input['btn_primary_bg'])) $sanitized['btn_primary_bg'] = sanitize_hex_color($input['btn_primary_bg']);
        if (isset($input['btn_primary_text'])) $sanitized['btn_primary_text'] = sanitize_hex_color($input['btn_primary_text']);
        return $sanitized;
    }

    public function render_field($args) {
        $options = get_option('cck_options', []);
        $value = $options[$args['name']] ?? ($args['default'] ?? '');
        $type = $args['type'] ?? 'text';
        $name = 'cck_options[' . esc_attr($args['name']) . ']';
        if ($type === 'textarea') {
            echo '<textarea name="' . $name . '" rows="4" class="large-text">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($type) . '" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($args['placeholder'] ?? '') . '" />';
        }
    }

    public function render_color_fields() {
        $options = get_option('cck_options', []);
        $colors = [
            'bg_color' => ['label' => __('Banner Background', 'cookie-consent-king'), 'default' => '#FFFFFF'],
            'text_color' => ['label' => __('Banner Text', 'cookie-consent-king'), 'default' => '#333333'],
            'btn_primary_bg' => ['label' => __('Primary Button Background', 'cookie-consent-king'), 'default' => '#000000'],
            'btn_primary_text' => ['label' => __('Primary Button Text', 'cookie-consent-king'), 'default' => '#FFFFFF']
        ];
        foreach ($colors as $name => $field) {
            $value = $options[$name] ?? $field['default'];
            echo '<div style="margin-bottom:10px;"><label style="display:inline-block;width:200px;">' . esc_html($field['label']) . '</label><input type="color" name="cck_options[' . esc_attr($name) . ']" value="' . esc_attr($value) . '"></div>';
        }
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo get_admin_page_title(); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('cck_settings_group'); ?>
                <?php do_settings_sections('cck-settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cck_consent_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 100");
        $total = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $accept = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE action = 'accept_all'");
        $reject = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE action = 'reject_all'");
        $custom = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE action = 'custom_selection'");
        ?>
        <div class="wrap" id="cck-dashboard">
            <h1><?php esc_html_e('Consent Dashboard', 'cookie-consent-king'); ?></h1>
            <div class="cck-metrics">
                <div class="cck-metric-card"><h3><?php esc_html_e('Total Interactions', 'cookie-consent-king'); ?></h3><p><?php echo (int)$total; ?></p></div>
                <div class="cck-metric-card"><h3><?php esc_html_e('Acceptance Rate', 'cookie-consent-king'); ?></h3><p><?php echo $total > 0 ? number_format(($accept / $total) * 100, 1) : 0; ?>%</p></div>
                <div class="cck-metric-card"><h3><?php esc_html_e('Rejection Rate', 'cookie-consent-king'); ?></h3><p><?php echo $total > 0 ? number_format(($reject / $total) * 100, 1) : 0; ?>%</p></div>
                <div class="cck-metric-card"><h3><?php esc_html_e('Custom Selections', 'cookie-consent-king'); ?></h3><p><?php echo $total > 0 ? number_format(($custom / $total) * 100, 1) : 0; ?>%</p></div>
            </div>
            <div class="cck-main-content">
                <div class="cck-chart-container"><canvas id="cck-consent-chart"></canvas></div>
                <div class="cck-logs-container">
                    <div class="cck-logs-header"><h2><?php esc_html_e('Recent Logs', 'cookie-consent-king'); ?></h2><a href="<?php echo esc_url(admin_url('admin-post.php?action=cck_export_logs')); ?>" class="button button-primary"><?php esc_html_e('Export CSV', 'cookie-consent-king'); ?></a></div>
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th><?php esc_html_e('Date', 'cookie-consent-king'); ?></th><th><?php esc_html_e('Action', 'cookie-consent-king'); ?></th><th><?php esc_html_e('IP', 'cookie-consent-king'); ?></th></tr></thead>
                        <tbody>
                            <?php if ($logs): foreach ($logs as $log): ?>
                            <tr><td><?php echo esc_html($log->created_at); ?></td><td><?php echo esc_html($log->action); ?></td><td><?php echo esc_html($log->ip); ?></td></tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="3"><?php esc_html_e('No logs yet.', 'cookie-consent-king'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function log_consent() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cck_log_consent_nonce')) {
            wp_send_json_error('Invalid nonce', 403);
            return;
        }
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $wpdb->insert($wpdb->prefix . 'cck_consent_logs', [
            'action' => sanitize_text_field($_POST['consent_action']),
            'ip' => sanitize_text_field($ip),
            'consent_details' => sanitize_text_field($_POST['consent_details'])
        ]);
        wp_send_json_success();
    }
    
    public function export_logs() { /* ... código de exportación ... */ }
    
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cck_consent_logs';
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE $table_name (id mediumint(9) NOT NULL AUTO_INCREMENT, action varchar(50) NOT NULL, ip varchar(100) DEFAULT '' NOT NULL, created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, consent_details TEXT, PRIMARY KEY  (id)) " . $wpdb->get_charset_collate() . ";");
    }
}