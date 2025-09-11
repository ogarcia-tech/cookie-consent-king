<?php
class CCK_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('wp_footer', [$this, 'render_banner_html']);
    }

    public function enqueue_public_assets() {
        wp_enqueue_style('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.css', [], CCK_VERSION);
        wp_enqueue_script('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.js', [], CCK_VERSION, true);

        $options = get_option('cck_options', []);
        
        $title = $options['title'] ?? __('Política de Cookies', 'cookie-consent-king');
        $message = $options['message'] ?? __('Utilizamos cookies esenciales para el funcionamiento del sitio y cookies de análisis para mejorar tu experiencia. Puedes aceptar todas, rechazarlas o personalizar tus preferencias. Lee nuestra {privacy_policy_link}.', 'cookie-consent-king');
        $privacy_url = $options['privacy_policy_url'] ?? '';

        if (function_exists('pll__')) {
            $title = pll__($title);
            $message = pll__($message);
        }
        if (function_exists('do_action')) {
            $title = apply_filters('wpml_translate_string', $title, 'Banner Title', ['domain' => 'Cookie Consent King']);
            $message = apply_filters('wpml_translate_string', $message, 'Banner Message', ['domain' => 'Cookie Consent King']);
        }
        
        // Crear el enlace de la política de privacidad
        $privacy_link_text = __('política de privacidad', 'cookie-consent-king');
        $privacy_link = !empty($privacy_url) ? "<a href='" . esc_url($privacy_url) . "' target='_blank' rel='noopener noreferrer'>$privacy_link_text</a>" : '';
        $message = str_replace('{privacy_policy_link}', $privacy_link, $message);

        $texts = [
            'title' => $title,
            'message' => $message,
            'acceptAll' => __('Aceptar todas', 'cookie-consent-king'),
            'rejectAll' => __('Rechazar todas', 'cookie-consent-king'),
            // ... (resto de textos)
        ];

        wp_localize_script('cck-banner', 'cckData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cck_log_consent_nonce'),
            'icon_url' => esc_url($options['icon_url'] ?? ''),
            'reopen_icon_url' => esc_url($options['reopen_icon_url'] ?? plugin_dir_url(dirname(__FILE__)) . 'public/cookie.svg'), // Icono por defecto
            'texts'    => $texts,
        ]);
        
        $dynamic_css = ":root { /* ... (código sin cambios) ... */ }";
        wp_add_inline_style('cck-banner', $dynamic_css);
    }
    
    public function render_banner_html() {
        echo '<div id="cck-banner-container"></div>';
        echo '<div id="cck-reopen-trigger-container"></div>'; // Contenedor para el icono de reabrir
    }
}