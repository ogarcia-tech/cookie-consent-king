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
        
        // **MEJORA**: Cargar textos dinámicos y prepararlos para traducción
        $title = $options['title'] ?? __('Política de Cookies', 'cookie-consent-king');
        $message = $options['message'] ?? __('Utilizamos cookies esenciales para el funcionamiento del sitio y cookies de análisis para mejorar tu experiencia. Puedes aceptar todas, rechazarlas o personalizar tus preferencias.', 'cookie-consent-king');

        // **MEJORA**: Aplicar traducciones de WPML/Polylang si existen
        if (function_exists('pll__')) {
            $title = pll__($title);
            $message = pll__($message);
        }
        if (function_exists('do_action')) {
            $title = apply_filters('wpml_translate_string', $title, 'Banner Title', ['domain' => 'Cookie Consent King']);
            $message = apply_filters('wpml_translate_string', $message, 'Banner Message', ['domain' => 'Cookie Consent King']);
        }
        
        $texts = [
            'title' => $title,
            'message' => $message,
            'acceptAll' => __('Aceptar todas', 'cookie-consent-king'),
            'rejectAll' => __('Rechazar todas', 'cookie-consent-king'),
            'personalize' => __('Personalizar', 'cookie-consent-king'),
            'savePreferences' => __('Guardar preferencias', 'cookie-consent-king'),
            'preferences' => __('Preferencias', 'cookie-consent-king'),
            'analytics' => __('Análisis', 'cookie-consent-king'),
            'marketing' => __('Marketing', 'cookie-consent-king'),
        ];

        wp_localize_script('cck-banner', 'cckData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cck_log_consent_nonce'),
            'icon_url' => esc_url($options['icon_url'] ?? ''),
            'texts'    => $texts,
        ]);
        
        $dynamic_css = ":root {
            --cck-bg-color: " . esc_attr($options['bg_color'] ?? '#ffffff') . ";
            --cck-text-color: " . esc_attr($options['text_color'] ?? '#333333') . ";
            --cck-primary-btn-bg: " . esc_attr($options['btn_primary_bg'] ?? '#000000') . ";
            --cck-primary-btn-text: " . esc_attr($options['btn_primary_text'] ?? '#ffffff') . ";
        }";
        wp_add_inline_style('cck-banner', $dynamic_css);
    }
    
    public function render_banner_html() {
        echo '<div id="cck-banner-container"></div>';
    }
}