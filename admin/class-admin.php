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
        register_setting('cookie_banner_settings_group', 'cookie_banner_settings');
        
        add_settings_section(
            'cookie_banner_main_section',
            __('Configuración Principal', 'cookie-banner'),
            null,
            'cookie-banner-settings'
        );
        
        add_settings_field(
            'cookie_policy_url',
            __('URL Política de Cookies', 'cookie-banner'),
            array($this, 'cookie_policy_url_callback'),
            'cookie-banner-settings',
            'cookie_banner_main_section'
        );
        
        add_settings_field(
            'about_cookies_url',
            __('URL Acerca de las Cookies', 'cookie-banner'),
            array($this, 'about_cookies_url_callback'),
            'cookie-banner-settings',
            'cookie_banner_main_section'
        );
        
        add_settings_field(
            'gtm_id',
            __('Google Tag Manager ID', 'cookie-banner'),
            array($this, 'gtm_id_callback'),
            'cookie-banner-settings',
            'cookie_banner_main_section'
        );
    }
    
    /**
     * Página de administración
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración Cookie Banner GDPR', 'cookie-banner'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cookie_banner_settings_group');
                do_settings_sections('cookie-banner-settings');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="margin-top: 20px; padding: 20px;">
                <h2><?php _e('Información del Plugin', 'cookie-banner'); ?></h2>
                <p><?php _e('Este plugin proporciona un banner de cookies compatible con GDPR y Google Consent Mode v2.', 'cookie-banner'); ?></p>
                
                <h3><?php _e('Características:', 'cookie-banner'); ?></h3>
                <ul>
                    <li><?php _e('✓ Compatible con GDPR', 'cookie-banner'); ?></li>
                    <li><?php _e('✓ Google Consent Mode v2', 'cookie-banner'); ?></li>
                    <li><?php _e('✓ Soporte multiidioma', 'cookie-banner'); ?></li>
                    <li><?php _e('✓ Enlaces personalizables', 'cookie-banner'); ?></li>
                    <li><?php _e('✓ Configuración granular de cookies', 'cookie-banner'); ?></li>
                </ul>
                
                <h3><?php _e('Idiomas soportados:', 'cookie-banner'); ?></h3>
                <p><?php _e('Español, Inglés, Francés, Italiano, Alemán', 'cookie-banner'); ?></p>
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
        <input type="url" name="cookie_banner_settings[cookie_policy_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
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
        <input type="url" name="cookie_banner_settings[about_cookies_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('URL con información detallada sobre cookies. Aparecerá en la pestaña "Acerca de las cookies".', 'cookie-banner'); ?></p>
        <?php
    }
    
    /**
     * Callback para GTM ID
     */
    public function gtm_id_callback() {
        $settings = get_option('cookie_banner_settings');
        $value = isset($settings['gtm_id']) ? $settings['gtm_id'] : '';
        ?>
        <input type="text" name="cookie_banner_settings[gtm_id]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="GTM-XXXXXXX" />
        <p class="description"><?php _e('Tu ID de Google Tag Manager para integración con Consent Mode v2.', 'cookie-banner'); ?></p>
        <?php
    }
}