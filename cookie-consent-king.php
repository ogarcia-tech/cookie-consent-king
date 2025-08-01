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

function cck_enqueue_assets() {
    $asset_path = plugin_dir_path(__FILE__) . 'dist/assets/';

    $js_files = glob($asset_path . '*.js');
    if ($js_files) {
        $js_file = basename($js_files[0]);
        wp_enqueue_script(
            'cookie-consent-king-js',
            plugin_dir_url(__FILE__) . 'dist/assets/' . $js_file,
            [],
            null,
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
    }

    $css_files = glob($asset_path . '*.css');
    if ($css_files) {
        $css_file = basename($css_files[0]);
        wp_enqueue_style(
            'cookie-consent-king-css',
            plugin_dir_url(__FILE__) . 'dist/assets/' . $css_file,
            [],
            null
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
