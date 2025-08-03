<?php
/**
 * Plugin Name: Cookie Consent King
 * Plugin URI: https://www.metricaweb.es
 * Description: Provides a React-powered cookie consent banner.
 * Version: 2.0
 * Author: David Adell (Metricaweb) & Oscar Garcia
 * License: GPL2

*/


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!defined('COOKIE_CONSENT_KING_VERSION')) {
    define('COOKIE_CONSENT_KING_VERSION', '1.0.0');
}

function cck_enqueue_assets() {
    $asset_path = plugin_dir_path(__FILE__) . 'dist/assets/';
    $asset_url  = plugin_dir_url(__FILE__) . 'dist/assets/';

    $js_path  = $asset_path . 'index.js';
    $css_path = $asset_path . 'index.css';

    if (!file_exists($js_path) || !file_exists($css_path)) {
        wp_admin_notice(
            __('Cookie Consent King: assets not found. Please run "npm run build".', 'cookie-consent-king'),
            ['type' => 'error']
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

        wp_localize_script('cookie-consent-king-js', 'cckTranslations', $translations);
        add_action('wp_footer', 'cck_render_root_div');
    }


    wp_enqueue_style(
        'cookie-consent-king-css',
        $asset_url . 'index.css',
        [],
        filemtime($css_path)
    );
}

function cck_render_root_div() {
    echo '<div id="root"></div>';
}
add_action('wp_enqueue_scripts', 'cck_enqueue_assets');

function cck_load_textdomain() {
    load_plugin_textdomain(
        'cookie-consent-king',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'cck_load_textdomain');

function cck_activate() {
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
}
add_action('admin_menu', 'cck_register_admin_menu');

// -----------------------------------------------------------------------------
// Settings API registration
// -----------------------------------------------------------------------------

function cck_settings_init() {
    // Banner Styles options.
    register_setting('cck_banner_styles_group', 'cck_banner_styles_options');
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

/**
 * Render the Dashboard screen.
 */
function cck_render_dashboard() {
    echo '<div class="wrap"><h1>' . esc_html__('Dashboard', 'cookie-consent-king') . '</h1><p>' . esc_html__('Admin dashboard placeholder.', 'cookie-consent-king') . '</p></div>';
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
        $options = [
            'bg_color'   => sanitize_text_field($input['bg_color'] ?? ''),
            'text_color' => sanitize_text_field($input['text_color'] ?? ''),
        ];
        update_option('cck_banner_styles_options', $options);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'cookie-consent-king') . '</p></div>';
    }

    echo '<div class="wrap"><h1>' . esc_html__('Banner Styles', 'cookie-consent-king') . '</h1>';
    echo '<form method="post">';
    wp_nonce_field('cck_save_banner_styles', 'cck_banner_styles_nonce');
    do_settings_sections('cck-banner-styles');
    submit_button();
    echo '</form></div>';
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
    echo '</form></div>';
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
    echo '</form></div>';
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
    echo '</form></div>';
}

?>
