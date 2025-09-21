<?php
class CCK_Public {

    private static $instance = null;
    private $script_registry = [];

    public function __construct() {
        self::$instance = $this;

        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('wp_footer', [$this, 'render_banner_html'], 1000);

        if (!is_admin()) {
            add_filter('script_loader_tag', [$this, 'filter_script_loader_tag'], 99, 3);
        }
    }

    /**
     * Permite a otros plugins o al tema registrar un script para el manejo de consentimiento.
     *
     * @param string $handle El "handle" del script de WordPress.
     * @param string $category La categoría de consentimiento ('analytics', 'marketing', 'preferences').
     */
    public static function register_script_category($handle, $category) {
        if (self::$instance instanceof self && !empty($handle) && !empty($category)) {
            self::$instance->script_registry[$handle] = ['category' => $category];
        }
    }

    /**
     * Encola los assets públicos (CSS y JS) y pasa los datos de PHP a JavaScript.
     */
    public function enqueue_public_assets() {
        wp_enqueue_style('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.css', [], CCK_VERSION);
        wp_enqueue_script('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.js', [], CCK_VERSION, true);

        $options = get_option('cck_options', []);
        
        // Centralizamos la obtención y traducción de todos los textos.
        $texts = $this->get_translated_texts($options);

        wp_localize_script('cck-banner', 'cckData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cck_log_consent_nonce'),
            'icon_url' => esc_url($options['icon_url'] ?? ''),
            'reopen_icon_url' => esc_url($options['reopen_icon_url'] ?? ''),
            'forceShow' => !empty($options['force_show']),
            'debug' => !empty($options['debug']),
            'testButton' => [
                'text'      => $options['test_button_text'] ?? $texts['testButton'],
                'helpUrl'   => esc_url($options['test_button_url'] ?? ''),
                'helpLabel' => $texts['testHelp'],
            ],
            'texts' => $texts,
        ]);
        
        // Inyecta los colores personalizados como variables CSS.
        $dynamic_css = ":root {
            --cck-bg-color: " . esc_attr($options['bg_color'] ?? '#ffffff') . ";
            --cck-text-color: " . esc_attr($options['text_color'] ?? '#333333') . ";
            --cck-primary-btn-bg: " . esc_attr($options['btn_primary_bg'] ?? '#000000') . ";
            --cck-primary-btn-text: " . esc_attr($options['btn_primary_text'] ?? '#ffffff') . ";
        }";
        wp_add_inline_style('cck-banner', $dynamic_css);
    }

    /**
     * Recopila y traduce todos los textos necesarios para el banner.
     */
    private function get_translated_texts($options) {
        $privacy_url = $options['privacy_policy_url'] ?? '';
        $privacy_link_text = __('política de privacidad', 'cookie-consent-king');
        $privacy_link = !empty($privacy_url) 
            ? "<a href='" . esc_url($privacy_url) . "' target='_blank' rel='noopener noreferrer'>$privacy_link_text</a>" 
            : $privacy_link_text;

        $raw_texts = [
            'title'           => $options['title'] ?? __('Gestión de Cookies', 'cookie-consent-king'),
            'message'         => $options['message'] ?? sprintf(__('Usamos cookies para mejorar tu experiencia. Lee nuestra %s.', 'cookie-consent-king'), '{privacy_policy_link}'),
            'acceptAll'       => __('Aceptar todas', 'cookie-consent-king'),
            'rejectAll'       => __('Rechazar todas', 'cookie-consent-king'),
            'personalize'     => __('Personalizar', 'cookie-consent-king'),
            'savePreferences' => __('Guardar preferencias', 'cookie-consent-king'),
            'settingsTitle'   => __('Configuración de Cookies', 'cookie-consent-king'),
            'back'            => __('Volver', 'cookie-consent-king'),
            'necessary'       => __('Necesario', 'cookie-consent-king'),
            'preferences'     => __('Preferencias', 'cookie-consent-king'),
            'analytics'       => __('Análisis', 'cookie-consent-king'),
            'marketing'       => __('Marketing', 'cookie-consent-king'),
            'reopenTrigger'   => __('Gestionar consentimiento', 'cookie-consent-king'),
            'testButton'      => __('Limpiar y Probar', 'cookie-consent-king'),
            'testHelp'        => __('Ver guía de pruebas', 'cookie-consent-king'),
        ];

        // Compatibilidad con WPML/Polylang para los textos dinámicos.
        if (function_exists('pll__')) {
            $raw_texts['title'] = pll__($raw_texts['title']);
            $raw_texts['message'] = pll__($raw_texts['message']);
        }
        if (function_exists('apply_filters')) {
            $raw_texts['title'] = apply_filters('wpml_translate_string', $raw_texts['title'], 'Banner Title', ['domain' => 'Cookie Consent King']);
            $raw_texts['message'] = apply_filters('wpml_translate_string', $raw_texts['message'], 'Banner Message', ['domain' => 'Cookie Consent King']);
        }

        // Inserta el enlace de privacidad en el mensaje final.
        $raw_texts['message'] = str_replace('{privacy_policy_link}', $privacy_link, $raw_texts['message']);

        return $raw_texts;
    }
    
    /**
     * Renderiza los contenedores HTML en el footer donde se montará el banner y el botón.
     */
    public function render_banner_html() {
        echo '<div id="cck-banner-container"></div>';
        echo '<div id="cck-reopen-trigger-container"></div>';
    }

    /**
     * Filtra la etiqueta <script> para bloquearla si no hay consentimiento.
     */
    public function filter_script_loader_tag($tag, $handle, $src) {
        $category = $this->script_registry[$handle]['category'] ?? null;
        
        if ($this->user_has_consent($category)) {
            return $tag;
        }

        // Modifica la etiqueta para que el navegador no la ejecute y JS pueda gestionarla.
        $blocked_tag = str_replace('<script ', '<script type="text/plain" data-cck-consent="' . esc_attr($category) . '" ', $tag);
        
        // Mueve los atributos clave a data-* para que JS pueda restaurarlos.
        $blocked_tag = preg_replace('/ src=([\'"])(.*?)\1/', ' data-src=$1$2$1', $blocked_tag, 1);
        $blocked_tag = preg_replace('/ type=([\'"])(.*?)\1/', ' data-cck-orig-type=$1$2$1', $blocked_tag, 1);
        
        return $blocked_tag;
    }

    /**
     * Comprueba del lado del servidor si el usuario ya ha dado consentimiento.
     * Esto evita que el script se bloquee innecesariamente en cargas de página posteriores.
     */
    protected function user_has_consent($category) {
        // Si no hay categoría, no se bloquea. Si es 'necessary', nunca se bloquea.
        if (empty($category) || $category === 'necessary') {
            return true;
        }
        
        // Si la cookie no existe, el consentimiento no ha sido otorgado.
        if (!isset($_COOKIE['cck_consent'])) {
            return false;
        }

        $consent_data = json_decode(stripslashes($_COOKIE['cck_consent']), true);
        
        // Devuelve true si el consentimiento para esa categoría es true.
        return is_array($consent_data) && !empty($consent_data[$category]);
    }
}
