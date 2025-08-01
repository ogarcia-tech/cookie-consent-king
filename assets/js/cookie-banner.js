/**
 * Cookie Banner GDPR JavaScript
 * Mantiene la misma funcionalidad que el componente React original
 */

class CookieBanner {
    constructor() {
        this.showBanner = false;
        this.showSettings = false;
        this.currentTab = 'detalles';
        this.consent = {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false
        };
        
        this.config = window.cookieBannerConfig || {};
        this.translations = this.config.translations || {};
        
        this.init();
    }
    
    init() {
        // Una sola inicialización controlada
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.safeStart());
        } else {
            this.safeStart();
        }
    }
    
    safeStart() {
        if (this.initialized) {
            console.log('CookieBanner: Ya inicializado, saltando...');
            return;
        }
        
        this.initialized = true;
        console.log('CookieBanner: Iniciando...');
        
        this.blockScripts();
        this.checkExistingConsent();
        this.initializeConsentMode();
    }
    
    blockScripts() {
        // Evitar múltiples interceptaciones
        if (window.cookieBannerScriptInterceptionActive) {
            return;
        }
        window.cookieBannerScriptInterceptionActive = true;
        
        // Bloquear scripts de terceros antes de que se ejecuten
        const scriptsToBlock = [
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
        
        // Interceptar nuevos scripts
        const self = this;
        const originalCreateElement = document.createElement;
        document.createElement = function(tagName) {
            const element = originalCreateElement.call(document, tagName);
            
            if (tagName.toLowerCase() === 'script') {
                // Solo redefinir src si no se ha hecho ya para este elemento
                if (!element.__cookieBannerSrcIntercepted) {
                    element.__cookieBannerSrcIntercepted = true;
                    
                    const originalSetSrc = Object.getOwnPropertyDescriptor(HTMLScriptElement.prototype, 'src').set;
                    
                    try {
                        Object.defineProperty(element, 'src', {
                            set: function(value) {
                                const shouldBlock = scriptsToBlock.some(domain => value.includes(domain));
                                const hasConsent = self.hasAnalyticsConsent() || self.hasMarketingConsent();
                                
                                if (shouldBlock && !hasConsent) {
                                    console.log('CookieBanner: Bloqueando script:', value);
                                    element.setAttribute('data-blocked-src', value);
                                    element.setAttribute('data-cookie-consent', 'required');
                                    return;
                                }
                                
                                originalSetSrc.call(element, value);
                            },
                            get: function() {
                                return element.getAttribute('src');
                            },
                            configurable: true
                        });
                    } catch (e) {
                        // Si ya existe la propiedad, simplemente usarla
                        console.warn('CookieBanner: No se pudo redefinir src para el script:', e.message);
                    }
                }
            }
            
            return element;
        };
        
        // Bloquear scripts existentes
        this.blockExistingScripts();
    }
    
    blockExistingScripts() {
        const scripts = document.querySelectorAll('script[src]');
        scripts.forEach(script => {
            const src = script.getAttribute('src');
            if (!src) return;
            
            const scriptsToBlock = [
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
            
            const shouldBlock = scriptsToBlock.some(domain => src.includes(domain));
            const hasConsent = this.hasAnalyticsConsent() || this.hasMarketingConsent();
            
            if (shouldBlock && !hasConsent) {
                console.log('CookieBanner: Bloqueando script existente:', src);
                script.setAttribute('data-blocked-src', src);
                script.setAttribute('data-cookie-consent', 'required');
                script.removeAttribute('src');
            }
        });
    }
    
    hasAnalyticsConsent() {
        const savedConsent = localStorage.getItem('cookieConsent');
        if (!savedConsent) return false;
        const consent = JSON.parse(savedConsent);
        return consent.analytics === true;
    }
    
    hasMarketingConsent() {
        const savedConsent = localStorage.getItem('cookieConsent');
        if (!savedConsent) return false;
        const consent = JSON.parse(savedConsent);
        return consent.marketing === true;
    }
    
    unblockScripts() {
        // Reactivar scripts bloqueados según el consentimiento
        const blockedScripts = document.querySelectorAll('script[data-blocked-src]');
        blockedScripts.forEach(script => {
            const src = script.getAttribute('data-blocked-src');
            const requiresAnalytics = src.includes('google-analytics') || src.includes('googletagmanager') || src.includes('hotjar') || src.includes('clarity');
            const requiresMarketing = src.includes('facebook') || src.includes('doubleclick') || src.includes('googlesyndication');
            
            let shouldUnblock = false;
            
            if (requiresAnalytics && this.hasAnalyticsConsent()) {
                shouldUnblock = true;
            }
            
            if (requiresMarketing && this.hasMarketingConsent()) {
                shouldUnblock = true;
            }
            
            if (shouldUnblock) {
                console.log('CookieBanner: Desbloqueando script:', src);
                script.setAttribute('src', src);
                script.removeAttribute('data-blocked-src');
                script.removeAttribute('data-cookie-consent');
                
                // Recargar el script
                const newScript = document.createElement('script');
                newScript.src = src;
                script.parentNode.replaceChild(newScript, script);
            }
        });
    }
    
    checkExistingConsent() {
        const savedConsent = localStorage.getItem('cookieConsent');
        console.log('CookieBanner: checking saved consent', savedConsent);
        
        if (!savedConsent) {
            console.log('CookieBanner: No saved consent, showing banner');
            this.showBanner = true;
            this.render();
        } else {
            console.log('CookieBanner: Found saved consent, parsing and applying');
            this.consent = JSON.parse(savedConsent);
            this.updateConsentMode(this.consent);
        }
    }
    
    initializeConsentMode() {
        if (typeof window !== 'undefined' && window.gtag) {
            window.gtag('consent', 'default', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied',
                'functionality_storage': 'denied',
                'personalization_storage': 'denied',
                'security_storage': 'granted',
                'wait_for_update': 500,
            });
        }
        
        // Inicializar GTM si está configurado
        if (this.config.gtmId) {
            this.initializeGTM();
        }
    }
    
    initializeGTM() {
        // Crear dataLayer si no existe
        window.dataLayer = window.dataLayer || [];
        
        // Función gtag
        function gtag(){dataLayer.push(arguments);}
        window.gtag = gtag;
        
        // Inicializar con fecha
        gtag('js', new Date());
        
        // Configurar GTM
        gtag('config', this.config.gtmId);
        
        // Cargar script de GTM
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${this.config.gtmId}`;
        document.head.appendChild(script);
    }
    
    getConsentAction(consentSettings) {
        const { analytics, marketing, preferences } = consentSettings;
        
        if (analytics && marketing && preferences) {
            return 'accept_all';
        } else if (!analytics && !marketing && !preferences) {
            return 'reject_all';
        } else {
            return 'custom_selection';
        }
    }
    
    pushDataLayerEvent(consentSettings, action) {
        if (typeof window !== 'undefined') {
            window.dataLayer = window.dataLayer || [];
            
            const eventData = {
                'event': 'consent_update',
                'consent': {
                    'ad_storage': consentSettings.marketing ? 'granted' : 'denied',
                    'analytics_storage': consentSettings.analytics ? 'granted' : 'denied',
                    'functionality_storage': consentSettings.preferences ? 'granted' : 'denied',
                    'personalization_storage': consentSettings.preferences ? 'granted' : 'denied',
                    'security_storage': 'granted',
                    'ad_user_data': consentSettings.marketing ? 'granted' : 'denied',
                    'ad_personalization': consentSettings.marketing ? 'granted' : 'denied'
                },
                'consent_action': action,
                'timestamp': new Date().toISOString()
            };

            console.log('Pushing to dataLayer:', eventData);
            window.dataLayer.push(eventData);
        }
    }
    
    updateConsentMode(consentSettings, action) {
        if (typeof window !== 'undefined' && window.gtag) {
            window.gtag('consent', 'update', {
                'ad_storage': consentSettings.marketing ? 'granted' : 'denied',
                'ad_user_data': consentSettings.marketing ? 'granted' : 'denied',
                'ad_personalization': consentSettings.marketing ? 'granted' : 'denied',
                'analytics_storage': consentSettings.analytics ? 'granted' : 'denied',
                'functionality_storage': consentSettings.preferences ? 'granted' : 'denied',
                'personalization_storage': consentSettings.preferences ? 'granted' : 'denied',
            });
        }

        if (action) {
            this.pushDataLayerEvent(consentSettings, action);
        }
    }
    
    saveConsent(consentSettings, action) {
        console.log('Saving consent:', consentSettings, 'Action:', action);
        
        localStorage.setItem('cookieConsent', JSON.stringify(consentSettings));
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
        
        this.updateConsentMode(consentSettings, action);
        
        this.consent = consentSettings;
        this.showBanner = false;
        this.showSettings = false;
        
        // Desbloquear scripts según el consentimiento dado
        this.unblockScripts();
        
        this.render();
        
        // Mostrar el mini banner después de dar consentimiento
        setTimeout(() => {
            this.showMiniBanner();
        }, 1000);
    }
    
    showMinimizedBanner() {
        const container = document.getElementById('cookie-banner-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="cookie-banner-minimized" id="cookie-banner-minimized">
                <div class="cookie-banner-minimized-content">
                    <div class="cookie-banner-minimized-icon">
                        ${this.createIcon('cookie')}
                    </div>
                    <span class="cookie-banner-minimized-text">${this.translations.title || 'Cookies'}</span>
                </div>
            </div>
        `;
        
        // Añadir eventos de hover y click
        const minimizedBanner = document.getElementById('cookie-banner-minimized');
        if (minimizedBanner) {
            minimizedBanner.addEventListener('mouseenter', () => {
                this.showBanner = true;
                this.render();
            });
            
            minimizedBanner.addEventListener('click', () => {
                this.showBanner = true;
                this.showSettings = true;
                this.render();
            });
        }
    }
    
    acceptAll() {
        const allAccepted = {
            necessary: true,
            analytics: true,
            marketing: true,
            preferences: true,
        };
        this.saveConsent(allAccepted, 'accept_all');
    }
    
    acceptNecessary() {
        const necessaryOnly = {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false,
        };
        this.saveConsent(necessaryOnly, 'reject_all');
    }
    
    saveCustomSettings() {
        this.saveConsent(this.consent, 'custom_selection');
    }
    
    toggleConsent(type) {
        if (type === 'necessary') return;
        this.consent[type] = !this.consent[type];
        this.render();
    }
    
    showSettingsPanel() {
        this.showSettings = true;
        this.render();
    }
    
    hideSettingsPanel() {
        this.showSettings = false;
        this.render();
    }
    
    setActiveTab(tab) {
        this.currentTab = tab;
        this.render();
    }
    
    createIcon(type) {
        const icons = {
            cookie: '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8 12c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm6 0c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2z"/>',
            settings: '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>',
            shield: '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            barChart: '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>',
            target: '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="2" stroke="currentColor" stroke-width="2" fill="none"/>',
            x: '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 6L6 18M6 6l12 12"/>'
        };
        return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none">${icons[type] || ''}</svg>`;
    }
    
    render() {
        const container = document.getElementById('cookie-banner-container');
        if (!container) return;
        
        if (!this.showBanner) {
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
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
    }
    
    attachEventListeners() {
        const container = document.getElementById('cookie-banner-container');
        if (!container) return;
        
        // Botones principales
        const acceptAllBtn = container.querySelector('.cookie-banner-accept-all');
        const rejectAllBtn = container.querySelector('.cookie-banner-reject-all');
        const customizeBtn = container.querySelector('.cookie-banner-customize');
        const closeBtn = container.querySelector('.cookie-banner-close');
        const saveBtn = container.querySelector('.cookie-banner-save');
        
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => this.acceptAll());
        }
        
        if (rejectAllBtn) {
            rejectAllBtn.addEventListener('click', () => this.acceptNecessary());
        }
        
        if (customizeBtn) {
            customizeBtn.addEventListener('click', () => this.showSettingsPanel());
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideSettingsPanel());
        }
        
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveCustomSettings());
        }
        
        // Tabs
        const tabButtons = container.querySelectorAll('.cookie-banner-tab-trigger');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const tab = e.target.getAttribute('data-tab');
                if (tab) this.setActiveTab(tab);
            });
        });
        
        // Toggles de categorías
        const toggles = container.querySelectorAll('.cookie-banner-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                const category = e.target.getAttribute('data-category');
                if (category) this.toggleConsent(category);
            });
        });
    }
    
    renderMain() {
        return `
            <div>
                <div class="cookie-banner-header">
                    <div class="cookie-banner-icon">
                        ${this.createIcon('cookie')}
                    </div>
                    <div class="cookie-banner-text">
                        <h3 class="cookie-banner-title">
                            ${this.translations.title || 'Gestión de Cookies'}
                        </h3>
                        <p class="cookie-banner-description">
                            ${this.translations.description || 'Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico.'}
                        </p>
                    </div>
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-customize">
                        ${this.createIcon('settings')}
                        ${this.translations.customize || 'Personalizar'}
                    </button>
                    
                    <div class="cookie-banner-actions-right">
                        <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-reject-all">
                            ${this.translations.rejectAll || 'Rechazar todas'}
                        </button>
                        <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-accept-all">
                            ${this.translations.acceptAll || 'Aceptar todas'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderSettings() {
        return `
            <div class="cookie-banner-settings">
                <div class="cookie-banner-settings-header">
                    <h3 class="cookie-banner-settings-title">
                        ${this.translations.settings || 'Configuración de Cookies'}
                    </h3>
                    <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-btn-icon cookie-banner-close">
                        ${this.createIcon('x')}
                    </button>
                </div>
                
                <div class="cookie-banner-tabs">
                    <div class="cookie-banner-tabs-list">
                        <button class="cookie-banner-tab-trigger ${this.currentTab === 'consentimiento' ? 'active' : ''}" data-tab="consentimiento">
                            ${this.translations.consentTab || 'Consentimiento'}
                        </button>
                        <button class="cookie-banner-tab-trigger ${this.currentTab === 'detalles' ? 'active' : ''}" data-tab="detalles">
                            ${this.translations.detailsTab || 'Detalles'}
                        </button>
                        <button class="cookie-banner-tab-trigger ${this.currentTab === 'acerca' ? 'active' : ''}" data-tab="acerca">
                            ${this.translations.aboutTab || 'Acerca de las cookies'}
                        </button>
                    </div>
                    
                    ${this.renderTabContent()}
                </div>
            </div>
        `;
    }
    
    renderTabContent() {
        switch (this.currentTab) {
            case 'consentimiento':
                return this.renderConsentTab();
            case 'detalles':
                return this.renderDetailsTab();
            case 'acerca':
                return this.renderAboutTab();
            default:
                return this.renderDetailsTab();
        }
    }
    
    renderConsentTab() {
        const policyLink = this.config.cookiePolicyUrl ? 
            ` ${this.translations.policyLink || 'Si deseas más información pulsa en'} <a href="${this.config.cookiePolicyUrl}" target="_blank" rel="noopener noreferrer" class="cookie-banner-link">${this.translations.cookiePolicy || 'Política de Cookies'}</a>.` : '';
            
        return `
            <div class="cookie-banner-tab-content">
                <div style="margin-bottom: 0.75rem;">
                    <p class="cookie-banner-description">
                        ${this.translations.consentDescription || 'Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web para hacerlo más intuitivo y para mostrarte publicidad personalizada.'}
                    </p>
                    <p class="cookie-banner-description">
                        ${this.translations.consentInstructions || 'Puedes aceptar todas las cookies pulsando el botón "Aceptar", rechazar todas las cookies pulsando sobre el botón "Rechazar" o configurarlas su uso pulsando el botón "Configuración de cookies".'}${policyLink}
                    </p>
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-reject-all">
                        ${this.translations.rejectAll || 'Rechazar todas'}
                    </button>
                    
                    <div class="cookie-banner-actions-right">
                        <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-accept-all">
                            ${this.translations.acceptAll || 'Aceptar todas'}
                        </button>
                        <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-save">
                            ${this.translations.allowSelection || 'Permitir selección'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderDetailsTab() {
        return `
            <div class="cookie-banner-tab-content">
                <div style="margin-bottom: 1rem;">
                    ${this.renderCookieCategory('necessary', 'shield', this.translations.necessary || 'Necesario', this.translations.necessaryDesc || 'Las cookies necesarias ayudan a hacer una página web utilizable activando funciones básicas como la navegación en la página y el acceso a áreas seguras de la página web.', true, true)}
                    ${this.renderCookieCategory('preferences', 'settings', this.translations.preferences || 'Preferencias', this.translations.preferencesDesc || 'Las cookies de preferencias permiten a la página web recordar información que cambia la forma en que la página se comporta o el aspecto que tiene.', this.consent.preferences, false)}
                    ${this.renderCookieCategory('analytics', 'barChart', this.translations.statistics || 'Estadística', this.translations.statisticsDesc || 'Las cookies estadísticas ayudan a los propietarios de páginas web a comprender cómo interactúan los visitantes con las páginas web.', this.consent.analytics, false)}
                    ${this.renderCookieCategory('marketing', 'target', this.translations.marketing || 'Marketing', this.translations.marketingDesc || 'Las cookies de marketing se utilizan para rastrear a los visitantes en las páginas web para mostrar anuncios relevantes.', this.consent.marketing, false)}
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-reject-all">
                        ${this.translations.rejectAll || 'Rechazar todas'}
                    </button>
                    
                    <div class="cookie-banner-actions-right">
                        <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-accept-all">
                            ${this.translations.acceptAll || 'Aceptar todas'}
                        </button>
                        <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-save">
                            ${this.translations.allowSelection || 'Permitir selección'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderAboutTab() {
        const aboutLink = this.config.aboutCookiesUrl ? 
            ` ${this.translations.detailedInfo || 'Para información detallada sobre cookies, visite'} <a href="${this.config.aboutCookiesUrl}" target="_blank" rel="noopener noreferrer" class="cookie-banner-link">${this.translations.aboutCookies || 'Acerca de las Cookies'}</a>.` : '';
            
        return `
            <div class="cookie-banner-tab-content">
                <div style="margin-bottom: 1rem; font-size: 0.875rem; color: #6b7280;">
                    <p style="margin-bottom: 1rem;">
                        ${this.translations.aboutDescription || 'Las cookies son pequeños archivos de texto que se almacenan en el dispositivo del usuario cuando visita un sitio web.'} 
                        Estas cookies contienen información sobre la navegación del usuario y se utilizan para mejorar la funcionalidad del sitio web, 
                        personalizar la experiencia del usuario y proporcionar información analítica a los propietarios del sitio.
                    </p>
                    <p style="margin-bottom: 0.5rem;"><strong>${this.translations.cookieTypes || 'Tipos de cookies que utilizamos:'}</strong></p>
                    <ul style="margin-left: 1.25rem; margin-bottom: 1rem;">
                        <li style="margin-bottom: 0.25rem;"><strong>Cookies técnicas o necesarias:</strong> Son esenciales para el funcionamiento básico del sitio web y no se pueden desactivar.</li>
                        <li style="margin-bottom: 0.25rem;"><strong>Cookies de preferencias:</strong> Permiten recordar las configuraciones y preferencias del usuario para mejorar su experiencia.</li>
                        <li style="margin-bottom: 0.25rem;"><strong>Cookies estadísticas:</strong> Recopilan información de forma anónima sobre cómo los usuarios interactúan con el sitio web para mejorar su rendimiento.</li>
                        <li style="margin-bottom: 0.25rem;"><strong>Cookies de marketing:</strong> Se utilizan para mostrar publicidad relevante y medir la efectividad de las campañas publicitarias.</li>
                    </ul>
                    <p style="margin-bottom: 1rem;">
                        ${this.translations.gdprCompliance || 'En cumplimiento del Reglamento General de Protección de Datos (RGPD), solicitamos su consentimiento para el uso de cookies no esenciales.'} 
                        Puede gestionar sus preferencias de cookies en cualquier momento accediendo a la configuración de privacidad de nuestro sitio web.
                    </p>
                    <p>
                        ${this.translations.moreInfo || 'Para más información sobre nuestra política de privacidad y el tratamiento de datos personales, consulte nuestra política de privacidad completa.'}${aboutLink}
                    </p>
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-reject-all">
                        ${this.translations.rejectAll || 'Rechazar'}
                    </button>
                    
                    <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-accept-all">
                        ${this.translations.acceptAll || 'Aceptar'}
                    </button>
                </div>
            </div>
        `;
    }
    
    renderCookieCategory(type, icon, title, description, checked, disabled) {
        return `
            <div class="cookie-category ${disabled ? 'disabled' : ''}">
                <div class="cookie-category-info">
                    <div class="cookie-category-icon">
                        ${this.createIcon(icon)}
                    </div>
                    <div class="cookie-category-text">
                        <h4>${title}</h4>
                        ${disabled ? '<span class="cookie-banner-badge">Obligatorias</span>' : ''}
                        <p>${description}</p>
                    </div>
                </div>
                <button class="cookie-switch ${checked ? 'checked' : ''} cookie-banner-toggle" ${disabled ? 'disabled' : ''} data-category="${type}">
                    <div class="cookie-switch-thumb"></div>
                </button>
            </div>
    }
    
    createMiniBanner() {
        if (this.miniBanner) return;
        
        this.miniBanner = document.createElement('div');
        this.miniBanner.className = 'cookie-banner-minimized';
        this.miniBanner.innerHTML = `
            <div class="cookie-banner-minimized-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>
                    <path d="M8.5 8.5v.01"/>
                    <path d="M16 15.5v.01"/>
                    <path d="M12 12v.01"/>
                    <path d="M11 17v.01"/>
                    <path d="M7 14v.01"/>
                </svg>
            </div>
        `;
        this.miniBanner.title = 'Gestionar cookies';
        this.miniBanner.setAttribute('aria-label', 'Abrir configuración de cookies');
        this.miniBanner.addEventListener('click', () => this.reopenBanner());
        
        document.body.appendChild(this.miniBanner);
    }
    
    showMiniBanner() {
        if (!this.miniBanner) {
            this.createMiniBanner();
        }
        this.miniBanner.style.display = 'flex';
    }
    
    hideMiniBanner() {
        if (this.miniBanner) {
            this.miniBanner.style.display = 'none';
        }
    }
    
    reopenBanner() {
        this.showBanner = true;
        this.hideMiniBanner();
        this.render();
    }
}

// Inicialización robusta para compatibilidad con todos los builders
// Variables globales para evitar múltiples inicializaciones
let cookieBanner = null;
let initializationAttempted = false;

function initializeCookieBanner() {
    // Protección absoluta contra múltiples inicializaciones
    if (initializationAttempted) {
        console.log('CookieBanner: Inicialización ya intentada, saltando...');
        return;
    }
    
    initializationAttempted = true;
    console.log('CookieBanner: Inicializando una sola vez...');
    
    cookieBanner = new CookieBanner();
}

// Una sola inicialización controlada
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCookieBanner, { once: true });
} else {
    initializeCookieBanner();
}

// Funciones globales para control manual
window.showCookieBanner = function() {
    if (cookieBanner) {
        localStorage.removeItem('cookieConsent');
        cookieBanner.showBanner = true;
        cookieBanner.render();
    } else {
        setTimeout(() => window.showCookieBanner(), 100);
    }
};

window.resetCookieConsent = function() {
    localStorage.removeItem('cookieConsent');
    if (cookieBanner) {
        cookieBanner.showBanner = true;
        cookieBanner.render();
    }
};