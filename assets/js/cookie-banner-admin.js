/**
 * JavaScript para el panel de administración del Cookie Banner
 */

(function($) {
    'use strict';

    const CookieBannerAdmin = {
        
        init: function() {
            this.bindEvents();
            this.loadStats();
            this.initColorPickers();
            this.initPreview();
        },
        
        bindEvents: function() {
            $('#reset-settings').on('click', this.resetSettings.bind(this));
            $('#export-settings').on('click', this.exportSettings.bind(this));
            $('#import-settings').on('click', this.importSettings.bind(this));
            $('#import-file').on('change', this.handleFileSelect.bind(this));
            
            // Actualizar vista previa en tiempo real
            $('input[name*="cookie_banner_appearance"], select[name*="cookie_banner_appearance"]').on('change', this.updatePreview.bind(this));
            $('input[name*="cookie_banner_texts"]').on('input', this.updatePreview.bind(this));
        },
        
        resetSettings: function(e) {
            e.preventDefault();
            
            if (!confirm(cookieBannerAdmin.strings?.confirmReset || '¿Estás seguro de que quieres resetear toda la configuración?')) {
                return;
            }
            
            const $button = $(e.target);
            this.setButtonLoading($button, true);
            
            $.post(cookieBannerAdmin.ajax_url, {
                action: 'cookie_banner_reset_settings',
                nonce: cookieBannerAdmin.nonce
            })
            .done((response) => {
                if (response.success) {
                    this.showNotice('success', response.data);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.showNotice('error', response.data || 'Error al resetear la configuración');
                }
            })
            .fail(() => {
                this.showNotice('error', 'Error de conexión');
            })
            .always(() => {
                this.setButtonLoading($button, false);
            });
        },
        
        exportSettings: function(e) {
            e.preventDefault();
            
            const settings = {
                cookie_banner_settings: this.getFormData('cookie_banner_settings'),
                cookie_banner_appearance: this.getFormData('cookie_banner_appearance'),
                cookie_banner_texts: this.getFormData('cookie_banner_texts'),
                exported_at: new Date().toISOString(),
                version: '1.0.0'
            };
            
            const dataStr = JSON.stringify(settings, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            
            const link = document.createElement('a');
            link.href = url;
            link.download = 'cookie-banner-settings-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            this.showNotice('success', 'Configuración exportada correctamente');
        },
        
        importSettings: function(e) {
            e.preventDefault();
            
            const fileInput = $('#import-file')[0];
            if (!fileInput.files.length) {
                this.showNotice('error', 'Por favor selecciona un archivo');
                return;
            }
            
            const file = fileInput.files[0];
            const reader = new FileReader();
            
            reader.onload = (event) => {
                try {
                    const settings = JSON.parse(event.target.result);
                    this.applyImportedSettings(settings);
                    this.showNotice('success', 'Configuración importada correctamente');
                } catch (error) {
                    this.showNotice('error', 'Error al procesar el archivo: ' + error.message);
                }
            };
            
            reader.readAsText(file);
        },
        
        handleFileSelect: function(e) {
            const file = e.target.files[0];
            if (file && file.type !== 'application/json') {
                this.showNotice('error', 'Por favor selecciona un archivo JSON válido');
                e.target.value = '';
            }
        },
        
        loadStats: function() {
            if ($('.cookie-stats-grid').length === 0) return;
            
            $.post(cookieBannerAdmin.ajax_url, {
                action: 'cookie_banner_get_stats',
                nonce: cookieBannerAdmin.nonce
            })
            .done((response) => {
                if (response.success) {
                    this.updateStatsDisplay(response.data);
                }
            });
        },
        
        updateStatsDisplay: function(stats) {
            $('.stat-card').each(function() {
                const $card = $(this);
                const type = $card.find('h4').text().toLowerCase();
                
                if (type.includes('aceptados')) {
                    $card.find('.stat-value, div[style*="font-size: 2em"]').text(stats.accepted);
                } else if (type.includes('rechazados')) {
                    $card.find('.stat-value, div[style*="font-size: 2em"]').text(stats.rejected);
                } else if (type.includes('total')) {
                    $card.find('.stat-value, div[style*="font-size: 2em"]').text(stats.total);
                }
            });
            
            // Actualizar gráfico de porcentaje
            if (stats.total > 0) {
                const percentage = (stats.accepted / stats.total * 100).toFixed(1);
                $('.progress-fill, div[style*="background: #22c55e"]').css('width', percentage + '%');
                $('.stats-chart p').text(percentage + '% de los usuarios aceptaron las cookies');
            }
        },
        
        initColorPickers: function() {
            $('input[type="color"]').on('change', this.updatePreview.bind(this));
        },
        
        initPreview: function() {
            this.updatePreview();
        },
        
        updatePreview: function() {
            if ($('.cookie-banner-preview').length === 0) return;
            
            const primaryColor = $('input[name="cookie_banner_appearance[primary_color]"]').val() || '#3b82f6';
            const secondaryColor = $('input[name="cookie_banner_appearance[secondary_color]"]').val() || '#6b7280';
            const title = $('input[name="cookie_banner_texts[title]"]').val() || '🍪 Usamos cookies';
            const description = $('textarea[name="cookie_banner_texts[description]"]').val() || 'Este sitio utiliza cookies para mejorar la experiencia del usuario.';
            const acceptButton = $('input[name="cookie_banner_texts[accept_button]"]').val() || 'Aceptar todas';
            const settingsButton = $('input[name="cookie_banner_texts[settings_button]"]').val() || 'Configurar';
            
            const $preview = $('.cookie-banner-card');
            $preview.find('h4').text(title);
            $preview.find('p').text(description);
            
            const $buttons = $preview.find('button');
            $buttons.eq(0).text(acceptButton).css('background-color', primaryColor);
            $buttons.eq(1).text(settingsButton).css({
                'color': secondaryColor,
                'border-color': secondaryColor
            });
        },
        
        getFormData: function(prefix) {
            const data = {};
            $(`input[name^="${prefix}"], select[name^="${prefix}"], textarea[name^="${prefix}"]`).each(function() {
                const name = $(this).attr('name');
                const key = name.replace(prefix + '[', '').replace(']', '');
                data[key] = $(this).val();
            });
            return data;
        },
        
        applyImportedSettings: function(settings) {
            // Aplicar configuraciones generales
            if (settings.cookie_banner_settings) {
                this.applyFormData('cookie_banner_settings', settings.cookie_banner_settings);
            }
            
            // Aplicar configuraciones de apariencia
            if (settings.cookie_banner_appearance) {
                this.applyFormData('cookie_banner_appearance', settings.cookie_banner_appearance);
            }
            
            // Aplicar configuraciones de textos
            if (settings.cookie_banner_texts) {
                this.applyFormData('cookie_banner_texts', settings.cookie_banner_texts);
            }
            
            this.updatePreview();
        },
        
        applyFormData: function(prefix, data) {
            Object.keys(data).forEach(key => {
                const $field = $(`input[name="${prefix}[${key}]"], select[name="${prefix}[${key}]"], textarea[name="${prefix}[${key}]"]`);
                if ($field.length) {
                    $field.val(data[key]);
                }
            });
        },
        
        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
            } else {
                $button.removeClass('loading').prop('disabled', false);
            }
        },
        
        showNotice: function(type, message) {
            // Crear o actualizar el elemento de notificación
            let $notice = $('.cookie-banner-notice');
            if ($notice.length === 0) {
                $notice = $('<div class="cookie-banner-notice"></div>');
                $('.tab-content').prepend($notice);
            }
            
            $notice
                .removeClass('success error info')
                .addClass(type)
                .text(message)
                .show();
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                $notice.fadeOut();
            }, 5000);
        }
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        CookieBannerAdmin.init();
    });
    
})(jQuery);