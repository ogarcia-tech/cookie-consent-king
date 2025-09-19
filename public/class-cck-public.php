<?php
class CCK_Public {

    /**
     * Instance reference used to expose the registration helpers before the
     * object is constructed by WordPress.
     *
     * @var self|null
     */
    protected static $instance = null;

    /**
     * Cache of registered script handles and their metadata.
     *
     * @var array<string, array<string, mixed>>
     */
    protected $script_registry = [];

    /**
     * Blocked scripts collected during the request lifecycle.
     *
     * @var array<int, array<string, mixed>>
     */
    protected $blocked_scripts = [];

    /**
     * Memoized consent state decoded from the consent cookie.
     *
     * @var array<string, bool>
     */
    protected $consent_cache = [];

    /**
     * Flag to avoid starting multiple output buffers.
     *
     * @var bool
     */
    protected $buffer_active = false;

    public function __construct() {
        self::$instance = $this;

        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('wp_footer', [$this, 'render_banner_html'], 999);

        if (!is_admin()) {
            add_filter('script_loader_tag', [$this, 'filter_script_loader_tag'], 10, 3);
            add_action('template_redirect', [$this, 'start_output_buffer'], 0);
            add_action('shutdown', [$this, 'end_output_buffer'], 0);
            add_action('wp_footer', [$this, 'print_blocked_scripts_store'], 5);
        }
    }

    /**
     * Registers a third-party script handle that must wait for consent before
     * being executed.
     *
     * This method can be used directly or through the static proxy
     * {@see CCK_Public::register_script_category()} to ensure compatibility with
     * page builders (Elementor, Divi, etc.) that register their scripts outside
     * the standard WordPress enqueue flow.
     *
     * Inline scripts rendered directly by builders can alternatively declare the
     * category using the `data-cck-consent` attribute, which is handled by the
     * output buffer started by this class.
     *
     * @param string $handle   Script handle passed to wp_enqueue_script().
     * @param string $category Consent category (e.g. analytics, marketing).
     * @param array  $args     Optional metadata. Supported keys:
     *                         - callback: JavaScript function name executed when
     *                                     consent for the category is granted.
     *                         - description: Developer note stored with the
     *                                         blocked script information.
     */
    public function register_third_party_script($handle, $category, array $args = []) {
        if (empty($handle) || empty($category)) {
            return;
        }

        $defaults = [
            'category' => $category,
            'callback' => $args['callback'] ?? '',
            'description' => $args['description'] ?? '',
        ];

        $this->script_registry[$handle] = $defaults;
    }

    /**
     * Static proxy that allows other plugins to register scripts even before the
     * public instance is fully initialised by WordPress.
     *
     * Example usage for Elementor widgets:
     *
     * ```php
     * add_action( 'elementor/frontend/before_enqueue_scripts', function() {
     *     \CCK_Public::register_script_category( 'google-maps', 'preferences', [
     *         'callback' => 'initGoogleMapsWidget',
     *     ] );
     * } );
     * ```
     *
     * @param string $handle   Script handle passed to wp_enqueue_script().
     * @param string $category Consent category slug.
     * @param array  $args     Optional metadata forwarded to
     *                         register_third_party_script().
     */
    public static function register_script_category($handle, $category, array $args = []) {
        if (self::$instance instanceof self) {
            self::$instance->register_third_party_script($handle, $category, $args);
            return;
        }

        add_action('plugins_loaded', function () use ($handle, $category, $args) {
            if (self::$instance instanceof self) {
                self::$instance->register_third_party_script($handle, $category, $args);
            }
        });
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
        
        $privacy_link_text = __('política de privacidad', 'cookie-consent-king');
        $privacy_link = !empty($privacy_url) ? "<a href='" . esc_url($privacy_url) . "' target='_blank' rel='noopener noreferrer'>$privacy_link_text</a>" : '';
        $message_processed = str_replace('{privacy_policy_link}', $privacy_link, $message);

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
        ];

        wp_localize_script('cck-banner', 'cckData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cck_log_consent_nonce'),
            'icon_url' => esc_url($options['icon_url'] ?? ''),
            'reopen_icon_url' => esc_url($options['reopen_icon_url'] ?? ''),
            'consentState' => $this->get_user_consent_state(),
            'blockedScripts' => [],
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

    /**
     * Filters enqueued script tags to prevent execution until consent is granted.
     *
     * @param string $tag    The full script tag generated by WordPress.
     * @param string $handle The registered script handle.
     * @param string $src    Script source URL.
     *
     * @return string
     */
    public function filter_script_loader_tag($tag, $handle, $src) {
        $registration = $this->script_registry[$handle] ?? [];
        $category = $registration['category'] ?? apply_filters('cck_script_category', '', $handle, $src, $tag);

        if (empty($category) || $this->user_has_consent($category)) {
            return $tag;
        }

        $context = array_merge($registration, [
            'handle' => $handle,
            'src' => $src,
        ]);

        $blocked_tag = $this->block_script_tag($tag, $category, $context);
        $this->remember_blocked_script($category, $context);

        return $blocked_tag;
    }

    /**
     * Starts an output buffer to catch inline scripts emitted directly by page builders.
     */
    public function start_output_buffer() {
        if ($this->buffer_active || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        $this->buffer_active = ob_start([$this, 'process_buffer_output']);
    }

    /**
     * Flushes the output buffer created for inline scripts.
     */
    public function end_output_buffer() {
        if ($this->buffer_active) {
            ob_end_flush();
            $this->buffer_active = false;
        }
    }

    /**
     * Processes buffered HTML and marks inline scripts requiring consent.
     *
     * Page builders that output raw HTML (Elementor, Divi, Gutenberg blocks,
     * etc.) can opt into the consent workflow by adding the
     * `data-cck-consent="{category}"` attribute to their script tags. The
     * buffer converts those tags into placeholders until the visitor grants the
     * required consent.
     *
     * @param string $html Buffered HTML output.
     *
     * @return string
     */
    public function process_buffer_output($html) {
        if (false === stripos($html, '<script')) {
            return $html;
        }

        return preg_replace_callback('/<script\b([^>]*)>(.*?)<\/script>/is', function ($matches) {
            $attribute_string = $matches[1];
            $content = $matches[2];

            $attributes = $this->parse_attributes_from_string($attribute_string);

            if (empty($attributes['data-cck-consent']) || !empty($attributes['data-cck-blocked'])) {
                return $matches[0];
            }

            $category = $attributes['data-cck-consent'];

            if ($this->user_has_consent($category)) {
                return $matches[0];
            }

            $context = [
                'handle' => $attributes['id'] ?? null,
                'callback' => $attributes['data-cck-callback'] ?? '',
                'type' => 'inline',
            ];

            $script_tag = '<script' . $attribute_string . '>' . $content . '</script>';
            $blocked_tag = $this->block_script_tag($script_tag, $category, $context);

            $this->remember_blocked_script($category, $context);

            return $blocked_tag;
        }, $html);
    }

    /**
     * Prints the blocked script registry so JavaScript can attempt re-execution
     * once consent is granted.
     */
    public function print_blocked_scripts_store() {
        $blocked = wp_json_encode(array_values($this->blocked_scripts));

        printf(
            '<script id="cck-blocked-scripts-store">window.cckData = window.cckData || {}; window.cckData.blockedScripts = %s;</script>',
            $blocked ? $blocked : '[]'
        );
    }

    /**
     * Converts a script tag into a consent-aware placeholder.
     *
     * @param string $tag      Original script tag.
     * @param string $category Consent category slug.
     * @param array  $context  Additional metadata such as handle or callback.
     *
     * @return string
     */
    protected function block_script_tag($tag, $category, array $context = []) {
        if (!preg_match('/<script\b([^>]*)>(.*?)<\/script>/is', $tag, $matches)) {
            return $tag;
        }

        $attribute_string = $matches[1];
        $content = $matches[2];

        $attributes = $this->parse_attributes_from_string($attribute_string);
        $original_attributes = $attributes;

        if (isset($attributes['src']) && !empty($attributes['src'])) {
            $attributes['data-cck-src'] = $attributes['src'];
            unset($attributes['src']);
        }

        if (isset($attributes['type'])) {
            $context['original_type'] = $attributes['type'];
        }

        $attributes['type'] = 'text/plain';
        $attributes['data-cck-blocked'] = '1';
        $attributes['data-cck-consent'] = $category;

        if (!empty($context['handle'])) {
            $attributes['data-cck-handle'] = $context['handle'];
        }

        if (!empty($context['callback'])) {
            $attributes['data-cck-callback'] = $context['callback'];
        }

        if (!empty($context['original_type'])) {
            $attributes['data-cck-orig-type'] = $context['original_type'];
        }

        if (!empty($original_attributes)) {
            $attributes['data-cck-orig-attrs'] = wp_json_encode($original_attributes);
        }

        $attribute_html = $this->render_attributes($attributes);

        return '<script' . $attribute_html . '>' . $content . '</script>';
    }

    /**
     * Stores blocked script metadata for later restoration.
     *
     * @param string $category Consent category slug.
     * @param array  $context  Additional metadata captured while blocking.
     */
    protected function remember_blocked_script($category, array $context = []) {
        $this->blocked_scripts[] = [
            'category' => $category,
            'handle' => $context['handle'] ?? '',
            'callback' => $context['callback'] ?? '',
            'description' => $context['description'] ?? '',
            'type' => empty($context['src']) ? 'inline' : 'external',
        ];
    }

    /**
     * Parses an HTML attribute string into an associative array.
     *
     * @param string $attribute_string Raw attribute string.
     *
     * @return array<string, string>
     */
    protected function parse_attributes_from_string($attribute_string) {
        $parsed = [];
        $hair = wp_kses_hair($attribute_string, wp_allowed_protocols());

        foreach ($hair as $info) {
            $parsed[$info['name']] = $info['value'];
        }

        return $parsed;
    }

    /**
     * Renders an associative array of attributes into HTML.
     *
     * @param array<string, string> $attributes Attribute map.
     *
     * @return string
     */
    protected function render_attributes(array $attributes) {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ('' === $value) {
                $html .= ' ' . esc_attr($name);
                continue;
            }

            $html .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
        }

        return $html;
    }

    /**
     * Returns the decoded consent state from the cookie.
     *
     * @return array<string, bool>
     */
    protected function get_user_consent_state() {
        if (!empty($this->consent_cache)) {
            return $this->consent_cache;
        }

        $defaults = [
            'necessary' => true,
            'preferences' => false,
            'analytics' => false,
            'marketing' => false,
        ];

        $cookie = $_COOKIE['cck_consent'] ?? '';

        if (empty($cookie)) {
            $this->consent_cache = $defaults;
            return $this->consent_cache;
        }

        $decoded = json_decode(stripslashes($cookie), true);

        if (!is_array($decoded)) {
            $this->consent_cache = $defaults;
            return $this->consent_cache;
        }

        $this->consent_cache = array_merge($defaults, array_map('boolval', $decoded));

        return $this->consent_cache;
    }

    /**
     * Checks whether consent for a category has already been granted.
     *
     * @param string $category Consent category slug.
     *
     * @return bool
     */
    protected function user_has_consent($category) {
        if ('necessary' === $category) {
            return true;
        }

        $state = $this->get_user_consent_state();

        return !empty($state[$category]);
    }
}