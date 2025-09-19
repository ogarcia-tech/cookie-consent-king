<?php
class CCK_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('wp_footer', [$this, 'render_banner_html'], 999);
    }

    public function enqueue_public_assets() {
        wp_enqueue_style('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.css', [], CCK_VERSION);
        wp_enqueue_script('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.js', [], CCK_VERSION, true);

        $options = get_option('cck_options', []);
        
        $title = $options['title'] ?? __('Política de Cookies', 'cookie-consent-king');
        $message = $options['message'] ?? __('Utilizamos cookies esenciales para el funcionamiento del sitio y cookies de análisis para mejorar tu experiencia. Puedes aceptar todas, rechazarlas o personalizar tus preferencias. Lee nuestra {privacy_policy_link}.', 'cookie-consent-king');
        $privacy_url = $options['privacy_policy_url'] ?? '';
        $details_description = $options['details_description'] ?? __('Elige qué categorías de cookies quieres activar. Puedes modificar tu elección en cualquier momento.', 'cookie-consent-king');
        $rgpd_text = $options['rgpd_text'] ?? __('Cumplimos con el RGPD. Consulta nuestra {privacy_policy_link} para obtener más información sobre cómo utilizamos las cookies.', 'cookie-consent-king');

        if (function_exists('pll__')) {
            $title = pll__($title);
            $message = pll__($message);
            $details_description = pll__($details_description);
            $rgpd_text = pll__($rgpd_text);
        }
        if (function_exists('do_action')) {
            $title = apply_filters('wpml_translate_string', $title, 'Banner Title', ['domain' => 'Cookie Consent King']);
            $message = apply_filters('wpml_translate_string', $message, 'Banner Message', ['domain' => 'Cookie Consent King']);
            $details_description = apply_filters('wpml_translate_string', $details_description, 'Banner Details Description', ['domain' => 'Cookie Consent King']);
            $rgpd_text = apply_filters('wpml_translate_string', $rgpd_text, 'Banner RGPD Text', ['domain' => 'Cookie Consent King']);
        }

        $privacy_link_text = __('política de privacidad', 'cookie-consent-king');
        $privacy_link = !empty($privacy_url) ? "<a href='" . esc_url($privacy_url) . "' target='_blank' rel='noopener noreferrer'>$privacy_link_text</a>" : '';
        $message_processed = str_replace('{privacy_policy_link}', $privacy_link, $message);
        $rgpd_text_processed = str_replace('{privacy_policy_link}', $privacy_link, $rgpd_text);

        $force_show = !empty($options['force_show']);
        $debug_mode = !empty($options['debug']);
        $test_button_text = $options['test_button_text'] ?? __('Limpiar y Probar', 'cookie-consent-king');
        $test_button_url = esc_url($options['test_button_url'] ?? '');

        $texts = [
            'title' => $title,
            'message' => $message_processed,
            'acceptAll' => __('Aceptar todas', 'cookie-consent-king'),
            'rejectAll' => __('Rechazar todas', 'cookie-consent-king'),
            'personalize' => __('Personalizar', 'cookie-consent-king'),
            'savePreferences' => __('Guardar preferencias', 'cookie-consent-king'),
            'preferences' => __('Preferencias', 'cookie-consent-king'),
            'analytics' => __('Análisis', 'cookie-consent-king'),
            'marketing' => __('Marketing', 'cookie-consent-king'),
            'testButton' => $test_button_text,
            'testHelp' => __('Guía de pruebas', 'cookie-consent-king'),

        ];

        wp_localize_script('cck-banner', 'cckData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cck_log_consent_nonce'),
            'icon_url' => esc_url($options['icon_url'] ?? ''),
            'reopen_icon_url' => esc_url($options['reopen_icon_url'] ?? ''),
            'forceShow' => (bool) $force_show,
            'debug' => (bool) $debug_mode,
            'testButton' => [
                'text' => $test_button_text,
                'helpUrl' => $test_button_url,
                'helpLabel' => __('Abrir documentación', 'cookie-consent-king'),
            ],
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
        echo '<div id="cck-reopen-trigger-container"></div>';
    }
}