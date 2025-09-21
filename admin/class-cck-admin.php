<?php
class CCK_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_cck_log_consent', [$this, 'log_consent']);
        add_action('wp_ajax_nopriv_cck_log_consent', [$this, 'log_consent']);
        add_action('admin_post_cck_export_logs', [$this, 'export_logs']);
        add_action('update_option_cck_options', [$this, 'register_strings_for_translation'], 10, 2);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'page_cck-') !== false) {
             wp_enqueue_style('cck-admin-styles', plugin_dir_url(__FILE__) . 'cck-admin-styles.css', [], CCK_VERSION);
        }
        if (strpos($hook, 'cck-dashboard') !== false) {
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
    }

    public function register_admin_menu() {
        add_menu_page(__('Consent King', 'cookie-consent-king'), __('Consent King', 'cookie-consent-king'), 'manage_options', 'cck-dashboard', [$this, 'render_dashboard'], 'dashicons-shield-alt');
        add_submenu_page('cck-dashboard', __('Dashboard', 'cookie-consent-king'), __('Dashboard', 'cookie-consent-king'), 'manage_options', 'cck-dashboard');
        add_submenu_page('cck-dashboard', __('Banner Settings', 'cookie-consent-king'), __('Banner Settings', 'cookie-consent-king'), 'manage_options', 'cck-settings', [$this, 'render_settings_page']);
        add_submenu_page('cck-dashboard', __('Translations', 'cookie-consent-king'), __('Translations', 'cookie-consent-king'), 'manage_options', 'cck-translations', [$this, 'render_translations_page']);
    }

    public function settings_init() {
        register_setting('cck_settings_group', 'cck_options', [$this, 'sanitize_options']);
        
        // Sección de Contenido Principal
        add_settings_section('cck_content_section', __('Content', 'cookie-consent-king'), null, 'cck-settings');
        add_settings_field('title', __('Title', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_content_section', ['name' => 'title', 'default' => __('Política de Cookies', 'cookie-consent-king')]);
        add_settings_field('message', __('Message', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_content_section', ['name' => 'message', 'type' => 'textarea', 'default' => __('Utilizamos cookies esenciales para el funcionamiento del sitio y cookies de análisis para mejorar tu experiencia. Puedes aceptar todas, rechazarlas o personalizar tus preferencias. Lee nuestra {privacy_policy_link}.', 'cookie-consent-king')]);
        add_settings_field('privacy_policy_url', __('Privacy Policy URL', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_content_section', ['name' => 'privacy_policy_url', 'type' => 'url', 'placeholder' => 'https://ejemplo.com/politica-de-privacidad']);
        
        // --- INICIO NUEVA SECCIÓN ---
        // Sección de Descripciones de Cookies
        add_settings_section('cck_descriptions_section', __('Cookie Category Descriptions', 'cookie-consent-king'), function() {
            echo '<p>' . esc_html__('Explain what each cookie category is for. This text will appear in a collapsible section in the banner.', 'cookie-consent-king') . '</p>';
        }, 'cck-settings');
        add_settings_field('description_necessary', __('Necessary Cookies', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_descriptions_section', ['name' => 'description_necessary', 'type' => 'textarea', 'placeholder' => __('e.g., These cookies are essential for the website to function properly.', 'cookie-consent-king')]);
        add_settings_field('description_preferences', __('Preferences Cookies', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_descriptions_section', ['name' => 'description_preferences', 'type' => 'textarea', 'placeholder' => __('e.g., These cookies remember your preferences, such as language or region.', 'cookie-consent-king')]);
        add_settings_field('description_analytics', __('Analytics Cookies', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_descriptions_section', ['name' => 'description_analytics', 'type' => 'textarea', 'placeholder' => __('e.g., These cookies help us understand how visitors interact with the website.', 'cookie-consent-king')]);
        add_settings_field('description_marketing', __('Marketing Cookies', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_descriptions_section', ['name' => 'description_marketing', 'type' => 'textarea', 'placeholder' => __('e.g., These cookies are used to track visitors across websites to display relevant ads.', 'cookie-consent-king')]);
        // --- FIN NUEVA SECCIÓN ---

        // Sección de Apariencia
        add_settings_section('cck_style_section', __('Appearance', 'cookie-consent-king'), null, 'cck-settings');
        add_settings_field('icon_url', __('Banner Icon URL', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_style_section', ['name' => 'icon_url', 'placeholder' => 'https://example.com/icon.svg']);
        add_settings_field('reopen_icon_url', __('Re-open Icon URL', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_style_section', ['name' => 'reopen_icon_url', 'placeholder' => __('Overrides the default arrow icon', 'cookie-consent-king')]);
        add_settings_field('colors', __('Colors', 'cookie-consent-king'), [$this, 'render_color_fields'], 'cck-settings', 'cck_style_section');

        // Sección de Herramientas de Prueba
        add_settings_section('cck_testing_section', __('Testing tools', 'cookie-consent-king'), null, 'cck-settings');
        add_settings_field('force_show', __('Force banner display', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_testing_section', ['name' => 'force_show', 'type' => 'checkbox', 'label' => __('Show the banner even if consent has been given.', 'cookie-consent-king')]);
        add_settings_field('debug', __('Enable debug logs', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_testing_section', ['name' => 'debug', 'type' => 'checkbox', 'label' => __('Print descriptive messages in the browser console.', 'cookie-consent-king')]);
        add_settings_field('test_button_text', __('Test button text', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_testing_section', ['name' => 'test_button_text', 'placeholder' => __('Leave empty to hide', 'cookie-consent-king')]);
        add_settings_field('test_button_url', __('Test instructions URL', 'cookie-consent-king'), [$this, 'render_field'], 'cck-settings', 'cck_testing_section', ['name' => 'test_button_url', 'type' => 'url', 'placeholder' => __('Optional link to internal testing guides', 'cookie-consent-king')]);
    }

    public function sanitize_options($input) {
        $sanitized = [];
        // Sanitiza campos existentes
        $sanitized['title'] = isset($input['title']) ? sanitize_text_field($input['title']) : '';
        $sanitized['message'] = isset($input['message']) ? sanitize_textarea_field($input['message']) : '';
        $sanitized['privacy_policy_url'] = isset($input['privacy_policy_url']) ? esc_url_raw($input['privacy_policy_url']) : '';
        $sanitized['icon_url'] = isset($input['icon_url']) ? esc_url_raw($input['icon_url']) : '';
        $sanitized['reopen_icon_url'] = isset($input['reopen_icon_url']) ? esc_url_raw($input['reopen_icon_url']) : '';
        $sanitized['bg_color'] = isset($input['bg_color']) ? sanitize_hex_color($input['bg_color']) : '';
        $sanitized['text_color'] = isset($input['text_color']) ? sanitize_hex_color($input['text_color']) : '';
        $sanitized['btn_primary_bg'] = isset($input['btn_primary_bg']) ? sanitize_hex_color($input['btn_primary_bg']) : '';
        $sanitized['btn_primary_text'] = isset($input['btn_primary_text']) ? sanitize_hex_color($input['btn_primary_text']) : '';
        $sanitized['force_show'] = !empty($input['force_show']) ? 1 : 0;
        $sanitized['debug'] = !empty($input['debug']) ? 1 : 0;
        $sanitized['test_button_text'] = isset($input['test_button_text']) ? sanitize_text_field($input['test_button_text']) : '';
        $sanitized['test_button_url'] = isset($input['test_button_url']) ? esc_url_raw($input['test_button_url']) : '';

        // --- INICIO SANITIZACIÓN NUEVOS CAMPOS ---
        $sanitized['description_necessary'] = isset($input['description_necessary']) ? sanitize_textarea_field($input['description_necessary']) : '';
        $sanitized['description_preferences'] = isset($input['description_preferences']) ? sanitize_textarea_field($input['description_preferences']) : '';
        $sanitized['description_analytics'] = isset($input['description_analytics']) ? sanitize_textarea_field($input['description_analytics']) : '';
        $sanitized['description_marketing'] = isset($input['description_marketing']) ? sanitize_textarea_field($input['description_marketing']) : '';
        // --- FIN SANITIZACIÓN NUEVOS CAMPOS ---

        return $sanitized;
    }

    public function register_strings_for_translation($old_value, $new_value) {
        if (isset($new_value['title'])) { $this->register_string('Banner Title', $new_value['title']); }
        if (isset($new_value['message'])) { $this->register_string('Banner Message', $new_value['message'], true); }
        
        // --- INICIO REGISTRO NUEVOS CAMPOS ---
        if (isset($new_value['description_necessary'])) { $this->register_string('Description Necessary', $new_value['description_necessary'], true); }
        if (isset($new_value['description_preferences'])) { $this->register_string('Description Preferences', $new_value['description_preferences'], true); }
        if (isset($new_value['description_analytics'])) { $this->register_string('Description Analytics', $new_value['description_analytics'], true); }
        if (isset($new_value['description_marketing'])) { $this->register_string('Description Marketing', $new_value['description_marketing'], true); }
        // --- FIN REGISTRO NUEVOS CAMPOS ---
    }
    
    private function register_string($name, $value, $multiline = false) {
        if (function_exists('pll_register_string')) {
            pll_register_string($name, $value, 'Cookie Consent King', $multiline);
        }
        if (function_exists('do_action')) {
            do_action('wpml_register_single_string', 'Cookie Consent King', $name, $value);
        }
    }

    public function render_field($args) {
        $options = get_option('cck_options', []);
        $value = $options[$args['name']] ?? ($args['default'] ?? '');
        $type = $args['type'] ?? 'text';
        $name = 'cck_options[' . esc_attr($args['name']) . ']';
        $placeholder = $args['placeholder'] ?? '';

        if ($type === 'textarea') {
            echo '<textarea name="' . $name . '" rows="4" class="large-text" placeholder="' . esc_attr($placeholder) . '">' . esc_textarea($value) . '</textarea>';
        } elseif ($type === 'checkbox') {
            echo '<label><input type="checkbox" name="' . $name . '" value="1" ' . checked(!empty($value), true, false) . '> ' . esc_html($args['label'] ?? '') . '</label>';
        } else {
            echo '<input type="' . esc_attr($type) . '" name="' . $name . '" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" />';
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
                <div class="postbox cck-metric-card"><h3><?php esc_html_e('Total Interactions', 'cookie-consent-king'); ?></h3><p class="cck-metric-value"><?php echo (int)$total; ?></p></div>
                <div class="postbox cck-metric-card"><h3><?php esc_html_e('Acceptance Rate', 'cookie-consent-king'); ?></h3><p class="cck-metric-value"><?php echo $total > 0 ? number_format(($accept / $total) * 100, 1) : 0; ?>%</p></div>
                <div class="postbox cck-metric-card"><h3><?php esc_html_e('Rejection Rate', 'cookie-consent-king'); ?></h3><p class="cck-metric-value"><?php echo $total > 0 ? number_format(($reject / $total) * 100, 1) : 0; ?>%</p></div>
                <div class="postbox cck-metric-card"><h3><?php esc_html_e('Custom Selections', 'cookie-consent-king'); ?></h3><p class="cck-metric-value"><?php echo $total > 0 ? number_format(($custom / $total) * 100, 1) : 0; ?>%</p></div>
            </div>
            <div class="cck-main-content">
                <div class="postbox cck-chart-container"><div class="inside"><canvas id="cck-consent-chart"></canvas></div></div>
                <div class="cck-logs-container">
                    <div class="cck-logs-header"><h2><?php esc_html_e('Recent Logs', 'cookie-consent-king'); ?></h2><a href="<?php echo esc_url(admin_url('admin-post.php?action=cck_export_logs')); ?>" class="button button-primary"><?php esc_html_e('Export CSV', 'cookie-consent-king'); ?></a></div>
                    <table class="wp-list-table widefat striped">
                        <thead><tr><th><?php esc_html_e('Date', 'cookie-consent-king'); ?></th><th><?php esc_html_e('Action', 'cookie-consent-king'); ?></th><th><?php esc_html_e('IP', 'cookie-consent-king'); ?></th><th><?php esc_html_e('Details', 'cookie-consent-king'); ?></th></tr></thead>
                        <tbody>
                            <?php if ($logs): foreach ($logs as $log): ?>
                            <tr><td><?php echo esc_html($log->created_at); ?></td><td><?php echo esc_html($log->action); ?></td><td><?php echo esc_html($log->ip); ?></td><td><?php echo esc_html($log->consent_details); ?></td></tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4"><?php esc_html_e('No logs yet.', 'cookie-consent-king'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_translations_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Translations Guide', 'cookie-consent-king'); ?></h1>
            <div class="postbox">
                <div class="inside">
                    <h2><?php esc_html_e('How to Translate Cookie Consent King', 'cookie-consent-king'); ?></h2>
                    <p><?php esc_html_e('This plugin is fully translatable and compatible with multilingual plugins like WPML and Polylang.', 'cookie-consent-king'); ?></p>
                    
                    <h3>1. <?php esc_html_e('Static Texts (Buttons, Labels)', 'cookie-consent-king'); ?></h3>
                    <p>
                        <?php esc_html_e('Static texts are translated using standard .po and .mo files located in the plugin\'s /languages/ folder.', 'cookie-consent-king'); ?>
                        <?php esc_html_e('You can use a program like Poedit to edit these files or generate your own for new languages.', 'cookie-consent-king'); ?>
                    </p>
                    
                    <h3>2. <?php esc_html_e('Dynamic Texts (Your Custom Content)', 'cookie-consent-king'); ?></h3>
                    <p>
                        <?php esc_html_e('The Title and Message you write in the "Banner Settings" page are dynamic. To translate them, please use the "String Translation" module of your multilingual plugin (WPML or Polylang).', 'cookie-consent-king'); ?>
                    </p>
                    <ol>
                        <li><?php esc_html_e('Go to "Consent King" -> "Banner Settings" and save your texts in your site\'s primary language.', 'cookie-consent-king'); ?></li>
                        <li><?php esc_html_e('Go to your multilingual plugin\'s "String Translation" page (e.g., "WPML" -> "String Translation").', 'cookie-consent-king'); ?></li>
                        <li><?php esc_html_e('Find the strings under the domain "Cookie Consent King". You should see "Banner Title" and "Banner Message".', 'cookie-consent-king'); ?></li>
                        <li><?php esc_html_e('Add your translations for each language.', 'cookie-consent-king'); ?></li>
                    </ol>
                    <p><em><?php esc_html_e('Every time you save the Banner Settings, the strings are re-registered for translation.', 'cookie-consent-king'); ?></em></p>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_user_ip_address() {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) { $ip = $_SERVER['HTTP_CLIENT_IP']; }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
        elseif (isset($_SERVER['REMOTE_ADDR'])) { $ip = $_SERVER['REMOTE_ADDR']; }
        else { $ip = 'UNKNOWN'; }
        return sanitize_text_field($ip);
    }
    
    public function log_consent() {
        check_ajax_referer('cck_log_consent_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cck_consent_logs';
        
        $action = isset($_POST['consent_action']) ? sanitize_text_field($_POST['consent_action']) : '';
        if (empty($action)) {
            wp_send_json_error('Action is missing.', 400);
            return;
        }

        $details_json = isset($_POST['consent_details']) ? stripslashes($_POST['consent_details']) : '{}';
        $details_array = json_decode($details_json, true);
        
        $clean_details = [
            'necessary'   => !empty($details_array['necessary']),
            'preferences' => !empty($details_array['preferences']),
            'analytics'   => !empty($details_array['analytics']),
            'marketing'   => !empty($details_array['marketing']),
        ];

        $consent_details_to_store = wp_json_encode($clean_details);

        $wpdb->insert($table_name, [
            'action'          => $action,
            'ip'              => $this->get_user_ip_address(),
            'consent_details' => $consent_details_to_store,
            'created_at'      => current_time('mysql', 1)
        ]);

        wp_send_json_success('Consent logged.');
    }
    
    public function export_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'cookie-consent-king'));
        }
        global $wpdb;
        $logs  = $wpdb->get_results("SELECT created_at, action, ip, consent_details FROM " . $wpdb->prefix . "cck_consent_logs ORDER BY id DESC", ARRAY_A);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="cck_consent_logs_'.date('Y-m-d').'.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Action', 'IP', 'Details']);
        if ($logs) { foreach ($logs as $log) { fputcsv($output, $log); } }
        fclose($output);
        exit;
    }
    
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cck_consent_logs';
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE $table_name (id mediumint(9) NOT NULL AUTO_INCREMENT, action varchar(50) NOT NULL, ip varchar(100) DEFAULT '' NOT NULL, created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, consent_details TEXT, PRIMARY KEY (id)) " . $wpdb->get_charset_collate() . ";");
    }
}
