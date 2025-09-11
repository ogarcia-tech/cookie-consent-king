<?php
/**
 * Plugin Name: Cookie Consent King
 * Plugin URI:  https://www.metricaweb.es
 * Description: Un banner de consentimiento de cookies sencillo, sin dependencias y personalizable.
 * Version:   3.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author:    David Adell (Metricaweb) & Oscar Garcia
 * License:   GPL2
 * Text Domain: cookie-consent-king
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

// --- ACCIONES PRINCIPALES DEL PLUGIN ---

// 1. Añadir el menú de administración
add_action('admin_menu', 'cck_register_admin_menu');
// 2. Registrar las configuraciones del plugin
add_action('admin_init', 'cck_settings_init');
// 3. Cargar los estilos y scripts en el sitio web
add_action('wp_enqueue_scripts', 'cck_enqueue_frontend_assets');
// 4. Cargar el dominio de texto para traducciones
add_action('init', 'cck_load_textdomain');
// 5. Crear la tabla en la base de datos al activar
register_activation_hook(__FILE__, 'cck_activate');
// 6. Registrar los endpoints de AJAX para el registro de consentimientos
add_action('wp_ajax_cck_log_consent', 'cck_log_consent');
add_action('wp_ajax_nopriv_cck_log_consent', 'cck_log_consent');
// 7. Registrar el endpoint para exportar logs
add_action('admin_post_cck_export_logs', 'cck_export_logs');


// --- LÓGICA DEL BANNER (HTML, CSS, JS) ---

function cck_enqueue_frontend_assets() {
    // Obtener las opciones de personalización
    $styles_opts = get_option('cck_banner_styles_options', []);
    $texts_opts = get_option('cck_default_texts_options', []);
    $config_opts = get_option('cck_basic_configuration_options', []);

    // Colores con valores por defecto
    $bg_color = $styles_opts['bg_color'] ?? '#ffffff';
    $text_color = $styles_opts['text_color'] ?? '#000000';
    $btn_bg_color = $styles_opts['accept_bg_color'] ?? '#000000';
    $btn_text_color = $styles_opts['accept_text_color'] ?? '#ffffff';
    $position_bottom = ($styles_opts['position'] ?? 'bottom') === 'bottom';

    // CSS dinámico para el banner
    $dynamic_css = "
        :root {
            --cck-bg-color: " . esc_attr($bg_color) . ";
            --cck-text-color: " . esc_attr($text_color) . ";
            --cck-btn-bg-color: " . esc_attr($btn_bg_color) . ";
            --cck-btn-text-color: " . esc_attr($btn_text_color) . ";
        }
        #cck-banner {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            " . ($position_bottom ? 'bottom: 20px;' : 'top: 20px;') . "
            width: calc(100% - 40px);
            max-width: 900px;
            background-color: var(--cck-bg-color);
            color: var(--cck-text-color);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 9999;
            display: none;
            font-family: sans-serif;
            box-sizing: border-box;
        }
        #cck-banner-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        #cck-banner-text { flex: 1 1 300px; }
        #cck-banner-text h3 { margin: 0 0 10px; font-size: 18px; color: var(--cck-text-color); }
        #cck-banner-text p { margin: 0; font-size: 14px; line-height: 1.5; }
        #cck-banner-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .cck-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: opacity 0.2s;
        }
        .cck-btn:hover { opacity: 0.85; }
        .cck-btn-primary {
            background-color: var(--cck-btn-bg-color);
            color: var(--cck-btn-text-color);
        }
        .cck-btn-secondary {
            background-color: #e0e0e0;
            color: #333;
        }
    ";
    // Añadir el CSS en línea
    wp_add_inline_style('wp-block-library', $dynamic_css);

    // HTML del banner que se añadirá al footer
    $banner_html = '
    <div id="cck-banner">
        <div id="cck-banner-content">
            <div id="cck-banner-text">
                <h3>' . esc_html($texts_opts['title'] ?? __('Gestión de Cookies', 'cookie-consent-king')) . '</h3>
                <p>' . esc_html($texts_opts['message'] ?? __('Utilizamos cookies para mejorar tu experiencia de navegación.', 'cookie-consent-king')) . '</p>
            </div>
            <div id="cck-banner-actions">
                <button id="cck-btn-reject" class="cck-btn cck-btn-primary">' . esc_html__('Rechazar todas', 'cookie-consent-king') . '</button>
                <button id="cck-btn-accept" class="cck-btn cck-btn-primary">' . esc_html__('Aceptar todas', 'cookie-consent-king') . '</button>
            </div>
        </div>
    </div>';
    add_action('wp_footer', function() use ($banner_html) {
        echo $banner_html;
    });

    // JavaScript para gestionar el banner
    $frontend_js = "
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cck-banner');
        if (!banner) return;

        const acceptBtn = document.getElementById('cck-btn-accept');
        const rejectBtn = document.getElementById('cck-btn-reject');

        // Función para obtener una cookie
        const getCookie = (name) => {
            const value = '; ' + document.cookie;
            const parts = value.split('; ' + name + '=');
            if (parts.length === 2) return parts.pop().split(';').shift();
        };

        // Si la cookie de consentimiento no existe, muestra el banner
        if (!getCookie('cookie_consent')) {
            banner.style.display = 'block';
        }

        // Función para gestionar el consentimiento
        const handleConsent = (action) => {
            // Ocultar el banner
            banner.style.display = 'none';

            // Crear una cookie que dure 365 días
            const date = new Date();
            date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            document.cookie = 'cookie_consent=' + action + '; ' + expires + '; path=/; SameSite=Lax';

            // Enviar el registro al servidor (AJAX)
            const formData = new URLSearchParams();
            formData.append('action', 'cck_log_consent');
            formData.append('consent_action', action);
            fetch('" . admin_url('admin-ajax.php') . "', {
                method: 'POST',
                body: formData
            });
        };

        acceptBtn.addEventListener('click', () => handleConsent('accept_all'));
        rejectBtn.addEventListener('click', () => handleConsent('reject_all'));
    });
    ";
    wp_add_inline_script('jquery-core', $frontend_js);
}

// --- LÓGICA DEL PANEL DE ADMINISTRACIÓN ---

function cck_register_admin_menu() {
    add_menu_page(__('Consent King', 'cookie-consent-king'), __('Consent King', 'cookie-consent-king'), 'manage_options', 'cck-dashboard', 'cck_render_dashboard', 'dashicons-carrot');
    add_submenu_page('cck-dashboard', __('Dashboard', 'cookie-consent-king'), __('Dashboard', 'cookie-consent-king'), 'manage_options', 'cck-dashboard');
    add_submenu_page('cck-dashboard', __('Banner Styles', 'cookie-consent-king'), __('Banner Styles', 'cookie-consent-king'), 'manage_options', 'cck-banner-styles', 'cck_render_banner_styles');
    add_submenu_page('cck-dashboard', __('Default Texts', 'cookie-consent-king'), __('Default Texts', 'cookie-consent-king'), 'manage_options', 'cck-default-texts', 'cck_render_default_texts');
    add_submenu_page('cck-dashboard', __('Basic Configuration', 'cookie-consent-king'), __('Basic Configuration', 'cookie-consent-king'), 'manage_options', 'cck-basic-configuration', 'cck_render_basic_configuration');
}

function cck_settings_init() {
    register_setting('cck_banner_styles_group', 'cck_banner_styles_options');
    add_settings_section('cck_banner_styles_section', '', '__return_false', 'cck-banner-styles');
    add_settings_field('cck_banner_bg_color', __('Banner Background Color', 'cookie-consent-king'), 'cck_field_color_picker', 'cck-banner-styles', 'cck_banner_styles_section', ['name' => 'bg_color', 'default' => '#ffffff']);
    add_settings_field('cck_banner_text_color', __('Banner Text Color', 'cookie-consent-king'), 'cck_field_color_picker', 'cck-banner-styles', 'cck_banner_styles_section', ['name' => 'text_color', 'default' => '#000000']);
    add_settings_field('cck_accept_button_colors', __('Accept/Reject Buttons', 'cookie-consent-king'), 'cck_field_button_colors', 'cck-banner-styles', 'cck_banner_styles_section');
    add_settings_field('cck_banner_position', __('Banner Position', 'cookie-consent-king'), 'cck_field_banner_position', 'cck-banner-styles', 'cck_banner_styles_section');

    register_setting('cck_default_texts_group', 'cck_default_texts_options');
    add_settings_section('cck_default_texts_section', '', '__return_false', 'cck-default-texts');
    add_settings_field('cck_default_title', __('Title', 'cookie-consent-king'), 'cck_field_text_input', 'cck-default-texts', 'cck_default_texts_section', ['name' => 'title']);
    add_settings_field('cck_default_message', __('Message', 'cookie-consent-king'), 'cck_field_textarea', 'cck-default-texts', 'cck_default_texts_section', ['name' => 'message']);

    register_setting('cck_basic_configuration_group', 'cck_basic_configuration_options');
    add_settings_section('cck_basic_configuration_section', '', '__return_false', 'cck-basic-configuration');
    add_settings_field('cck_privacy_url', __('Privacy Policy URL', 'cookie-consent-king'), 'cck_field_text_input', 'cck-basic-configuration', 'cck_basic_configuration_section', ['name' => 'privacy_url', 'type' => 'url']);
}

// --- FUNCIONES PARA RENDERIZAR CAMPOS DEL ADMIN ---

function cck_field_color_picker($args) {
    $options = get_option('cck_banner_styles_options', []);
    $value = $options[$args['name']] ?? $args['default'];
    echo '<input type="color" name="cck_banner_styles_options[' . esc_attr($args['name']) . ']" value="' . esc_attr($value) . '" />';
}

function cck_field_button_colors() {
    $options = get_option('cck_banner_styles_options', []);
    $bg_color = $options['accept_bg_color'] ?? '#000000';
    $text_color = $options['accept_text_color'] ?? '#ffffff';
    echo '<p><em>' . __('Estos colores se aplican a los botones "Aceptar" y "Rechazar".', 'cookie-consent-king') . '</em></p>';
    echo '<label>' . __('Background', 'cookie-consent-king') . ': </label>';
    echo '<input type="color" name="cck_banner_styles_options[accept_bg_color]" value="' . esc_attr($bg_color) . '" />';
    echo '<label style="margin-left: 15px;">' . __('Text', 'cookie-consent-king') . ': </label>';
    echo '<input type="color" name="cck_banner_styles_options[accept_text_color]" value="' . esc_attr($text_color) . '" />';
}

function cck_field_banner_position() {
    $options = get_option('cck_banner_styles_options', []);
    $value = $options['position'] ?? 'bottom';
    $positions = ['bottom' => __('Bottom', 'cookie-consent-king'), 'top' => __('Top', 'cookie-consent-king')];
    echo '<select name="cck_banner_styles_options[position]">';
    foreach ($positions as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function cck_field_text_input($args) {
    $group = 'cck_basic_configuration_options';
    if ($args['name'] === 'title') $group = 'cck_default_texts_options';
    $options = get_option($group, []);
    $value = $options[$args['name']] ?? '';
    $type = $args['type'] ?? 'text';
    echo '<input type="' . esc_attr($type) . '" name="' . $group . '[' . esc_attr($args['name']) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
}

function cck_field_textarea($args) {
    $options = get_option('cck_default_texts_options', []);
    $value = $options[$args['name']] ?? '';
    echo '<textarea name="cck_default_texts_options[' . esc_attr($args['name']) . ']" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
}


// --- FUNCIONES PARA RENDERIZAR PÁGINAS DEL ADMIN ---

function cck_render_dashboard() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cck_consent_logs';
    $logs = $wpdb->get_results("SELECT action, ip, country, created_at FROM $table_name ORDER BY id DESC LIMIT 100");

    $total_logs = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
    $accept_all = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE action = 'accept_all'");
    $reject_all = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE action = 'reject_all'");
    $custom_selection = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE action = 'custom_selection'");

    $acceptance_rate = ($total_logs > 0) ? ($accept_all / $total_logs) * 100 : 0;
    $rejection_rate = ($total_logs > 0) ? ($reject_all / $total_logs) * 100 : 0;
    $customization_rate = ($total_logs > 0) ? ($custom_selection / $total_logs) * 100 : 0;
    
    $chart_data = $wpdb->get_results("SELECT action, COUNT(id) as count FROM $table_name GROUP BY action");
    $labels = [];
    $data = [];
    foreach ($chart_data as $row) {
        $labels[] = str_replace('_', ' ', $row->action);
        $data[] = $row->count;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Consent Dashboard', 'cookie-consent-king'); ?></h1>
        <div id="cck-dashboard-metrics" style="display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0;">
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;"><h2><?php esc_html_e('Total Interactions', 'cookie-consent-king'); ?></h2><p style="font-size: 2em;"><?php echo (int) $total_logs; ?></p></div>
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;"><h2><?php esc_html_e('Acceptance Rate', 'cookie-consent-king'); ?></h2><p style="font-size: 2em;"><?php echo number_format($acceptance_rate, 2); ?>%</p></div>
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;"><h2><?php esc_html_e('Rejection Rate', 'cookie-consent-king'); ?></h2><p style="font-size: 2em;"><?php echo number_format($rejection_rate, 2); ?>%</p></div>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 1 1 500px;"><div class="postbox"><h2><?php esc_html_e('Consent Breakdown', 'cookie-consent-king'); ?></h2><div class="inside"><canvas id="cck-chart"></canvas></div></div></div>
            <div style="flex: 1 1 500px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php esc_html_e('Recent Logs', 'cookie-consent-king'); ?></h2>
                    <a class="button button-primary" href="<?php echo esc_url(admin_url('admin-post.php?action=cck_export_logs')); ?>"><?php esc_html_e('Export CSV', 'cookie-consent-king'); ?></a>
                </div>
                <table class="wp-list-table widefat striped">
                    <thead><tr><th><?php esc_html_e('Date', 'cookie-consent-king'); ?></th><th><?php esc_html_e('Action', 'cookie-consent-king'); ?></th><th><?php esc_html_e('IP', 'cookie-consent-king'); ?></th><th><?php esc_html_e('Country', 'cookie-consent-king'); ?></th></tr></thead>
                    <tbody>
                        <?php if ($logs) : foreach ($logs as $log) : ?>
                            <tr><td><?php echo esc_html($log->created_at); ?></td><td><?php echo esc_html($log->action); ?></td><td><?php echo esc_html($log->ip); ?></td><td><?php echo esc_html($log->country); ?></td></tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4"><?php esc_html_e('No logs found.', 'cookie-consent-king'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            new Chart(document.getElementById('cck-chart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{ label: '<?php esc_html_e('Actions', 'cookie-consent-king'); ?>', data: <?php echo json_encode($data); ?> }]
                }
            });
        </script>
    </div>
    <?php
}

function cck_render_banner_styles() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Banner Styles', 'cookie-consent-king'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cck_banner_styles_group');
            do_settings_sections('cck-banner-styles');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function cck_render_default_texts() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Default Texts', 'cookie-consent-king'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cck_default_texts_group');
            do_settings_sections('cck-default-texts');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function cck_render_basic_configuration() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Basic Configuration', 'cookie-consent-king'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cck_basic_configuration_group');
            do_settings_sections('cck-basic-configuration');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}


// --- FUNCIONES AUXILIARES Y DE ACTIVACIÓN ---

function cck_load_textdomain() {
    load_plugin_textdomain('cookie-consent-king', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function cck_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cck_consent_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        action varchar(50) NOT NULL,
        ip varchar(100) DEFAULT '' NOT NULL,
        country varchar(100) DEFAULT '' NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function cck_get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return sanitize_text_field(trim(explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']))[0]));
    return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
}

function cck_get_country_from_ip($ip) {
    if (empty($ip)) return '';
    $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
    if (is_wp_error($response)) return '';
    return sanitize_text_field(wp_remote_retrieve_body($response));
}

function cck_log_consent() {
    $action = sanitize_text_field($_POST['consent_action'] ?? '');
    if (!$action) wp_send_json_error('Missing action');

    global $wpdb;
    $ip = cck_get_user_ip();
    $wpdb->insert($wpdb->prefix . 'cck_consent_logs', [
        'action' => $action,
        'ip' => $ip,
        'country' => cck_get_country_from_ip($ip),
        'created_at' => current_time('mysql'),
    ]);
    wp_send_json_success();
}

function cck_export_logs() {
    if (!current_user_can('manage_options')) wp_die(__('Unauthorized', 'cookie-consent-king'));
    
    global $wpdb;
    $logs = $wpdb->get_results("SELECT created_at, action, ip, country FROM " . $wpdb->prefix . "cck_consent_logs ORDER BY id DESC", ARRAY_A);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cck_consent_logs.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Action', 'IP', 'Country']);
    if ($logs) {
        foreach ($logs as $log) {
            fputcsv($output, $log);
        }
    }
    fclose($output);
    exit;
}