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

    public static function register_script_category($handle, $category) {
        if (self::$instance instanceof self && !empty($handle) && !empty($category)) {
            self::$instance->script_registry[$handle] = ['category' => $category];
        }
    }

    public function enqueue_public_assets() {
        wp_enqueue_style('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.css', [], CCK_VERSION);
        wp_enqueue_script('cck-banner', plugin_dir_url(__FILE__) . 'cck-banner.js', [], CCK_VERSION, true);

        $options = get_option('cck_options', []);
        $texts = $this->get_translated_texts($options);
        $events = $this->get_event_identifiers($options);

        wp_localize_script('cck-banner', 'cckData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cck_log_consent_nonce'),
            'icon_url' => esc_url($options['icon_url'] ?? ''),
            'reopen_icon_url' => esc_url($options['reopen_icon_url'] ?? ''),
            'forceShow' => !empty($options['force_show']),
            'debug' => !empty($options['debug']),
            'testButton' => [
                'text'      => $options['test_button_text'] ?? '', // Se oculta si está vacío
                'helpUrl'   => esc_url($options['test_button_url'] ?? ''),
                'helpLabel' => $texts['testHelp'],
            ],
            'texts' => $texts,
            'events' => $events,
        ]);
        
        $dynamic_css = ":root {
            --cck-bg-color: " . esc_attr($options['bg_color'] ?? '#ffffff') . ";
            --cck-text-color: " . esc_attr($options['text_color'] ?? '#333333') . ";
            --cck-primary-btn-bg: " . esc_attr($options['btn_primary_bg'] ?? '#000000') . ";
            --cck-primary-btn-text: " . esc_attr($options['btn_primary_text'] ?? '#ffffff') . ";
        }";
        wp_add_inline_style('cck-banner', $dynamic_css);
    }

    private function get_translated_texts($options) {
        $defaults = [
            'title'            => __('Política de Cookies', 'cookie-consent-king'),
            'message'          => sprintf(__('Utilizamos cookies esenciales para el funcionamiento del sitio y cookies de análisis para mejorar tu experiencia. Puedes aceptar todas, rechazarlas o personalizar tus preferencias. Lee nuestra %s.', 'cookie-consent-king'), '{privacy_policy_link}'),
            'privacyLinkText'  => __('política de privacidad', 'cookie-consent-king'),
            'acceptAll'        => __('Aceptar todas', 'cookie-consent-king'),
            'rejectAll'        => __('Rechazar todas', 'cookie-consent-king'),
            'personalize'      => __('Personalizar', 'cookie-consent-king'),
            'savePreferences'  => __('Guardar preferencias', 'cookie-consent-king'),
            'settingsTitle'    => __('Configuración de Cookies', 'cookie-consent-king'),
            'back'             => __('Volver', 'cookie-consent-king'),
            'necessary'        => __('Cookies necesarias', 'cookie-consent-king'),
            'necessaryInfo'    => __('(siempre activas)', 'cookie-consent-king'),
            'preferences'      => __('Preferencias', 'cookie-consent-king'),
            'analytics'        => __('Análisis', 'cookie-consent-king'),
            'marketing'        => __('Marketing', 'cookie-consent-king'),
            'reopenTrigger'    => __('Gestionar consentimiento', 'cookie-consent-king'),
            'testHelp'         => __('Ver guía de pruebas', 'cookie-consent-king'),
            'desc_necessary'   => $options['description_necessary'] ?? '',
            'desc_preferences' => $options['description_preferences'] ?? '',
            'desc_analytics'   => $options['description_analytics'] ?? '',
            'desc_marketing'   => $options['description_marketing'] ?? '',
        ];

        $raw_texts = $defaults;

        $option_map = [
            'title'                   => 'title',
            'message'                 => 'message',
            'privacy_link_label'      => 'privacyLinkText',
            'label_accept_all'        => 'acceptAll',
            'label_reject_all'        => 'rejectAll',
            'label_personalize'       => 'personalize',
            'label_save_preferences'  => 'savePreferences',
            'label_settings_title'    => 'settingsTitle',
            'label_back'              => 'back',
            'label_necessary_title'   => 'necessary',
            'label_necessary_info'    => 'necessaryInfo',
            'label_preferences_title' => 'preferences',
            'label_analytics_title'   => 'analytics',
            'label_marketing_title'   => 'marketing',
            'label_reopen_trigger'    => 'reopenTrigger',
            'label_test_help'         => 'testHelp',
        ];

        foreach ($option_map as $option_key => $text_key) {
            if (!empty($options[$option_key])) {
                $raw_texts[$text_key] = $options[$option_key];
            }
        }

        $privacy_url = $options['privacy_policy_url'] ?? '';

        // Aplicar traducciones de Polylang/WPML si existen
        $translatable = [
            'title',
            'message',
            'privacyLinkText',
            'acceptAll',
            'rejectAll',
            'personalize',
            'savePreferences',
            'settingsTitle',
            'back',
            'necessary',
            'necessaryInfo',
            'desc_necessary',
            'desc_preferences',
            'desc_analytics',
            'desc_marketing',
            'preferences',
            'analytics',
            'marketing',
            'reopenTrigger',
            'testHelp',
        ];

        foreach ($translatable as $key) {
            if (!isset($raw_texts[$key]) || $raw_texts[$key] === '') {
                continue;
            }
            if (function_exists('pll__')) {
                $raw_texts[$key] = pll__($raw_texts[$key]);
            }
            if (function_exists('apply_filters')) {
                $raw_texts[$key] = apply_filters('wpml_translate_string', $raw_texts[$key], 'cck-' . $key, ['domain' => 'Cookie Consent King']);
            }
        }

        $privacy_link_text = $raw_texts['privacyLinkText'];
        $privacy_link = !empty($privacy_url)
            ? "<a href='" . esc_url($privacy_url) . "' target='_blank' rel='noopener noreferrer'>$privacy_link_text</a>"
            : $privacy_link_text;

        $raw_texts['message'] = str_replace('{privacy_policy_link}', $privacy_link, $raw_texts['message']);

        return $raw_texts;
    }

    private function get_event_identifiers($options) {
        $events = [
            'dataLayerEvent' => !empty($options['event_consent_update']) ? $options['event_consent_update'] : 'consent_update',
            'actions'        => [
                'acceptAll'       => !empty($options['event_accept_all']) ? $options['event_accept_all'] : 'accept_all',
                'rejectAll'       => !empty($options['event_reject_all']) ? $options['event_reject_all'] : 'reject_all',
                'customSelection' => !empty($options['event_custom_selection']) ? $options['event_custom_selection'] : 'custom_selection',
                'openPersonalize' => !empty($options['event_open_personalize']) ? $options['event_open_personalize'] : 'open_personalize',
            ],
            'toggles'        => [
                'preferences' => !empty($options['event_toggle_preferences']) ? $options['event_toggle_preferences'] : 'toggle_preferences',
                'analytics'   => !empty($options['event_toggle_analytics']) ? $options['event_toggle_analytics'] : 'toggle_analytics',
                'marketing'   => !empty($options['event_toggle_marketing']) ? $options['event_toggle_marketing'] : 'toggle_marketing',
            ],
            'states'         => [
                'granted' => !empty($options['event_state_granted']) ? $options['event_state_granted'] : 'granted',
                'denied'  => !empty($options['event_state_denied']) ? $options['event_state_denied'] : 'denied',
            ],
        ];

        $translatable = [
            ['ref' => &$events['dataLayerEvent'], 'label' => 'Data Layer Event Name'],
            ['ref' => &$events['actions']['acceptAll'], 'label' => 'Accept All Event Action'],
            ['ref' => &$events['actions']['rejectAll'], 'label' => 'Reject All Event Action'],
            ['ref' => &$events['actions']['customSelection'], 'label' => 'Custom Selection Event Action'],
            ['ref' => &$events['actions']['openPersonalize'], 'label' => 'Open Personalize Event Action'],
            ['ref' => &$events['toggles']['preferences'], 'label' => 'Toggle Preferences Event'],
            ['ref' => &$events['toggles']['analytics'], 'label' => 'Toggle Analytics Event'],
            ['ref' => &$events['toggles']['marketing'], 'label' => 'Toggle Marketing Event'],
            ['ref' => &$events['states']['granted'], 'label' => 'Consent State Granted'],
            ['ref' => &$events['states']['denied'], 'label' => 'Consent State Denied'],
        ];

        foreach ($translatable as &$entry) {
            $value = $entry['ref'];
            if ($value === '') {
                continue;
            }
            if (function_exists('pll__')) {
                $value = pll__($value);
            }
            if (function_exists('apply_filters')) {
                $value = apply_filters('wpml_translate_single_string', $value, 'Cookie Consent King', $entry['label']);
            }
            $entry['ref'] = $value;
        }
        unset($entry);

        return $events;
    }
    
    public function render_banner_html() {
        echo '<div id="cck-banner-container"></div>';
        echo '<div id="cck-reopen-trigger-container"></div>';
    }

    public function filter_script_loader_tag($tag, $handle, $src) {
        $category = $this->script_registry[$handle]['category'] ?? null;
        if ($this->user_has_consent($category)) {
            return $tag;
        }

        $blocked_tag = str_replace('<script ', '<script type="text/plain" data-cck-consent="' . esc_attr($category) . '" ', $tag);
        $blocked_tag = preg_replace('/ src=([\'"])(.*?)\1/', ' data-src=$1$2$1', $blocked_tag, 1);
        $blocked_tag = preg_replace('/ type=([\'"])(.*?)\1/', ' data-cck-orig-type=$1$2$1', $blocked_tag, 1);
        
        return $blocked_tag;
    }

    protected function user_has_consent($category) {
        if (empty($category) || $category === 'necessary') {
            return true;
        }
        if (!isset($_COOKIE['cck_consent'])) {
            return false;
        }
        $consent_data = json_decode(stripslashes($_COOKIE['cck_consent']), true);
        return is_array($consent_data) && !empty($consent_data[$category]);
    }
}
