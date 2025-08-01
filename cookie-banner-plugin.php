<?php
/**
 * Plugin Name: Cookie Banner GDPR
 * Description: Banner de cookies compatible con GDPR y Google Consent Mode v2 con soporte multiidioma
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: cookie-banner
 * Domain Path: /languages
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('COOKIE_BANNER_VERSION', '1.0.0');
define('COOKIE_BANNER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COOKIE_BANNER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Clase principal del plugin
 */
class CookieBannerPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'inject_early_script'), 1); // Prioridad alta para cargar antes
        add_action('wp_footer', array($this, 'render_banner'), 99); // Volver a footer pero con alta prioridad
        add_action('wp_footer', array($this, 'add_manual_trigger'));
        
        // Admin
        if (is_admin()) {
            require_once COOKIE_BANNER_PLUGIN_DIR . 'admin/class-admin.php';
            new CookieBannerAdmin();
        }
        
        // Shortcode para mostrar el banner manualmente
        add_shortcode('cookie_banner', array($this, 'shortcode_banner'));
        
        // Hooks de activación/desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Cargar traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain('cookie-banner', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'cookie-banner-style',
            COOKIE_BANNER_PLUGIN_URL . 'assets/css/cookie-banner.css',
            array(),
            COOKIE_BANNER_VERSION
        );
        
        wp_enqueue_script(
            'cookie-banner-script',
            COOKIE_BANNER_PLUGIN_URL . 'assets/js/cookie-banner.js',
            array(),
            COOKIE_BANNER_VERSION,
            true
        );
        
        // Pasar configuraciones al JavaScript
        $settings = get_option('cookie_banner_settings', array());
        wp_localize_script('cookie-banner-script', 'cookieBannerConfig', array(
            'cookiePolicyUrl' => isset($settings['cookie_policy_url']) ? $settings['cookie_policy_url'] : '',
            'aboutCookiesUrl' => isset($settings['about_cookies_url']) ? $settings['about_cookies_url'] : '',
            'gtmId' => isset($settings['gtm_id']) ? $settings['gtm_id'] : '',
            'translations' => array(
                'title' => __('Gestión de Cookies', 'cookie-banner'),
                'description' => __('Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico.', 'cookie-banner'),
                'customize' => __('Personalizar', 'cookie-banner'),
                'acceptAll' => __('Aceptar todas', 'cookie-banner'),
                'rejectAll' => __('Rechazar todas', 'cookie-banner'),
                'settings' => __('Configuración de Cookies', 'cookie-banner'),
                'consentTab' => __('Consentimiento', 'cookie-banner'),
                'detailsTab' => __('Detalles', 'cookie-banner'),
                'aboutTab' => __('Acerca de las cookies', 'cookie-banner'),
                'necessary' => __('Necesario', 'cookie-banner'),
                'preferences' => __('Preferencias', 'cookie-banner'),
                'statistics' => __('Estadística', 'cookie-banner'),
                'marketing' => __('Marketing', 'cookie-banner'),
                'allowSelection' => __('Permitir selección', 'cookie-banner'),
                'cookiePolicy' => __('Política de Cookies', 'cookie-banner'),
                'aboutCookies' => __('Acerca de las Cookies', 'cookie-banner'),
                'consentDescription' => __('Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web para hacerlo más intuitivo y para mostrarte publicidad personalizada.', 'cookie-banner'),
                'consentInstructions' => __('Puedes aceptar todas las cookies pulsando el botón "Aceptar", rechazar todas las cookies pulsando sobre el botón "Rechazar" o configurarlas su uso pulsando el botón "Configuración de cookies".', 'cookie-banner'),
                'policyLink' => __('Si deseas más información pulsa en', 'cookie-banner'),
                'necessaryDesc' => __('Las cookies necesarias ayudan a hacer una página web utilizable activando funciones básicas como la navegación en la página y el acceso a áreas seguras de la página web.', 'cookie-banner'),
                'preferencesDesc' => __('Las cookies de preferencias permiten a la página web recordar información que cambia la forma en que la página se comporta o el aspecto que tiene.', 'cookie-banner'),
                'statisticsDesc' => __('Las cookies estadísticas ayudan a los propietarios de páginas web a comprender cómo interactúan los visitantes con las páginas web.', 'cookie-banner'),
                'marketingDesc' => __('Las cookies de marketing se utilizan para rastrear a los visitantes en las páginas web para mostrar anuncios relevantes.', 'cookie-banner'),
                'aboutDescription' => __('Las cookies son pequeños archivos de texto que se almacenan en el dispositivo del usuario cuando visita un sitio web.', 'cookie-banner'),
                'cookieTypes' => __('Tipos de cookies que utilizamos:', 'cookie-banner'),
                'gdprCompliance' => __('En cumplimiento del Reglamento General de Protección de Datos (RGPD), solicitamos su consentimiento para el uso de cookies no esenciales.', 'cookie-banner'),
                'moreInfo' => __('Para más información sobre nuestra política de privacidad y el tratamiento de datos personales, consulte nuestra política de privacidad completa.', 'cookie-banner'),
                'detailedInfo' => __('Para información detallada sobre cookies, visite', 'cookie-banner')
            )
        ));
    }
    
    /**
     * Inyectar script temprano en el head para bloquear cookies
     */
    public function inject_early_script() {
        ?>
        <script>
        // Script de bloqueo temprano - ejecuta antes que cualquier otro script
        (function() {
            // Verificar si ya tenemos consentimiento
            const savedConsent = localStorage.getItem('cookieConsent');
            let hasAnalyticsConsent = false;
            let hasMarketingConsent = false;
            
            if (savedConsent) {
                try {
                    const consent = JSON.parse(savedConsent);
                    hasAnalyticsConsent = consent.analytics === true;
                    hasMarketingConsent = consent.marketing === true;
                } catch (e) {
                    console.log('Error parsing consent:', e);
                }
            }
            
            // Si no hay consentimiento, bloquear scripts
            if (!hasAnalyticsConsent && !hasMarketingConsent) {
                console.log('CookieBanner: Bloqueando scripts de terceros...');
                
                // Lista de dominios a bloquear
                const blockedDomains = [
                    'googletagmanager.com',
                    'google-analytics.com',
                    'facebook.net',
                    'doubleclick.net',
                    'googlesyndication.com',
                    'youtube.com',
                    'twitter.com',
                    'linkedin.com',
                    'instagram.com',
                    'tiktok.com',
                    'pinterest.com',
                    'hotjar.com',
                    'clarity.ms',
                    'mixpanel.com',
                    'intercom.io',
                    'zendesk.com',
                    'drift.com',
                    'hubspot.com'
                ];
                
                // Interceptar createElement
                const originalCreateElement = document.createElement;
                document.createElement = function(tagName) {
                    const element = originalCreateElement.call(document, tagName);
                    
                    if (tagName.toLowerCase() === 'script') {
                        const originalSetAttribute = element.setAttribute;
                        element.setAttribute = function(name, value) {
                            if (name === 'src') {
                                const shouldBlock = blockedDomains.some(domain => value.includes(domain));
                                if (shouldBlock) {
                                    console.log('CookieBanner: Bloqueando script:', value);
                                    element.setAttribute('data-blocked-src', value);
                                    element.setAttribute('data-cookie-consent', 'required');
                                    return;
                                }
                            }
                            originalSetAttribute.call(this, name, value);
                        };
                    }
                    
                    return element;
                };
                
                // Inyectar contenedor del banner inmediatamente
                document.addEventListener('DOMContentLoaded', function() {
                    if (!document.getElementById('cookie-banner-container')) {
                        const container = document.createElement('div');
                        container.id = 'cookie-banner-container';
                        document.body.appendChild(container);
                    }
                });
            }
        })();
        </script>
        
        <!-- Inyectar CSS del banner temprano -->
        <link rel="stylesheet" href="<?php echo COOKIE_BANNER_PLUGIN_URL; ?>assets/css/cookie-banner.css" />
        <?php
    }
    
    /**
     * Renderizar el banner (estrategia robusta para Elementor)
     */
    public function render_banner() {
        ?>
        <!-- Contenedor principal del banner -->
        <div id="cookie-banner-container"></div>
        
        <!-- Script de inicialización ultra robusta para Elementor -->
        <script>
        console.log('CookieBanner: Script de inicialización cargado');
        
        (function() {
            let initAttempts = 0;
            const maxAttempts = 20;
            
            function forceInitCookieBanner() {
                initAttempts++;
                console.log('CookieBanner: Intento de inicialización #' + initAttempts);
                
                if (window.cookieBannerInitialized) {
                    console.log('CookieBanner: Ya inicializado');
                    return;
                }
                
                // Verificar si el contenedor existe
                let container = document.getElementById('cookie-banner-container');
                if (!container) {
                    console.log('CookieBanner: Creando contenedor...');
                    container = document.createElement('div');
                    container.id = 'cookie-banner-container';
                    container.style.cssText = 'position:fixed;top:0;left:0;width:100%;z-index:999999;pointer-events:none;';
                    
                    // Intentar múltiples ubicaciones
                    if (document.body) {
                        document.body.appendChild(container);
                        console.log('CookieBanner: Contenedor agregado al body');
                    } else if (document.documentElement) {
                        document.documentElement.appendChild(container);
                        console.log('CookieBanner: Contenedor agregado al documentElement');
                    }
                }
                
                // Verificar si tenemos consentimiento
                const savedConsent = localStorage.getItem('cookieConsent');
                console.log('CookieBanner: Consentimiento guardado:', savedConsent);
                
                if (!savedConsent) {
                    console.log('CookieBanner: No hay consentimiento, intentando mostrar banner...');
                    
                    // Verificar si la clase CookieBanner está disponible
                    if (typeof CookieBanner !== 'undefined') {
                        console.log('CookieBanner: Clase disponible, creando instancia...');
                        
                        if (!window.cookieBannerInstance) {
                            try {
                                window.cookieBannerInstance = new CookieBanner();
                                window.cookieBannerInstance.showBanner = true;
                                window.cookieBannerInstance.render();
                                window.cookieBannerInitialized = true;
                                console.log('CookieBanner: Banner inicializado exitosamente');
                                return;
                            } catch (error) {
                                console.error('CookieBanner: Error al crear instancia:', error);
                            }
                        }
                    } else {
                        console.log('CookieBanner: Clase no disponible aún, reintentando...');
                        if (initAttempts < maxAttempts) {
                            setTimeout(forceInitCookieBanner, 500);
                        }
                    }
                } else {
                    console.log('CookieBanner: Consentimiento ya existe, no mostrando banner');
                    window.cookieBannerInitialized = true;
                }
            }
            
            // Múltiples estrategias de inicialización
            console.log('CookieBanner: Estado del documento:', document.readyState);
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', forceInitCookieBanner);
            } else {
                forceInitCookieBanner();
            }
            
            // Intentos adicionales para Elementor
            window.addEventListener('load', forceInitCookieBanner);
            document.addEventListener('elementor/frontend/init', forceInitCookieBanner);
            
            // Timeouts escalonados
            setTimeout(forceInitCookieBanner, 100);
            setTimeout(forceInitCookieBanner, 500);
            setTimeout(forceInitCookieBanner, 1000);
            setTimeout(forceInitCookieBanner, 2000);
            setTimeout(forceInitCookieBanner, 5000);
            
            // Observer para detectar cambios en el DOM (para Elementor)
            if (window.MutationObserver) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            setTimeout(forceInitCookieBanner, 100);
                        }
                    });
                });
                
                observer.observe(document.documentElement, {
                    childList: true,
                    subtree: true
                });
                
                setTimeout(() => observer.disconnect(), 10000); // Desconectar después de 10s
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Shortcode para mostrar el banner manualmente
     */
    public function shortcode_banner($atts) {
        $atts = shortcode_atts(array(
            'force' => 'false',
        ), $atts);
        
        ob_start();
        ?>
        <div id="cookie-banner-container-shortcode"></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof CookieBanner !== 'undefined') {
                    <?php if ($atts['force'] === 'true'): ?>
                    // Forzar mostrar el banner independientemente del consentimiento guardado
                    localStorage.removeItem('cookieConsent');
                    <?php endif; ?>
                    
                    // Crear una nueva instancia del banner
                    const shortcodeBanner = new CookieBanner();
                    shortcodeBanner.showBanner = true;
                    const originalContainer = shortcodeBanner.constructor.prototype.render;
                    shortcodeBanner.render = function() {
                        const container = document.getElementById('cookie-banner-container-shortcode');
                        if (!container) return;
                        
                        if (!this.showBanner) {
                            container.innerHTML = '';
                            return;
                        }
                        
                        container.innerHTML = `
                            <div class="cookie-banner-overlay">
                                <div class="cookie-banner-card">
                                    <div class="cookie-banner-content">
                                        ${this.showSettings ? this.renderSettings() : this.renderMain()}
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        this.attachEventListeners();
                    };
                    shortcodeBanner.render();
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Función para mostrar banner manualmente via JavaScript
     */
    public function add_manual_trigger() {
        ?>
        <script>
            function showCookieBanner() {
                if (typeof cookieBanner !== 'undefined') {
                    cookieBanner.showBanner = true;
                    cookieBanner.render();
                }
            }
            
            function resetCookieConsent() {
                localStorage.removeItem('cookieConsent');
                localStorage.removeItem('cookieConsentDate');
                if (typeof cookieBanner !== 'undefined') {
                    cookieBanner.showBanner = true;
                    cookieBanner.render();
                }
            }
        </script>
        <?php
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        // Configuraciones por defecto
        $default_settings = array(
            'cookie_policy_url' => '',
            'about_cookies_url' => '',
            'gtm_id' => ''
        );
        
        if (!get_option('cookie_banner_settings')) {
            add_option('cookie_banner_settings', $default_settings);
        }
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar si es necesario
    }
}

// Inicializar el plugin
new CookieBannerPlugin();