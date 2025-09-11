<?php
/**
 * Plugin Name: Cookie Consent King
 * Plugin URI: https://www.metricaweb.es
 * Description: Provides a React-powered cookie consent banner.
 * Version: 2.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: David Adell (Metricaweb) & Oscar Garcia
 * License: GPL2
 * Text Domain: cookie-consent-king

*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!defined('COOKIE_CONSENT_KING_VERSION')) {
    define('COOKIE_CONSENT_KING_VERSION', '2.0');
}

function cck_enqueue_assets() {
    $asset_path = plugin_dir_path(__FILE__) . 'dist/assets/';
    $asset_url  = plugin_dir_url(__FILE__) . 'dist/assets/';

    $js_path  = $asset_path . 'index.js';
    $css_path = $asset_path . 'index.css';

    if (!file_exists($js_path) || !file_exists($css_path)) {
        $message = __('Cookie Consent King: assets not found. Loading from CDN. To use local assets, run "npm run build".', 'cookie-consent-king');

        if (function_exists('wp_admin_notice')) {
            wp_admin_notice($message, ['type' => 'warning']);
        } else {
            add_action('admin_notices', function () use ($message) {
                echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
            });
        }

        $cdn_base = 'https://cdn.jsdelivr.net/gh/metricaweb/cookie-consent-king@main/dist/assets/';
        wp_enqueue_script(
            'cookie-consent-king-js',
            $cdn_base . 'index.js',
            [],
            COOKIE_CONSENT_KING_VERSION,
            true
        );
        wp_enqueue_style(
            'cookie-consent-king-css',
            $cdn_base . 'index.css',
            [],
            COOKIE_CONSENT_KING_VERSION
        );

        return;
    }

    wp_enqueue_script(
        'cookie-consent-king-js',
        $asset_url . 'index.js',
        [],
        filemtime($js_path),
        true
    );
    wp_enqueue_style(
        'cookie-consent-king-css',
        $asset_url . 'index.css',
        [],
        filemtime($css_path)
    );

    // Definición de las traducciones (sin cambios)
    $translations = [
        'Banner de cookies moderno con soporte completo para Google Consent Mode v2' => __('Banner de cookies moderno con soporte completo para Google Consent Mode v2', 'cookie-consent-king'),
        'Cumplimiento GDPR' => __('Cumplimiento GDPR', 'cookie-consent-king'),
        'Cumple totalmente con las regulaciones GDPR y otras leyes de privacidad internacionales.' => __('Cumple totalmente con las regulaciones GDPR y otras leyes de privacidad internacionales.', 'cookie-consent-king'),
        'Integración nativa con Google Consent Mode v2 para una gestión avanzada de consentimientos.' => __('Integración nativa con Google Consent Mode v2 para una gestión avanzada de consentimientos.', 'cookie-consent-king'),
        'Fácil Configuración' => __('Fácil Configuración', 'cookie-consent-king'),
        'Configuración granular de diferentes tipos de cookies con interfaz intuitiva.' => __('Configuración granular de diferentes tipos de cookies con interfaz intuitiva.', 'cookie-consent-king'),
        'Estado del Consentimiento' => __('Estado del Consentimiento', 'cookie-consent-king'),
        'Información actual sobre las preferencias de cookies' => __('Información actual sobre las preferencias de cookies', 'cookie-consent-king'),
        'Consentimiento otorgado' => __('Consentimiento otorgado', 'cookie-consent-king'),
        'Fecha:' => __('Fecha:', 'cookie-consent-king'),
        'Necesarias:' => __('Necesarias:', 'cookie-consent-king'),
        'Análisis:' => __('Análisis:', 'cookie-consent-king'),
        'Marketing:' => __('Marketing:', 'cookie-consent-king'),
        'Preferencias:' => __('Preferencias:', 'cookie-consent-king'),
        'Activo' => __('Activo', 'cookie-consent-king'),
        'Inactivo' => __('Inactivo', 'cookie-consent-king'),
        'Restablecer Consentimiento' => __('Restablecer Consentimiento', 'cookie-consent-king'),
        'Mostrar Banner de Cookies' => __('Mostrar Banner de Cookies', 'cookie-consent-king'),
        'No se ha otorgado consentimiento aún' => __('No se ha otorgado consentimiento aún', 'cookie-consent-king'),
        'Limpiar y Probar' => __('Limpiar y Probar', 'cookie-consent-king'),
        'Gestionar cookies' => __('Gestionar cookies', 'cookie-consent-king'),
        'Abrir configuración de cookies' => __('Abrir configuración de cookies', 'cookie-consent-king'),
        'Gestión de Cookies' => __('Gestión de Cookies', 'cookie-consent-king'),
        'Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico. También compartimos información sobre tu uso de nuestro sitio con nuestros socios de análisis y publicidad.' => __('Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico. También compartimos información sobre tu uso de nuestro sitio con nuestros socios de análisis y publicidad.', 'cookie-consent-king'),
        'Personalizar' => __('Personalizar', 'cookie-consent-king'),
        'Rechazar todas' => __('Rechazar todas', 'cookie-consent-king'),
        'Aceptar todas' => __('Aceptar todas', 'cookie-consent-king'),
        'Configuración de Cookies' => __('Configuración de Cookies', 'cookie-consent-king'),
        'Consentimiento' => __('Consentimiento', 'cookie-consent-king'),
        'Detalles' => __('Detalles', 'cookie-consent-king'),
        'Acerca de las cookies' => __('Acerca de las cookies', 'cookie-consent-king'),
        'Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web para hacerlo más intuitivo y para mostrarte publicidad personalizada con base en un perfil elaborado a partir las páginas webs que visitas y los productos y servicios por los que te interesas.' => __('Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web para hacerlo más intuitivo y para mostrarte publicidad personalizada con base en un perfil elaborado a partir las páginas webs que visitas y los productos y servicios por los que te interesas.', 'cookie-consent-king'),
        'Puedes aceptar todas las cookies pulsando el botón "Aceptar", rechazar todas las cookies pulsando sobre el botón "Rechazar" o configurarlas su uso pulsando el botón "Configuración de cookies".' => __('Puedes aceptar todas las cookies pulsando el botón "Aceptar", rechazar todas las cookies pulsando sobre el botón "Rechazar" o configurarlas su uso pulsando el botón "Configuración de cookies".', 'cookie-consent-king'),
        'Si deseas más información pulsa en' => __('Si deseas más información pulsa en', 'cookie-consent-king'),
        'Política de Cookies' => __('Política de Cookies', 'cookie-consent-king'),
        'Permitir selección' => __('Permitir selección', 'cookie-consent-king'),
        'Necesario' => __('Necesario', 'cookie-consent-king'),
        'Las cookies necesarias ayudan a hacer una página web utilizable activando funciones básicas como la navegación en la página y el acceso a áreas seguras de la página web. La página web no puede funcionar adecuadamente sin estas cookies.' => __('Las cookies necesarias ayudan a hacer una página web utilizable activando funciones básicas como la navegación en la página y el acceso a áreas seguras de la página web. La página web no puede funcionar adecuadamente sin estas cookies.', 'cookie-consent-king'),
        'Preferencias' => __('Preferencias', 'cookie-consent-king'),
        'Las cookies de preferencias permiten a la página web recordar información que cambia la forma en que la página se comporta o el aspecto que tiene, como su idioma preferido o la región en la que usted se encuentra.' => __('Las cookies de preferencias permiten a la página web recordar información que cambia la forma en que la página se comporta o el aspecto que tiene, como su idioma preferido o la región en la que usted se encuentra.', 'cookie-consent-king'),
        'Estadística' => __('Estadística', 'cookie-consent-king'),
        'Las cookies estadísticas ayudan a los propietarios de páginas web a comprender cómo interactúan los visitantes con las páginas web reuniendo y proporcionando información de forma anónima.' => __('Las cookies estadísticas ayudan a los propietarios de páginas web a comprender cómo interactúan los visitantes con las páginas web reuniendo y proporcionando información de forma anónima.', 'cookie-consent-king'),
        'Marketing' => __('Marketing', 'cookie-consent-king'),
        'Las cookies de marketing se utilizan para rastrear a los visitantes en las páginas web. La intención es mostrar anuncios relevantes y atractivos para el usuario individual.' => __('Las cookies de marketing se utilizan para rastrear a los visitantes en las páginas web. La intención es mostrar anuncios relevantes y atractivos para el usuario individual.', 'cookie-consent-king'),
        'Tipos de cookies que utilizamos:' => __('Tipos de cookies que utilizamos:', 'cookie-consent-king'),
        'Cookies técnicas o necesarias:' => __('Cookies técnicas o necesarias:', 'cookie-consent-king'),
        'Son esenciales para el funcionamiento básico del sitio web y no se pueden desactivar.' => __('Son esenciales para el funcionamiento básico del sitio web y no se pueden desactivar.', 'cookie-consent-king'),
        'Cookies de preferencias:' => __('Cookies de preferencias:', 'cookie-consent-king'),
        'Permiten recordar las configuraciones y preferencias del usuario para mejorar su experiencia.' => __('Permiten recordar las configuraciones y preferencias del usuario para mejorar su experiencia.', 'cookie-consent-king'),
        'Cookies estadísticas:' => __('Cookies estadísticas:', 'cookie-consent-king'),
        'Recopilan información de forma anónima sobre cómo los usuarios interactúan con el sitio web para mejorar su rendimiento.' => __('Recopilan información de forma anónima sobre cómo los usuarios interactúan con el sitio web para mejorar su rendimiento.', 'cookie-consent-king'),
        'Cookies de marketing:' => __('Cookies de marketing:', 'cookie-consent-king'),
        'Se utilizan para mostrar publicidad relevante y medir la efectividad de las campañas publicitarias.' => __('Se utilizan para mostrar publicidad relevante y medir la efectividad de las campañas publicitarias.', 'cookie-consent-king'),
        'En cumplimiento del Reglamento General de Protección de Datos (RGPD), solicitamos su consentimiento para el uso de cookies no esenciales. Puede gestionar sus preferencias de cookies en cualquier momento accediendo a la configuración de privacidad de nuestro sitio web.' => __('En cumplimiento del Reglamento General de Protección de Datos (RGPD), solicitamos su consentimiento para el uso de cookies no esenciales. Puede gestionar sus preferencias de cookies en cualquier momento accediendo a la configuración de privacidad de nuestro sitio web.', 'cookie-consent-king'),
        'Para más información sobre nuestra política de privacidad y el tratamiento de datos personales, consulte nuestra política de privacidad completa.' => __('Para más información sobre nuestra política de privacidad y el tratamiento de datos personales, consulte nuestra política de privacidad completa.', 'cookie-consent-king'),
        'Para información detallada sobre cookies, visite' => __('Para información detallada sobre cookies, visite', 'cookie-consent-king'),
        'Acerca de las Cookies' => __('Acerca de las Cookies', 'cookie-consent-king'),
        'Rechazar' => __('Rechazar', 'cookie-consent-king'),
        'Aceptar' => __('Aceptar', 'cookie-consent-king'),
        'Cookies necesarias (siempre activadas)' => __('Cookies necesarias (siempre activadas)', 'cookie-consent-king'),
        'Cookies de preferencias' => __('Cookies de preferencias', 'cookie-consent-king'),
        'Cookies estadísticas' => __('Cookies estadísticas', 'cookie-consent-king'),
        'Cookies de marketing' => __('Cookies de marketing', 'cookie-consent-king'),
    ];

    // *** CORRECCIÓN APLICADA AQUÍ ***
    $banner_styles = get_option('cck_banner_styles_options', []);
    wp_localize_script(
        'cookie-consent-king-js',
        'cckBannerStyles',
        [
            'position' => $banner_styles['position'] ?? 'bottom',
            // Agrega aquí los nuevos campos de color si los has añadido
            // Ejemplo:
            // 'accept_bg_color' => $banner_styles['accept_bg_color'] ?? '#000000',
            // 'accept_text_color' => $banner_styles['accept_text_color'] ?? '#ffffff',
        ]
    );

    wp_localize_script('cookie-consent-king-js', 'cckTranslations', $translations);
    wp_localize_script('cookie-consent-king-js', 'cckAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);

    add_action('wp_footer', 'cck_render_root_div');
}

function cck_render_root_div() {
    echo '<div id="root"></div>';
}
add_action('wp_enqueue_scripts', 'cck_enqueue_assets');

function cck_enqueue_admin_preview_assets($hook) {
    if (strpos($hook, 'cck') === false) {
        return;
    }

    cck_enqueue_assets();

    $default_texts = get_option('cck_default_texts_options', []);
    $basic_config = get_option('cck_basic_configuration_options', []);
    $options = [
        'title' => $default_texts['title'] ?? '',
        'message' => $default_texts['message'] ?? '',
        'cookiePolicyUrl' => $basic_config['privacy_url'] ?? '',
    ];
    // Pasar los nuevos colores al script
    wp_localize_script('cookie-consent-king-js', 'cckBannerStyles', [
        'position' => $banner_styles['position'] ?? 'bottom',
        'accept_bg_color' => $banner_styles['accept_bg_color'] ?? '#000000',
        'accept_text_color' => $banner_styles['accept_text_color'] ?? '#ffffff',
    wp_localize_script('cookie-consent-king-js', 'cckOptions', $options);
    wp_add_inline_script('cookie-consent-king-js', 'window.cckPreview = true; window.cckForceShow = true;', 'before');
}
add_action('admin_enqueue_scripts', 'cck_enqueue_admin_preview_assets');

function cck_load_textdomain() {
    load_plugin_textdomain(
        'cookie-consent-king',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'cck_load_textdomain');

function cck_activate() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'cck_consent_logs';
    $charset_collate = $wpdb->get_charset_collate();
    $sql             = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        action varchar(50) NOT NULL,
        ip varchar(100) DEFAULT '' NOT NULL,
        country varchar(100) DEFAULT '' NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    $defaults = [
        'cck_banner_heading' => __('Gestión de Cookies', 'cookie-consent-king'),
        'cck_banner_message' => __('Utilizamos cookies para mejorar tu experiencia de navegación.', 'cookie-consent-king'),
        'cck_policy_url'     => '/politica-de-cookies',
    ];

    foreach ($defaults as $option => $value) {
        if (false === get_option($option)) {
            add_option($option, $value);
        }
    }
}
register_activation_hook(__FILE__, 'cck_activate');

function cck_deactivate() {
    delete_option('cck_banner_heading');
    delete_option('cck_banner_message');
    delete_option('cck_policy_url');
}
register_deactivation_hook(__FILE__, 'cck_deactivate');

// -----------------------------------------------------------------------------
// Admin Menu Setup
// -----------------------------------------------------------------------------

/**
 * Register the Consent King admin menu and submenus.
 */
function cck_register_admin_menu() {
    // Top level menu.
    add_menu_page(
        __('Consent King', 'cookie-consent-king'),
        __('Consent King', 'cookie-consent-king'),
        'manage_options',
        'cck-dashboard',
        'cck_render_dashboard',
        'dashicons-carrot'
    );

    // Submenu: Dashboard.
    add_submenu_page(
        'cck-dashboard',
        __('Dashboard', 'cookie-consent-king'),
        __('Dashboard', 'cookie-consent-king'),
        'manage_options',
        'cck-dashboard',
        'cck_render_dashboard'
    );

    // Submenu: Banner Styles.
    add_submenu_page(
        'cck-dashboard',
        __('Banner Styles', 'cookie-consent-king'),
        __('Banner Styles', 'cookie-consent-king'),
        'manage_options',
        'cck-banner-styles',
        'cck_render_banner_styles'
    );

    // Submenu: Default Texts.
    add_submenu_page(
        'cck-dashboard',
        __('Default Texts', 'cookie-consent-king'),
        __('Default Texts', 'cookie-consent-king'),
        'manage_options',
        'cck-default-texts',
        'cck_render_default_texts'
    );

    // Submenu: Basic Configuration.
    add_submenu_page(
        'cck-dashboard',
        __('Basic Configuration', 'cookie-consent-king'),
        __('Basic Configuration', 'cookie-consent-king'),
        'manage_options',
        'cck-basic-configuration',
        'cck_render_basic_configuration'
    );

    // Submenu: Cookie List/Analysis.
    add_submenu_page(
        'cck-dashboard',
        __('Cookie List/Analysis', 'cookie-consent-king'),
        __('Cookie List/Analysis', 'cookie-consent-king'),
        'manage_options',
        'cck-cookie-list',
        'cck_render_cookie_list'
    );

    // Submenu: Translations.
    add_submenu_page(
        'cck-dashboard',
        __('Translations', 'cookie-consent-king'),
        __('Translations', 'cookie-consent-king'),
        'manage_options',
        'cck-translations',
        'cck_render_translations'
    );
}
add_action('admin_menu', 'cck_register_admin_menu');

// -----------------------------------------------------------------------------
// Settings API registration
// -----------------------------------------------------------------------------

function cck_settings_init() {
    // Banner Styles options.
    register_setting('cck_banner_styles_group', 'cck_banner_styles_options');
    // Nuevos campos para colores de botones
    add_settings_field('cck_accept_button_colors', __('Accept Button Colors', 'cookie-consent-king'), 'cck_field_accept_button_colors', 'cck-banner-styles', 'cck_banner_styles_section');
    add_settings_field('cck_reject_button_colors', __('Reject Button Colors', 'cookie-consent-king'), 'cck_field_reject_button_colors', 'cck-banner-styles', 'cck_banner_styles_section');
    add_settings_field('cck_customize_button_colors', __('Customize Button Colors', 'cookie-consent-king'), 'cck_field_customize_button_colors', 'cck-banner-styles', 'cck_banner_styles_section');
    add_settings_section('cck_banner_styles_section', '', '__return_false', 'cck-banner-styles');
    add_settings_field(
        'cck_banner_bg_color',
        __('Background Color', 'cookie-consent-king'),
        'cck_field_banner_bg_color',
        'cck-banner-styles',
        'cck_banner_styles_section'
    );
    add_settings_field(
        'cck_banner_text_color',
        __('Text Color', 'cookie-consent-king'),
        'cck_field_banner_text_color',
        'cck-banner-styles',
        'cck_banner_styles_section'
    );
    add_settings_field(
        'cck_banner_position',
        __('Banner Position', 'cookie-consent-king'),
        'cck_field_banner_position',
        'cck-banner-styles',
        'cck_banner_styles_section'
    );

    // Default Texts options.
    register_setting('cck_default_texts_group', 'cck_default_texts_options');
    add_settings_section('cck_default_texts_section', '', '__return_false', 'cck-default-texts');
    add_settings_field(
        'cck_default_title',
        __('Title', 'cookie-consent-king'),
        'cck_field_default_title',
        'cck-default-texts',
        'cck_default_texts_section'
    );
    add_settings_field(
        'cck_default_message',
        __('Message', 'cookie-consent-king'),
        'cck_field_default_message',
        'cck-default-texts',
        'cck_default_texts_section'
    );

    // Basic Configuration options.
    register_setting('cck_basic_configuration_group', 'cck_basic_configuration_options');
    add_settings_section('cck_basic_configuration_section', '', '__return_false', 'cck-basic-configuration');
    add_settings_field(
        'cck_privacy_url',
        __('Privacy Policy URL', 'cookie-consent-king'),
        'cck_field_privacy_url',
        'cck-basic-configuration',
        'cck_basic_configuration_section'
    );

    // Cookie List options.
    register_setting('cck_cookie_list_group', 'cck_cookie_list_options');
    add_settings_section('cck_cookie_list_section', '', '__return_false', 'cck-cookie-list');
    add_settings_field(
        'cck_cookie_list',
        __('Cookie List', 'cookie-consent-king'),
        'cck_field_cookie_list',
        'cck-cookie-list',
        'cck_cookie_list_section'
    );
}
add_action('admin_init', 'cck_settings_init');


function cck_field_accept_button_colors() {
    $options = get_option('cck_banner_styles_options', []);
    $bg_color = $options['accept_bg_color'] ?? '#000000';
    $text_color = $options['accept_text_color'] ?? '#ffffff';
    echo '<input type="color" name="cck_banner_styles_options[accept_bg_color]" value="' . esc_attr($bg_color) . '" /> ' . __('Background', 'cookie-consent-king');
    echo '<input type="color" name="cck_banner_styles_options[accept_text_color]" value="' . esc_attr($text_color) . '" /> ' . __('Text', 'cookie-consent-king');
}

// -----------------------------------------------------------------------------
// Field render callbacks
// -----------------------------------------------------------------------------

function cck_field_banner_bg_color() {
    $options = get_option('cck_banner_styles_options', []);
    $value   = $options['bg_color'] ?? '';
    echo '<input type="text" name="cck_banner_styles_options[bg_color]" value="' . esc_attr($value) . '" />';
}

function cck_field_banner_text_color() {
    $options = get_option('cck_banner_styles_options', []);
    $value   = $options['text_color'] ?? '';
    echo '<input type="text" name="cck_banner_styles_options[text_color]" value="' . esc_attr($value) . '" />';
}

function cck_field_banner_position() {
    $options  = get_option('cck_banner_styles_options', []);
    $value    = $options['position'] ?? 'bottom';
    $positions = [
        'bottom' => __('Bottom', 'cookie-consent-king'),
        'top'    => __('Top', 'cookie-consent-king'),
        'modal'  => __('Modal', 'cookie-consent-king'),
    ];
    echo '<select name="cck_banner_styles_options[position]">';
    foreach ($positions as $key => $label) {
        $selected = selected($value, $key, false);
        echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function cck_field_default_title() {
    $options = get_option('cck_default_texts_options', []);
    $value   = $options['title'] ?? '';
    echo '<input type="text" name="cck_default_texts_options[title]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function cck_field_default_message() {
    $options = get_option('cck_default_texts_options', []);
    $value   = $options['message'] ?? '';
    echo '<textarea name="cck_default_texts_options[message]" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
}

function cck_field_privacy_url() {
    $options = get_option('cck_basic_configuration_options', []);
    $value   = $options['privacy_url'] ?? '';
    echo '<input type="url" name="cck_basic_configuration_options[privacy_url]" value="' . esc_attr($value) . '" class="regular-text" />';
}

function cck_field_cookie_list() {
    $options = get_option('cck_cookie_list_options', []);
    $value   = $options['list'] ?? '';
    echo '<textarea name="cck_cookie_list_options[list]" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
}

¡Por supuesto! Aquí tienes la función cck_render_dashboard() completa y mejorada para tu archivo cookie-consent-king.php.

Esta versión incluye las nuevas tarjetas de métricas en la parte superior para un resumen rápido y mantiene el gráfico y la tabla de registros que ya tenías.

PHP

/**
 * Render the Dashboard screen.
 */
function cck_render_dashboard() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cck_consent_logs';

    // Query for the raw logs for the table and chart
    $logs = $wpdb->get_results( "SELECT action, ip, country, created_at FROM $table_name ORDER BY id DESC LIMIT 100" );

    // --- New Metrics Calculations ---
    $total_logs       = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
    $accept_all       = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE action = 'accept_all'" );
    $reject_all       = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE action = 'reject_all'" );
    $custom_selection = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE action = 'custom_selection'" );

    // Calculate percentages safely
    $acceptance_rate     = ( $total_logs > 0 ) ? ( $accept_all / $total_logs ) * 100 : 0;
    $rejection_rate      = ( $total_logs > 0 ) ? ( $reject_all / $total_logs ) * 100 : 0;
    $customization_rate  = ( $total_logs > 0 ) ? ( $custom_selection / $total_logs ) * 100 : 0;

    // Data for the bar chart
    $counts      = [];
    $chart_logs = $wpdb->get_results( "SELECT action, COUNT(id) as count FROM $table_name GROUP BY action" );
    foreach ( $chart_logs as $log ) {
        $counts[ $log->action ] = $log->count;
    }
    $labels = array_keys( $counts );
    $data   = array_values( $counts );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Consent Dashboard', 'cookie-consent-king' ); ?></h1>

        <div id="cck-dashboard-metrics" style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; margin-bottom: 30px;">
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;">
                <h2 class="hndle"><?php esc_html_e( 'Total Interactions', 'cookie-consent-king' ); ?></h2>
                <div class="inside" style="font-size: 2em; font-weight: bold;"><?php echo esc_html( number_format( $total_logs ) ); ?></div>
            </div>
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;">
                <h2 class="hndle"><?php esc_html_e( 'Acceptance Rate', 'cookie-consent-king' ); ?></h2>
                <div class="inside" style="font-size: 2em; font-weight: bold;"><?php echo esc_html( number_format( $acceptance_rate, 2 ) ); ?>%</div>
            </div>
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;">
                <h2 class="hndle"><?php esc_html_e( 'Rejection Rate', 'cookie-consent-king' ); ?></h2>
                <div class="inside" style="font-size: 2em; font-weight: bold;"><?php echo esc_html( number_format( $rejection_rate, 2 ) ); ?>%</div>
            </div>
            <div class="postbox" style="padding: 15px; flex: 1; min-width: 200px;">
                <h2 class="hndle"><?php esc_html_e( 'Customized Selections', 'cookie-consent-king' ); ?></h2>
                <div class="inside" style="font-size: 2em; font-weight: bold;"><?php echo esc_html( number_format( $customization_rate, 2 ) ); ?>%</div>
            </div>
        </div>

        <hr/>

        <div id="cck-charts-and-logs" style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
            <div id="cck-chart-container" style="flex: 1 1 500px;">
                <h2><?php esc_html_e( 'Consent Actions Breakdown', 'cookie-consent-king' ); ?></h2>
                <canvas id="cck-consent-chart" height="250"></canvas>
            </div>

            <div id="cck-logs-container" style="flex: 1 1 500px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php esc_html_e( 'Recent Consent Logs', 'cookie-consent-king' ); ?></h2>
                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin-post.php?action=cck_export_logs' ) ); ?>"><?php esc_html_e( 'Export CSV', 'cookie-consent-king' ); ?></a>
                </div>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'cookie-consent-king' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'cookie-consent-king' ); ?></th>
                            <th><?php esc_html_e( 'IP Address', 'cookie-consent-king' ); ?></th>
                            <th><?php esc_html_e( 'Country', 'cookie-consent-king' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $logs ) ) : ?>
                            <?php foreach ( $logs as $row ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $row->created_at ); ?></td>
                                    <td><span class="cck-action-badge action-<?php echo esc_attr( $row->action ); ?>"><?php echo esc_html( str_replace( '_', ' ', $row->action ) ); ?></span></td>
                                    <td><?php echo esc_html( $row->ip ); ?></td>
                                    <td><?php echo esc_html( $row->country ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4"><?php esc_html_e( 'No consent logs found.', 'cookie-consent-king' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .cck-action-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                color: #fff;
                font-size: 0.9em;
                text-transform: capitalize;
            }
            .action-accept_all { background-color: #4CAF50; /* Green */ }
            .action-reject_all { background-color: #f44336; /* Red */ }
            .action-custom_selection { background-color: #2196F3; /* Blue */ }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('cck-consent-chart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo wp_json_encode( $labels ); ?>,
                        datasets: [{
                            label: '<?php esc_html_e( 'Total Actions', 'cookie-consent-king' ); ?>',
                            data: <?php echo wp_json_encode( $data ); ?>,
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.6)',
                                'rgba(255, 99, 132, 0.6)',
                                'rgba(54, 162, 235, 0.6)',
                                'rgba(255, 206, 86, 0.6)',
                                'rgba(153, 102, 255, 0.6)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(153, 102, 255, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            });
        </script>
    </div>
    <?php
}

/**
 * Render the Banner Styles screen.
 */
function cck_render_banner_styles() {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_banner_styles_nonce']) &&
        wp_verify_nonce($_POST['cck_banner_styles_nonce'], 'cck_save_banner_styles')
    ) {
        $input   = $_POST['cck_banner_styles_options'] ?? [];
        $position = sanitize_text_field($input['position'] ?? 'bottom');
        if (!in_array($position, ['bottom', 'top', 'modal'], true)) {
            $position = 'bottom';
        }
        $options = [
            'bg_color'   => sanitize_text_field($input['bg_color'] ?? ''),
            'text_color' => sanitize_text_field($input['text_color'] ?? ''),
            'position'   => $position,
        ];
        update_option('cck_banner_styles_options', $options);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'cookie-consent-king') . '</p></div>';
    }

    echo '<div class="wrap"><h1>' . esc_html__('Banner Styles', 'cookie-consent-king') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('cck_save_banner_styles', 'cck_banner_styles_nonce');
    do_settings_sections('cck-banner-styles');
    submit_button();
    echo '</form>';
    echo '<h2>' . esc_html__('Preview', 'cookie-consent-king') . '</h2>';
    cck_render_preview_banner();
    echo '</div>';
}

/**
 * Render the Default Texts screen.
 */
function cck_render_default_texts() {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_default_texts_nonce']) &&
        wp_verify_nonce($_POST['cck_default_texts_nonce'], 'cck_save_default_texts')
    ) {
        $input   = $_POST['cck_default_texts_options'] ?? [];
        $options = [
            'title'   => sanitize_text_field($input['title'] ?? ''),
            'message' => sanitize_textarea_field($input['message'] ?? ''),
        ];
        update_option('cck_default_texts_options', $options);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'cookie-consent-king') . '</p></div>';
    }

    echo '<div class="wrap"><h1>' . esc_html__('Default Texts', 'cookie-consent-king') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('cck_save_default_texts', 'cck_default_texts_nonce');
    do_settings_sections('cck-default-texts');
    submit_button();
    echo '</form>';
    echo '<h2>' . esc_html__('Preview', 'cookie-consent-king') . '</h2>';
    cck_render_preview_banner();
    echo '</div>';
}

/**
 * Render the Basic Configuration screen.
 */
function cck_render_basic_configuration() {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_basic_configuration_nonce']) &&
        wp_verify_nonce($_POST['cck_basic_configuration_nonce'], 'cck_save_basic_configuration')
    ) {
        $input   = $_POST['cck_basic_configuration_options'] ?? [];
        $options = [
            'privacy_url' => esc_url_raw($input['privacy_url'] ?? ''),
        ];
        update_option('cck_basic_configuration_options', $options);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'cookie-consent-king') . '</p></div>';
    }

    echo '<div class="wrap"><h1>' . esc_html__('Basic Configuration', 'cookie-consent-king') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('cck_save_basic_configuration', 'cck_basic_configuration_nonce');
    do_settings_sections('cck-basic-configuration');
    submit_button();
    echo '</form>';
    echo '<h2>' . esc_html__('Preview', 'cookie-consent-king') . '</h2>';
    cck_render_preview_banner();
    echo '</div>';
}

/**
 * Render the Cookie List/Analysis screen.
 */
function cck_render_cookie_list() {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_cookie_list_nonce']) &&
        wp_verify_nonce($_POST['cck_cookie_list_nonce'], 'cck_save_cookie_list')
    ) {
        $input   = $_POST['cck_cookie_list_options'] ?? [];
        $options = [
            'list' => sanitize_textarea_field($input['list'] ?? ''),
        ];
        update_option('cck_cookie_list_options', $options);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'cookie-consent-king') . '</p></div>';
    }

    echo '<div class="wrap"><h1>' . esc_html__('Cookie List/Analysis', 'cookie-consent-king') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('cck_save_cookie_list', 'cck_cookie_list_nonce');
    do_settings_sections('cck-cookie-list');
    submit_button();
    echo '</form>';
    echo '<h2>' . esc_html__('Preview', 'cookie-consent-king') . '</h2>';
    cck_render_preview_banner();
    echo '</div>';
}

function cck_render_preview_banner() {
    echo '<div id="root"></div>';
}
add_shortcode('cck_preview_banner', 'cck_render_preview_banner');

function cck_get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']))[0];
        return sanitize_text_field(trim($ip));
    }
    return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
}

function cck_get_country_from_ip($ip) {
    if (empty($ip)) {
        return '';
    }
    $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
    if (is_wp_error($response)) {
        return '';
    }
    return sanitize_text_field(wp_remote_retrieve_body($response));
}

function cck_log_consent() {
    $action = sanitize_text_field($_POST['consent_action'] ?? '');
    if (!$action) {
        wp_send_json_error('missing action');
    }

    global $wpdb;
    $ip      = cck_get_user_ip();
    $country = cck_get_country_from_ip($ip);
    $table   = $wpdb->prefix . 'cck_consent_logs';
    $wpdb->insert(
        $table,
        [
            'action'     => $action,
            'ip'         => $ip,
            'country'    => $country,
            'created_at' => current_time('mysql'),
        ]
    );

    wp_send_json_success();
}
add_action('wp_ajax_cck_log_consent', 'cck_log_consent');
add_action('wp_ajax_nopriv_cck_log_consent', 'cck_log_consent');

function cck_export_logs() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'cookie-consent-king'));
    }
    global $wpdb;
    $table = $wpdb->prefix . 'cck_consent_logs';
    $logs  = $wpdb->get_results("SELECT action, ip, country, created_at FROM $table ORDER BY id DESC");

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cck_consent_logs.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['date', 'action', 'ip', 'country']);
    foreach ($logs as $log) {
        fputcsv($output, [$log->created_at, $log->action, $log->ip, $log->country]);
    }
    fclose($output);
    exit;
}
add_action('admin_post_cck_export_logs', 'cck_export_logs');

/**
 * Render the Translations screen.
 */
function cck_render_translations() {
    $languages_dir = plugin_dir_path(__FILE__) . 'languages/';
    $po_files      = glob($languages_dir . '*.po');
    $locales       = [];
    $strings       = [];

    foreach ($po_files as $file) {
        if (preg_match('/cookie-banner-([a-z_]+)\.po$/i', basename($file), $m)) {
            $locale   = $m[1];
            $locales[] = $locale;
            $entries  = cck_parse_po($file);
            foreach ($entries as $id => $str) {
                $strings[$id][$locale] = $str;
            }
        }
    }

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_translation_nonce']) &&
        wp_verify_nonce($_POST['cck_translation_nonce'], 'cck_save_translations')
    ) {
        foreach ($locales as $locale) {
            $entries = array_map('sanitize_textarea_field', $_POST['trans_' . $locale] ?? []);
            cck_write_po($locale, $entries, $languages_dir);
        }
        cck_generate_mo($languages_dir);
        echo '<div class="updated"><p>' . esc_html__('Translations saved.', 'cookie-consent-king') . '</p></div>';
    }

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_import_po_nonce']) &&
        wp_verify_nonce($_POST['cck_import_po_nonce'], 'cck_import_po') &&
        !empty($_FILES['po_file']['tmp_name'])
    ) {
        $filename = basename($_FILES['po_file']['name']);
        $dest     = $languages_dir . $filename;
        move_uploaded_file($_FILES['po_file']['tmp_name'], $dest);
        cck_generate_mo($languages_dir);
        echo '<div class="updated"><p>' . esc_html__('File imported.', 'cookie-consent-king') . '</p></div>';
    }

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST['cck_regen_mo_nonce']) &&
        wp_verify_nonce($_POST['cck_regen_mo_nonce'], 'cck_regen_mo')
    ) {
        cck_generate_mo($languages_dir);
        echo '<div class="updated"><p>' . esc_html__('.mo files regenerated.', 'cookie-consent-king') . '</p></div>';
    }

    echo '<div class="wrap"><h1>' . esc_html__('Translations', 'cookie-consent-king') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('cck_save_translations', 'cck_translation_nonce');
    echo '<table class="widefat"><thead><tr><th>' . esc_html__('Key', 'cookie-consent-king') . '</th>';
    foreach ($locales as $locale) {
        echo '<th>' . esc_html($locale) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($strings as $id => $vals) {
        echo '<tr><td>' . esc_html($id) . '</td>';
        foreach ($locales as $locale) {
            $val = esc_textarea($vals[$locale] ?? '');
            echo '<td><textarea name="trans_' . esc_attr($locale) . '[' . esc_attr($id) . ']" rows="1" cols="25">' . $val . '</textarea></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    submit_button(__('Save Translations', 'cookie-consent-king'));
    echo '</form>';

    echo '<h2>' . esc_html__('Import / Export', 'cookie-consent-king') . '</h2>';
    echo '<form method="post" enctype="multipart/form-data" style="margin-bottom:1em;">';
    wp_nonce_field('cck_import_po', 'cck_import_po_nonce');
    echo '<input type="file" name="po_file" /> ';
    submit_button(__('Import .po/.pot', 'cookie-consent-king'), 'secondary', 'import_po', false);
    echo '</form>';

    echo '<p>';
    foreach ($po_files as $file) {
        $url = plugins_url('languages/' . basename($file), __FILE__);
        echo '<a class="button" href="' . esc_url($url) . '">' . esc_html__('Export', 'cookie-consent-king') . ' ' . esc_html(basename($file)) . '</a> ';
    }
    echo '</p>';

    echo '<form method="post">';
    wp_nonce_field('cck_regen_mo', 'cck_regen_mo_nonce');
    submit_button(__('Regenerate .mo files', 'cookie-consent-king'), 'secondary', 'regen_mo', false);
    echo '</form>';

    echo '</div>';
}

/**
 * Parse a .po file into an array of translations.
 */
function cck_parse_po($file) {
    $lines   = file($file);
    $entries = [];
    $id      = null;
    foreach ($lines as $line) {
        if (0 === strpos($line, 'msgid ')) {
            $id = trim(substr($line, 6), "\"\n");
        } elseif ($id !== null && 0 === strpos($line, 'msgstr ')) {
            $entries[$id] = trim(substr($line, 7), "\"\n");
            $id           = null;
        }
    }
    return $entries;
}

/**
 * Write translations back to a .po file.
 */
function cck_write_po($locale, $entries, $dir) {
    $file  = $dir . "cookie-banner-$locale.po";
    $lines = file_exists($file) ? file($file) : [];
    $out   = [];
    $id    = null;
    foreach ($lines as $line) {
        if (0 === strpos($line, 'msgid ')) {
            $id   = trim(substr($line, 6), "\"\n");
            $out[] = $line;
        } elseif ($id !== null && 0 === strpos($line, 'msgstr ')) {
            $new   = $entries[$id] ?? '';
            $out[] = 'msgstr "' . addslashes($new) . "\n";
            $id    = null;
        } else {
            $out[] = $line;
        }
    }
    file_put_contents($file, implode('', $out));
}

/**
 * Generate .mo files for all .po files in directory using msgfmt.
 */
function cck_generate_mo($dir) {
    $po_files = glob($dir . '*.po');
    foreach ($po_files as $po) {
        $mo  = substr($po, 0, -3) . 'mo';
        $cmd = sprintf('msgfmt %s -o %s', escapeshellarg($po), escapeshellarg($mo));
        shell_exec($cmd);
    }
}

?>
