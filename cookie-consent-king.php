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

function cck_activate() {
    // Placeholder for activation logic.
}
register_activation_hook(__FILE__, 'cck_activate');

function cck_deactivate() {
    // Placeholder for deactivation logic.
}
register_deactivation_hook(__FILE__, 'cck_deactivate');

?>
