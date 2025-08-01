<?php
/**
 * Panel de administración del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class CookieBannerAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_cookie_banner_get_stats', array($this, 'get_cookie_stats'));
        add_action('wp_ajax_cookie_banner_reset_settings', array($this, 'reset_settings'));
    }
    
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_options_page(
            __('Cookie Banner Settings', 'cookie-banner'),
            __('Cookie Banner', 'cookie-banner'),
            'manage_options',
            'cookie-banner-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('cookie_banner_settings_group', 'cookie_banner_settings', array($this, 'sanitize_settings'));
        register_setting('cookie_banner_appearance_group', 'cookie_banner_appearance');
        register_setting('cookie_banner_texts_group', 'cookie_banner_texts');
    }
    
    /**
     * Cargar scripts y estilos del admin
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_cookie-banner-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_script('cookie-banner-admin', COOKIE_BANNER_PLUGIN_URL . 'assets/js/cookie-banner-admin.js', array('jquery'), COOKIE_BANNER_VERSION, true);
        wp_enqueue_style('cookie-banner-admin', COOKIE_BANNER_PLUGIN_URL . 'assets/css/cookie-banner-admin.css', array(), COOKIE_BANNER_VERSION);
        
        // Cargar React para el dashboard si estamos en esa pestaña
        if (isset($_GET['tab']) && $_GET['tab'] === 'dashboard') {
            wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.development.js', array(), '18', true);
            wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.development.js', array('react'), '18', true);
        }
        
        wp_localize_script('cookie-banner-admin', 'cookieBannerAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cookie_banner_admin')
        ));
    }
    
    /**
     * Sanitizar configuraciones
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['cookie_policy_url'])) {
            $sanitized['cookie_policy_url'] = esc_url_raw($input['cookie_policy_url']);
        }
        
        if (isset($input['about_cookies_url'])) {
            $sanitized['about_cookies_url'] = esc_url_raw($input['about_cookies_url']);
        }
        
        if (isset($input['gtm_id'])) {
            $sanitized['gtm_id'] = sanitize_text_field($input['gtm_id']);
        }
        
        if (isset($input['banner_position'])) {
            $sanitized['banner_position'] = sanitize_text_field($input['banner_position']);
        }
        
        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        
        if (isset($input['secondary_color'])) {
            $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']);
        }
        
        return $sanitized;
    }
    
    /**
     * Página de administración
     */
    public function admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        $settings = get_option('cookie_banner_settings', array());
        $appearance = get_option('cookie_banner_appearance', array());
        $texts = get_option('cookie_banner_texts', array());
        ?>
        <div class="wrap cookie-banner-admin">
            <h1><?php _e('Configuración Cookie Banner GDPR', 'cookie-banner'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="?page=cookie-banner-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Configuración General', 'cookie-banner'); ?>
                </a>
                <a href="?page=cookie-banner-settings&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php _e('Apariencia', 'cookie-banner'); ?>
                </a>
                <a href="?page=cookie-banner-settings&tab=texts" class="nav-tab <?php echo $active_tab == 'texts' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Textos', 'cookie-banner'); ?>
                </a>
                <a href="?page=cookie-banner-settings&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php _e('Dashboard', 'cookie-banner'); ?>
                </a>
                <a href="?page=cookie-banner-settings&tab=stats" class="nav-tab <?php echo $active_tab == 'stats' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('Estadísticas', 'cookie-banner'); ?>
                </a>
                <a href="?page=cookie-banner-settings&tab=tools" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Herramientas', 'cookie-banner'); ?>
                </a>
            </div>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'general':
                        $this->render_general_tab($settings);
                        break;
                    case 'appearance':
                        $this->render_appearance_tab($appearance);
                        break;
                    case 'texts':
                        $this->render_texts_tab($texts);
                        break;
                    case 'dashboard':
                        $this->render_dashboard_tab();
                        break;
                    case 'stats':
                        $this->render_stats_tab();
                        break;
                    case 'tools':
                        $this->render_tools_tab();
                        break;
                    default:
                        $this->render_general_tab($settings);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Callback para URL de política de cookies
     */
    public function cookie_policy_url_callback() {
        $settings = get_option('cookie_banner_settings');
        $value = isset($settings['cookie_policy_url']) ? $settings['cookie_policy_url'] : '';
        ?>
        <input type="url" id="cookie_policy_url" name="cookie_banner_settings[cookie_policy_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('URL a tu página de Política de Cookies. Aparecerá en la pestaña "Consentimiento".', 'cookie-banner'); ?></p>
        <?php
    }
    
    /**
     * Callback para URL de acerca de cookies
     */
    public function about_cookies_url_callback() {
        $settings = get_option('cookie_banner_settings');
        $value = isset($settings['about_cookies_url']) ? $settings['about_cookies_url'] : '';
        ?>
        <input type="url" id="about_cookies_url" name="cookie_banner_settings[about_cookies_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('URL con información detallada sobre cookies. Aparecerá en la pestaña "Acerca de las cookies".', 'cookie-banner'); ?></p>
        <?php
    }
    
    /**
     * Renderizar pestaña general
     */
    public function render_general_tab($settings) {
        ?>
        <div class="tab-pane">
            <form method="post" action="options.php">
                <?php settings_fields('cookie_banner_settings_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('URL Política de Cookies', 'cookie-banner'); ?></th>
                        <td>
                            <input type="url" name="cookie_banner_settings[cookie_policy_url]" 
                                   value="<?php echo esc_attr($settings['cookie_policy_url'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('URL a tu página de Política de Cookies.', 'cookie-banner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('URL Acerca de las Cookies', 'cookie-banner'); ?></th>
                        <td>
                            <input type="url" name="cookie_banner_settings[about_cookies_url]" 
                                   value="<?php echo esc_attr($settings['about_cookies_url'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('URL con información detallada sobre cookies.', 'cookie-banner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Google Tag Manager ID', 'cookie-banner'); ?></th>
                        <td>
                            <input type="text" name="cookie_banner_settings[gtm_id]" 
                                   value="<?php echo esc_attr($settings['gtm_id'] ?? ''); ?>" 
                                   class="regular-text" placeholder="GTM-XXXXXXX" />
                            <p class="description"><?php _e('Tu ID de Google Tag Manager para integración con Consent Mode v2.', 'cookie-banner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Posición del Banner', 'cookie-banner'); ?></th>
                        <td>
                            <select name="cookie_banner_settings[banner_position]" class="regular-text">
                                <option value="bottom" <?php selected($settings['banner_position'] ?? 'bottom', 'bottom'); ?>><?php _e('Inferior', 'cookie-banner'); ?></option>
                                <option value="top" <?php selected($settings['banner_position'] ?? 'bottom', 'top'); ?>><?php _e('Superior', 'cookie-banner'); ?></option>
                                <option value="modal" <?php selected($settings['banner_position'] ?? 'bottom', 'modal'); ?>><?php _e('Modal Centrado', 'cookie-banner'); ?></option>
                            </select>
                            <p class="description"><?php _e('Selecciona dónde aparecerá el banner de cookies.', 'cookie-banner'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderizar pestaña de apariencia
     */
    public function render_appearance_tab($appearance) {
        ?>
        <div class="tab-pane">
            <form method="post" action="options.php">
                <?php settings_fields('cookie_banner_appearance_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Color Primario', 'cookie-banner'); ?></th>
                        <td>
                            <input type="color" name="cookie_banner_appearance[primary_color]" 
                                   value="<?php echo esc_attr($appearance['primary_color'] ?? '#3b82f6'); ?>" />
                            <p class="description"><?php _e('Color de los botones principales y elementos destacados.', 'cookie-banner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Color Secundario', 'cookie-banner'); ?></th>
                        <td>
                            <input type="color" name="cookie_banner_appearance[secondary_color]" 
                                   value="<?php echo esc_attr($appearance['secondary_color'] ?? '#6b7280'); ?>" />
                            <p class="description"><?php _e('Color de los botones secundarios y bordes.', 'cookie-banner'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Esquina Redondeada', 'cookie-banner'); ?></th>
                        <td>
                            <select name="cookie_banner_appearance[border_radius]">
                                <option value="none" <?php selected($appearance['border_radius'] ?? 'medium', 'none'); ?>><?php _e('Sin redondeo', 'cookie-banner'); ?></option>
                                <option value="small" <?php selected($appearance['border_radius'] ?? 'medium', 'small'); ?>><?php _e('Pequeño', 'cookie-banner'); ?></option>
                                <option value="medium" <?php selected($appearance['border_radius'] ?? 'medium', 'medium'); ?>><?php _e('Mediano', 'cookie-banner'); ?></option>
                                <option value="large" <?php selected($appearance['border_radius'] ?? 'medium', 'large'); ?>><?php _e('Grande', 'cookie-banner'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="cookie-banner-preview">
                <h3><?php _e('Vista Previa', 'cookie-banner'); ?></h3>
                <div class="preview-container" style="border: 1px solid #ddd; padding: 20px; background: #f9f9f9; margin: 20px 0;">
                    <div class="cookie-banner-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h4><?php _e('🍪 Usamos cookies', 'cookie-banner'); ?></h4>
                        <p><?php _e('Este sitio utiliza cookies para mejorar la experiencia del usuario.', 'cookie-banner'); ?></p>
                        <div style="margin-top: 15px;">
                            <button style="background: <?php echo esc_attr($appearance['primary_color'] ?? '#3b82f6'); ?>; color: white; border: none; padding: 8px 16px; border-radius: 4px; margin-right: 10px;">
                                <?php _e('Aceptar todas', 'cookie-banner'); ?>
                            </button>
                            <button style="background: transparent; color: <?php echo esc_attr($appearance['secondary_color'] ?? '#6b7280'); ?>; border: 1px solid <?php echo esc_attr($appearance['secondary_color'] ?? '#6b7280'); ?>; padding: 8px 16px; border-radius: 4px;">
                                <?php _e('Configurar', 'cookie-banner'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderizar pestaña de textos
     */
    public function render_texts_tab($texts) {
        ?>
        <div class="tab-pane">
            <form method="post" action="options.php">
                <?php settings_fields('cookie_banner_texts_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Título del Banner', 'cookie-banner'); ?></th>
                        <td>
                            <input type="text" name="cookie_banner_texts[title]" 
                                   value="<?php echo esc_attr($texts['title'] ?? __('🍪 Usamos cookies', 'cookie-banner')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Descripción', 'cookie-banner'); ?></th>
                        <td>
                            <textarea name="cookie_banner_texts[description]" rows="3" class="large-text"><?php echo esc_textarea($texts['description'] ?? __('Este sitio utiliza cookies para mejorar la experiencia del usuario y proporcionar funcionalidades adicionales.', 'cookie-banner')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Texto Botón Aceptar', 'cookie-banner'); ?></th>
                        <td>
                            <input type="text" name="cookie_banner_texts[accept_button]" 
                                   value="<?php echo esc_attr($texts['accept_button'] ?? __('Aceptar todas', 'cookie-banner')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Texto Botón Configurar', 'cookie-banner'); ?></th>
                        <td>
                            <input type="text" name="cookie_banner_texts[settings_button]" 
                                   value="<?php echo esc_attr($texts['settings_button'] ?? __('Configurar', 'cookie-banner')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Texto Botón Rechazar', 'cookie-banner'); ?></th>
                        <td>
                            <input type="text" name="cookie_banner_texts[reject_button]" 
                                   value="<?php echo esc_attr($texts['reject_button'] ?? __('Rechazar', 'cookie-banner')); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderizar pestaña de estadísticas
     */
    public function render_stats_tab() {
        $stats = $this->get_cookie_statistics();
        ?>
        <div class="tab-pane">
            <h3><?php _e('Estadísticas de Consentimiento', 'cookie-banner'); ?></h3>
            
            <div class="cookie-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h4 style="color: #22c55e; margin: 0 0 10px 0;"><?php _e('Consentimientos Aceptados', 'cookie-banner'); ?></h4>
                    <div style="font-size: 2em; font-weight: bold;"><?php echo esc_html($stats['accepted']); ?></div>
                </div>
                
                <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h4 style="color: #ef4444; margin: 0 0 10px 0;"><?php _e('Consentimientos Rechazados', 'cookie-banner'); ?></h4>
                    <div style="font-size: 2em; font-weight: bold;"><?php echo esc_html($stats['rejected']); ?></div>
                </div>
                
                <div class="stat-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h4 style="color: #3b82f6; margin: 0 0 10px 0;"><?php _e('Total de Decisiones', 'cookie-banner'); ?></h4>
                    <div style="font-size: 2em; font-weight: bold;"><?php echo esc_html($stats['total']); ?></div>
                </div>
            </div>
            
            <?php if ($stats['total'] > 0): ?>
            <div class="stats-chart" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
                <h4><?php _e('Tasa de Aceptación', 'cookie-banner'); ?></h4>
                <div style="width: 100%; height: 20px; background: #f3f4f6; border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo ($stats['accepted'] / $stats['total'] * 100); ?>%; height: 100%; background: #22c55e;"></div>
                </div>
                <p><?php printf(__('%s%% de los usuarios aceptaron las cookies', 'cookie-banner'), round($stats['accepted'] / $stats['total'] * 100, 1)); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderizar pestaña de herramientas
     */
    public function render_tools_tab() {
        ?>
        <div class="tab-pane">
            <h3><?php _e('Herramientas de Administración', 'cookie-banner'); ?></h3>
            
            <div class="tools-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="tool-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h4><?php _e('Resetear Configuración', 'cookie-banner'); ?></h4>
                    <p><?php _e('Restaura todas las configuraciones a sus valores por defecto.', 'cookie-banner'); ?></p>
                    <button type="button" class="button button-secondary" id="reset-settings">
                        <?php _e('Resetear Configuración', 'cookie-banner'); ?>
                    </button>
                </div>
                
                <div class="tool-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h4><?php _e('Exportar Configuración', 'cookie-banner'); ?></h4>
                    <p><?php _e('Descarga un archivo con tu configuración actual.', 'cookie-banner'); ?></p>
                    <button type="button" class="button button-secondary" id="export-settings">
                        <?php _e('Exportar', 'cookie-banner'); ?>
                    </button>
                </div>
                
                <div class="tool-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                    <h4><?php _e('Importar Configuración', 'cookie-banner'); ?></h4>
                    <p><?php _e('Sube un archivo de configuración previamente exportado.', 'cookie-banner'); ?></p>
                    <input type="file" id="import-file" accept=".json" style="margin-bottom: 10px;" />
                    <button type="button" class="button button-secondary" id="import-settings">
                        <?php _e('Importar', 'cookie-banner'); ?>
                    </button>
                </div>
            </div>
            
            <div class="debug-info" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin: 20px 0;">
                <h4><?php _e('Información de Debug', 'cookie-banner'); ?></h4>
                <pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;"><?php
                echo "Plugin Version: " . COOKIE_BANNER_VERSION . "\n";
                echo "WordPress Version: " . get_bloginfo('version') . "\n";
                echo "PHP Version: " . PHP_VERSION . "\n";
                echo "Active Theme: " . wp_get_theme()->get('Name') . "\n";
                echo "Settings: " . wp_json_encode(get_option('cookie_banner_settings', array()), JSON_PRETTY_PRINT);
                ?></pre>
            </div>
        </div>
        <?php
    }
    
    /**
     * Obtener estadísticas de cookies
     */
    private function get_cookie_statistics() {
        $stats = get_option('cookie_banner_stats', array(
            'accepted' => 0,
            'rejected' => 0,
            'total' => 0
        ));
        
        return $stats;
    }
    
    /**
     * AJAX: Obtener estadísticas
     */
    public function get_cookie_stats() {
        check_ajax_referer('cookie_banner_admin', 'nonce');
        
        $stats = $this->get_cookie_statistics();
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Resetear configuración
     */
    public function reset_settings() {
        check_ajax_referer('cookie_banner_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción.', 'cookie-banner'));
        }
        
        delete_option('cookie_banner_settings');
        delete_option('cookie_banner_appearance');
        delete_option('cookie_banner_texts');
        delete_option('cookie_banner_stats');
        
        wp_send_json_success(__('Configuración reseteada correctamente.', 'cookie-banner'));
    }
    
    /**
     * Renderizar pestaña del dashboard de pruebas
     */
    public function render_dashboard_tab() {
        ?>
        <div class="tab-pane">
            <div class="cookie-banner-dashboard">
                <h3><?php _e('Dashboard - Cookie Banner', 'cookie-banner'); ?></h3>
                <p><?php _e('Utiliza esta interfaz para probar el banner de cookies en tiempo real y verificar el estado del consentimiento.', 'cookie-banner'); ?></p>
                
                <!-- Contenedor para la aplicación React -->
                <div id="cookie-banner-react-dashboard" style="margin-top: 30px; border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #fff;">
                    <div class="loading-placeholder" style="text-align: center; padding: 40px;">
                        <p><?php _e('Cargando dashboard de pruebas...', 'cookie-banner'); ?></p>
                        <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                    </div>
                </div>
                
                <!-- Scripts para renderizar el dashboard React -->
                <script type="text/babel">
                const { useState, useEffect } = React;

                // Hook para gestionar el consentimiento
                const useConsentMode = () => {
                    const [consent, setConsent] = useState(null);
                    const [isLoaded, setIsLoaded] = useState(false);

                    useEffect(() => {
                        const loadConsent = () => {
                            const savedConsent = localStorage.getItem('cookieConsent');
                            if (savedConsent) {
                                try {
                                    const parsedConsent = JSON.parse(savedConsent);
                                    setConsent(parsedConsent);
                                } catch (error) {
                                    console.error('Error parsing saved consent:', error);
                                }
                            }
                            setIsLoaded(true);
                        };

                        loadConsent();

                        const handleStorageChange = (e) => {
                            if (e.key === 'cookieConsent') {
                                loadConsent();
                            }
                        };

                        const handleConsentUpdate = () => {
                            loadConsent();
                        };

                        window.addEventListener('storage', handleStorageChange);
                        window.addEventListener('consentUpdated', handleConsentUpdate);

                        return () => {
                            window.removeEventListener('storage', handleStorageChange);
                            window.removeEventListener('consentUpdated', handleConsentUpdate);
                        };
                    }, []);

                    const resetConsent = () => {
                        localStorage.removeItem('cookieConsent');
                        localStorage.removeItem('cookieConsentDate');
                        setConsent(null);
                    };

                    const isConsentGiven = () => consent !== null;

                    const getConsentDate = () => {
                        const dateString = localStorage.getItem('cookieConsentDate');
                        return dateString ? new Date(dateString) : null;
                    };

                    return {
                        consent,
                        isLoaded,
                        resetConsent,
                        isConsentGiven,
                        getConsentDate,
                    };
                };

                // Componente principal del dashboard
                const CookieBannerDashboard = () => {
                    const { consent, isLoaded, resetConsent, isConsentGiven, getConsentDate } = useConsentMode();
                    const [showDemo, setShowDemo] = useState(false);

                    const showCookieBanner = () => {
                        if (typeof window.showCookieBanner === 'function') {
                            window.showCookieBanner();
                        } else {
                            // Fallback para crear banner si no existe la función global
                            if (typeof CookieBanner !== 'undefined') {
                                const banner = new CookieBanner();
                                banner.showBanner = true;
                                banner.render();
                            }
                        }
                    };

                    const clearAndTest = () => {
                        resetConsent();
                        setTimeout(() => {
                            showCookieBanner();
                        }, 100);
                    };

                    if (!isLoaded) {
                        return React.createElement('div', { style: { textAlign: 'center', padding: '20px' } }, 
                            React.createElement('p', null, 'Cargando estado del consentimiento...')
                        );
                    }

                    const consentDate = getConsentDate();

                    return React.createElement('div', { className: 'dashboard-content' },
                        React.createElement('div', { 
                            style: { 
                                display: 'grid', 
                                gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', 
                                gap: '20px',
                                marginBottom: '30px'
                            } 
                        },
                            // Estado del Consentimiento
                            React.createElement('div', { 
                                style: { 
                                    background: '#f8f9fa', 
                                    border: '1px solid #dee2e6', 
                                    borderRadius: '8px', 
                                    padding: '20px' 
                                } 
                            },
                                React.createElement('h4', { style: { marginTop: 0 } }, '🍪 Estado del Consentimiento'),
                                React.createElement('div', { style: { marginBottom: '15px' } },
                                    React.createElement('strong', null, 'Estado: '),
                                    React.createElement('span', { 
                                        style: { 
                                            color: isConsentGiven() ? '#28a745' : '#dc3545',
                                            fontWeight: 'bold'
                                        } 
                                    }, isConsentGiven() ? 'Consentimiento Otorgado' : 'Sin Consentimiento')
                                ),
                                consentDate && React.createElement('div', { style: { marginBottom: '15px' } },
                                    React.createElement('strong', null, 'Fecha: '),
                                    React.createElement('span', null, consentDate.toLocaleString('es-ES'))
                                ),
                                consent && React.createElement('div', null,
                                    React.createElement('h5', null, 'Tipos de Cookie:'),
                                    React.createElement('ul', { style: { listStyle: 'none', padding: 0 } },
                                        React.createElement('li', null, `✅ Necesarias: ${consent.necessary ? 'Aceptadas' : 'Rechazadas'}`),
                                        React.createElement('li', null, `📊 Analíticas: ${consent.analytics ? 'Aceptadas' : 'Rechazadas'}`),
                                        React.createElement('li', null, `🎯 Marketing: ${consent.marketing ? 'Aceptadas' : 'Rechazadas'}`),
                                        React.createElement('li', null, `⚙️ Preferencias: ${consent.preferences ? 'Aceptadas' : 'Rechazadas'}`)
                                    )
                                )
                            ),

                            // Controles de Prueba
                            React.createElement('div', { 
                                style: { 
                                    background: '#f8f9fa', 
                                    border: '1px solid #dee2e6', 
                                    borderRadius: '8px', 
                                    padding: '20px' 
                                } 
                            },
                                React.createElement('h4', { style: { marginTop: 0 } }, '🧪 Controles de Prueba'),
                                React.createElement('div', { style: { display: 'flex', flexDirection: 'column', gap: '10px' } },
                                    React.createElement('button', {
                                        onClick: resetConsent,
                                        style: {
                                            padding: '10px 15px',
                                            background: '#6c757d',
                                            color: 'white',
                                            border: 'none',
                                            borderRadius: '4px',
                                            cursor: 'pointer'
                                        }
                                    }, 'Restablecer Consentimiento'),
                                    React.createElement('button', {
                                        onClick: showCookieBanner,
                                        style: {
                                            padding: '10px 15px',
                                            background: '#007cba',
                                            color: 'white',
                                            border: 'none',
                                            borderRadius: '4px',
                                            cursor: 'pointer'
                                        }
                                    }, 'Mostrar Banner de Cookies'),
                                    React.createElement('button', {
                                        onClick: clearAndTest,
                                        style: {
                                            padding: '10px 15px',
                                            background: '#dc3545',
                                            color: 'white',
                                            border: 'none',
                                            borderRadius: '4px',
                                            cursor: 'pointer'
                                        }
                                    }, 'Limpiar y Probar')
                                )
                            )
                        ),

                        // Información adicional
                        React.createElement('div', { 
                            style: { 
                                background: '#e3f2fd', 
                                border: '1px solid #bbdefb', 
                                borderRadius: '8px', 
                                padding: '15px' 
                            } 
                        },
                            React.createElement('h4', { style: { marginTop: 0 } }, 'ℹ️ Información'),
                            React.createElement('p', { style: { margin: 0 } }, 
                                'Este dashboard te permite probar el banner de cookies en tiempo real. Los cambios en el consentimiento se reflejarán automáticamente en esta interfaz.'
                            )
                        )
                    );
                };

                // Renderizar el componente
                ReactDOM.render(React.createElement(CookieBannerDashboard), document.getElementById('cookie-banner-react-dashboard'));
                </script>

                <!-- Cargar Babel para transpilar JSX -->
                <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
            </div>
        </div>
        <?php
    }
}