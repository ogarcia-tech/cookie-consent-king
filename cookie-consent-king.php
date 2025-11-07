<?php
/**
 * Plugin Name: Cookie Consent King
 * Plugin URI:  https://www.metricaweb.es
 * Description: Un banner de consentimiento de cookies avanzado y personalizable, nativo de WordPress.
 * Version:   4.3.1 Professional
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Author:    David Adell (Metricaweb) & Oscar Garcia
 * License:   GPL2
 * Text Domain: cookie-consent-king
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

define('CCK_VERSION', '4.3.1');
define('CCK_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Cargar las clases principales del plugin
require_once CCK_PLUGIN_DIR . 'admin/class-cck-admin.php';
require_once CCK_PLUGIN_DIR . 'public/class-cck-public.php';

// Iniciar el plugin
function cck_run_plugin() {
    new CCK_Admin();
    new CCK_Public();
}
add_action('plugins_loaded', 'cck_run_plugin');

// Funciones de activación y dominio de texto
register_activation_hook(__FILE__, ['CCK_Admin', 'activate']);
add_action('init', function() {
    load_plugin_textdomain('cookie-consent-king', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
