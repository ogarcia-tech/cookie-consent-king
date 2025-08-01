<?php
/**
 * Validador de datos del panel de administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class CookieBannerAdminValidator {
    
    /**
     * Validar configuraciones generales
     */
    public static function validate_general_settings($input) {
        $sanitized = array();
        $errors = array();
        
        // Validar URL de política de cookies
        if (isset($input['cookie_policy_url'])) {
            $url = trim($input['cookie_policy_url']);
            if (!empty($url)) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $sanitized['cookie_policy_url'] = esc_url_raw($url);
                } else {
                    $errors[] = __('La URL de Política de Cookies no es válida.', 'cookie-banner');
                }
            } else {
                $sanitized['cookie_policy_url'] = '';
            }
        }
        
        // Validar URL acerca de cookies
        if (isset($input['about_cookies_url'])) {
            $url = trim($input['about_cookies_url']);
            if (!empty($url)) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $sanitized['about_cookies_url'] = esc_url_raw($url);
                } else {
                    $errors[] = __('La URL de Acerca de las Cookies no es válida.', 'cookie-banner');
                }
            } else {
                $sanitized['about_cookies_url'] = '';
            }
        }
        
        // Validar GTM ID
        if (isset($input['gtm_id'])) {
            $gtm_id = trim($input['gtm_id']);
            if (!empty($gtm_id)) {
                if (preg_match('/^GTM-[A-Z0-9]+$/', $gtm_id)) {
                    $sanitized['gtm_id'] = sanitize_text_field($gtm_id);
                } else {
                    $errors[] = __('El ID de Google Tag Manager debe tener el formato GTM-XXXXXXX.', 'cookie-banner');
                }
            } else {
                $sanitized['gtm_id'] = '';
            }
        }
        
        // Validar posición del banner
        if (isset($input['banner_position'])) {
            $allowed_positions = array('bottom', 'top', 'modal');
            $position = sanitize_text_field($input['banner_position']);
            if (in_array($position, $allowed_positions)) {
                $sanitized['banner_position'] = $position;
            } else {
                $sanitized['banner_position'] = 'modal';
                $errors[] = __('Posición del banner no válida. Se ha establecido por defecto "Modal Centrado".', 'cookie-banner');
            }
        }
        
        // Validar expiration time (en días)
        if (isset($input['consent_expiration'])) {
            $expiration = intval($input['consent_expiration']);
            if ($expiration >= 1 && $expiration <= 365) {
                $sanitized['consent_expiration'] = $expiration;
            } else {
                $sanitized['consent_expiration'] = 365;
                $errors[] = __('El tiempo de expiración debe estar entre 1 y 365 días. Se ha establecido a 365 días.', 'cookie-banner');
            }
        }
        
        // Mostrar errores si los hay
        if (!empty($errors)) {
            foreach ($errors as $error) {
                add_settings_error('cookie_banner_settings', 'validation_error', $error, 'error');
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validar configuraciones de apariencia
     */
    public static function validate_appearance_settings($input) {
        $sanitized = array();
        $errors = array();
        
        // Validar color primario
        if (isset($input['primary_color'])) {
            $color = sanitize_hex_color($input['primary_color']);
            if ($color) {
                $sanitized['primary_color'] = $color;
            } else {
                $sanitized['primary_color'] = '#3b82f6';
                $errors[] = __('Color primario no válido. Se ha establecido el color por defecto.', 'cookie-banner');
            }
        }
        
        // Validar color secundario
        if (isset($input['secondary_color'])) {
            $color = sanitize_hex_color($input['secondary_color']);
            if ($color) {
                $sanitized['secondary_color'] = $color;
            } else {
                $sanitized['secondary_color'] = '#6b7280';
                $errors[] = __('Color secundario no válido. Se ha establecido el color por defecto.', 'cookie-banner');
            }
        }
        
        // Validar border radius
        if (isset($input['border_radius'])) {
            $allowed_radius = array('none', 'small', 'medium', 'large');
            $radius = sanitize_text_field($input['border_radius']);
            if (in_array($radius, $allowed_radius)) {
                $sanitized['border_radius'] = $radius;
            } else {
                $sanitized['border_radius'] = 'medium';
                $errors[] = __('Radio de borde no válido. Se ha establecido "Mediano".', 'cookie-banner');
            }
        }
        
        // Validar custom CSS
        if (isset($input['custom_css'])) {
            $css = trim($input['custom_css']);
            if (!empty($css)) {
                // Validación básica de CSS - remover tags script
                $css = wp_strip_all_tags($css, true);
                $sanitized['custom_css'] = $css;
            } else {
                $sanitized['custom_css'] = '';
            }
        }
        
        // Mostrar errores si los hay
        if (!empty($errors)) {
            foreach ($errors as $error) {
                add_settings_error('cookie_banner_appearance', 'validation_error', $error, 'error');
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validar textos personalizados
     */
    public static function validate_text_settings($input) {
        $sanitized = array();
        
        // Lista de campos de texto permitidos
        $text_fields = array(
            'title',
            'description',
            'accept_all_text',
            'reject_all_text',
            'customize_text',
            'necessary_title',
            'necessary_description',
            'preferences_title',
            'preferences_description',
            'analytics_title',
            'analytics_description',
            'marketing_title',
            'marketing_description'
        );
        
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $text = trim($input[$field]);
                if (!empty($text)) {
                    // Permitir HTML básico pero sanitizar
                    $allowed_html = array(
                        'a' => array(
                            'href' => array(),
                            'target' => array(),
                            'rel' => array()
                        ),
                        'strong' => array(),
                        'em' => array(),
                        'br' => array(),
                        'p' => array()
                    );
                    $sanitized[$field] = wp_kses($text, $allowed_html);
                } else {
                    $sanitized[$field] = '';
                }
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validar configuraciones avanzadas
     */
    public static function validate_advanced_settings($input) {
        $sanitized = array();
        $errors = array();
        
        // Validar dominios bloqueados (lista separada por comas)
        if (isset($input['blocked_domains'])) {
            $domains = trim($input['blocked_domains']);
            if (!empty($domains)) {
                $domain_list = array_map('trim', explode(',', $domains));
                $valid_domains = array();
                
                foreach ($domain_list as $domain) {
                    if (!empty($domain)) {
                        // Validación básica de dominio
                        if (preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
                            $valid_domains[] = sanitize_text_field($domain);
                        } else {
                            $errors[] = sprintf(__('Dominio no válido: %s', 'cookie-banner'), $domain);
                        }
                    }
                }
                
                $sanitized['blocked_domains'] = implode(', ', $valid_domains);
            } else {
                $sanitized['blocked_domains'] = '';
            }
        }
        
        // Validar scripts personalizados
        if (isset($input['custom_scripts'])) {
            $scripts = trim($input['custom_scripts']);
            if (!empty($scripts)) {
                // Validación de seguridad para scripts - solo permitir ciertas funciones
                if (strpos($scripts, 'eval(') !== false || strpos($scripts, 'document.write') !== false) {
                    $errors[] = __('Scripts personalizados contienen código no permitido.', 'cookie-banner');
                    $sanitized['custom_scripts'] = '';
                } else {
                    $sanitized['custom_scripts'] = $scripts;
                }
            } else {
                $sanitized['custom_scripts'] = '';
            }
        }
        
        // Validar configuraciones booleanas
        $boolean_fields = array('enable_analytics', 'enable_marketing', 'enable_preferences', 'auto_block_scripts');
        foreach ($boolean_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = (bool) $input[$field];
            } else {
                $sanitized[$field] = false;
            }
        }
        
        // Mostrar errores si los hay
        if (!empty($errors)) {
            foreach ($errors as $error) {
                add_settings_error('cookie_banner_advanced', 'validation_error', $error, 'error');
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Verificar permisos de usuario
     */
    public static function check_user_permissions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'cookie-banner'));
        }
    }
    
    /**
     * Verificar nonce de seguridad
     */
    public static function verify_nonce($action = 'cookie_banner_admin') {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', $action)) {
            wp_die(__('Verificación de seguridad fallida. Por favor, recarga la página e inténtalo de nuevo.', 'cookie-banner'));
        }
    }
    
    /**
     * Validar datos JSON (para configuraciones complejas)
     */
    public static function validate_json($json_string, $max_depth = 10) {
        if (empty($json_string)) {
            return array();
        }
        
        $data = json_decode($json_string, true, $max_depth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Limpiar y validar arrays de configuración
     */
    public static function sanitize_config_array($array, $allowed_keys = array()) {
        if (!is_array($array)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($array as $key => $value) {
            $clean_key = sanitize_key($key);
            
            if (!empty($allowed_keys) && !in_array($clean_key, $allowed_keys)) {
                continue;
            }
            
            if (is_string($value)) {
                $sanitized[$clean_key] = sanitize_text_field($value);
            } elseif (is_int($value) || is_float($value)) {
                $sanitized[$clean_key] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$clean_key] = (bool) $value;
            } elseif (is_array($value)) {
                $sanitized[$clean_key] = self::sanitize_config_array($value, $allowed_keys);
            }
        }
        
        return $sanitized;
    }
}