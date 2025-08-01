<?php
/**
 * Plugin Name: Cookie Consent King
 * Plugin URI: https://example.com
 * Description: Provides a React-powered cookie consent banner.
 * Version: 1.0.0
 * Author: Your Name
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

    $js_files = glob($asset_path . '*.js');
    if ($js_files) {
        $js_file = basename($js_files[0]);
        wp_enqueue_script(
            'cookie-consent-king-js',
            plugin_dir_url(__FILE__) . 'dist/assets/' . $js_file,
            [],
            COOKIE_CONSENT_KING_VERSION,
            true
        );
    }

    $css_files = glob($asset_path . '*.css');
    if ($css_files) {
        $css_file = basename($css_files[0]);
        wp_enqueue_style(
            'cookie-consent-king-css',
            plugin_dir_url(__FILE__) . 'dist/assets/' . $css_file,
            [],
            COOKIE_CONSENT_KING_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'cck_enqueue_assets');

function cck_load_textdomain() {
    load_plugin_textdomain(
        'cookie-banner',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'cck_load_textdomain');

function cck_activate() {
    // Placeholder for activation logic.
}
register_activation_hook(__FILE__, 'cck_activate');

function cck_deactivate() {
    // Placeholder for deactivation logic.
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
    echo '<div class="wrap"><h1>' . esc_html__('Banner Styles', 'cookie-consent-king') . '</h1><p>' . esc_html__('Placeholder for banner styles.', 'cookie-consent-king') . '</p></div>';
}

/**
 * Render the Default Texts screen.
 */
function cck_render_default_texts() {
    echo '<div class="wrap"><h1>' . esc_html__('Default Texts', 'cookie-consent-king') . '</h1><p>' . esc_html__('Placeholder for default texts.', 'cookie-consent-king') . '</p></div>';
}

/**
 * Render the Basic Configuration screen.
 */
function cck_render_basic_configuration() {
    echo '<div class="wrap"><h1>' . esc_html__('Basic Configuration', 'cookie-consent-king') . '</h1><p>' . esc_html__('Placeholder for basic configuration.', 'cookie-consent-king') . '</p></div>';
}

/**
 * Render the Cookie List/Analysis screen.
 */
function cck_render_cookie_list() {
    echo '<div class="wrap"><h1>' . esc_html__('Cookie List/Analysis', 'cookie-consent-king') . '</h1><p>' . esc_html__('Placeholder for cookie list or analysis.', 'cookie-consent-king') . '</p></div>';
}

?>
