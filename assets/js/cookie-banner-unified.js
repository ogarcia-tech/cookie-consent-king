/**
 * Cookie Banner Unified - Implementación limpia y eficiente
 * Sin duplicaciones, sin bucles, sin complejidad innecesaria
 */

class CookieBannerUnified {
    constructor(config = {}) {
        this.config = {
            cookiePolicyUrl: '',
            aboutCookiesUrl: '',
            gtmId: '',
            position: 'modal',
            primaryColor: '#3b82f6',
            secondaryColor: '#6b7280',
            translations: {},
            ...config
        };
        
        this.consent = {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false
        };
        
        this.showBanner = false;
        this.showSettings = false;
        this.currentTab = 'detalles';
        this.container = null;
        this.miniBanner = null;
        this.isInitialized = false;
        
        this.init();
    }
    
    init() {
        if (this.isInitialized) return;
        this.isInitialized = true;
        
        this.initializeConsentMode();
        this.checkExistingConsent();
        this.createContainer();
        this.bindEvents();
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
    }
    
    checkExistingConsent() {
        const savedConsent = localStorage.getItem('cookieConsent');
        if (savedConsent) {
            try {
                this.consent = JSON.parse(savedConsent);
                this.updateConsentMode(this.consent);
                this.showMiniBanner();
            } catch (error) {
                console.error('Error parsing consent:', error);
                this.showBanner = true;
            }
        } else {
            this.showBanner = true;
        }
        
        this.render();
    }
    
    createContainer() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'cookie-banner-unified-container';
            this.container.className = 'cookie-banner-container';
            document.body.appendChild(this.container);
        }
    }
    
    bindEvents() {
        // Evento para mostrar banner manualmente
        window.addEventListener('showCookieBanner', () => {
            this.reopenBanner();
        });
        
        // Evento para reset desde admin
        window.addEventListener('resetCookieConsent', () => {
            this.resetConsent();
        });
    }
    
    render() {
        if (!this.container) return;
        
        if (!this.showBanner) {
            this.container.innerHTML = '';
            return;
        }
        
        const content = this.showSettings ? this.renderSettings() : this.renderMain();
        
        this.container.innerHTML = `
            <div class="cookie-banner-overlay">
                <div class="cookie-banner-card ${this.config.position}">
                    <div class="cookie-banner-content">
                        ${content}
                    </div>
                </div>
            </div>
        `;
        
        this.attachEventListeners();
    }
    
    renderMain() {
        const { translations } = this.config;
        
        return `
            <div class="cookie-banner-main">
                <div class="cookie-banner-header">
                    <div class="cookie-banner-icon">
                        ${this.createSVGIcon('cookie')}
                    </div>
                    <div class="cookie-banner-text">
                        <h3>${translations.title || 'Gestión de Cookies'}</h3>
                        <p>${translations.description || 'Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico.'}</p>
                    </div>
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-customize" data-action="customize">
                        ${this.createSVGIcon('settings')}
                        ${translations.customize || 'Personalizar'}
                    </button>
                    
                    <div class="cookie-banner-actions-right">
                        <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-reject-all" data-action="reject">
                            ${translations.rejectAll || 'Rechazar todas'}
                        </button>
                        ${this.isAllAccepted() ? '' : `
                            <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-accept-all" data-action="accept">
                                ${translations.acceptAll || 'Aceptar todas'}
                            </button>
                        `}
                    </div>
                </div>
            </div>
        `;
    }
    
    renderSettings() {
        const { translations } = this.config;
        
        return `
            <div class="cookie-banner-settings">
                <div class="cookie-banner-settings-header">
                    <h3>${translations.settings || 'Configuración de Cookies'}</h3>
                    <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-close" data-action="close">
                        ${this.createSVGIcon('close')}
                    </button>
                </div>
                
                <div class="cookie-banner-tabs">
                    <div class="cookie-banner-tabs-list">
                        <button class="cookie-banner-tab ${this.currentTab === 'consentimiento' ? 'active' : ''}" data-tab="consentimiento">
                            ${translations.consentTab || 'Consentimiento'}
                        </button>
                        <button class="cookie-banner-tab ${this.currentTab === 'detalles' ? 'active' : ''}" data-tab="detalles">
                            ${translations.detailsTab || 'Detalles'}
                        </button>
                        <button class="cookie-banner-tab ${this.currentTab === 'acerca' ? 'active' : ''}" data-tab="acerca">
                            ${translations.aboutTab || 'Acerca de las cookies'}
                        </button>
                    </div>
                    
                    <div class="cookie-banner-tabs-content">
                        ${this.renderTabContent()}
                    </div>
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
        const { translations, cookiePolicyUrl } = this.config;
        const policyLink = cookiePolicyUrl ? 
            `<a href="${cookiePolicyUrl}" target="_blank" class="cookie-banner-link">${translations.cookiePolicy || 'Política de Cookies'}</a>` : '';
        
        return `
            <div class="cookie-banner-tab-content">
                <div class="cookie-banner-consent-content">
                    <p>${translations.consentDescription || 'Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web.'}</p>
                    <p>${translations.consentInstructions || 'Puedes aceptar todas las cookies pulsando el botón "Aceptar" o configurar su uso pulsando el botón "Configuración de cookies".'} ${policyLink ? `${translations.policyLink || 'Si deseas más información pulsa en'} ${policyLink}.` : ''}</p>
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-reject-all" data-action="reject">
                        ${translations.rejectAll || 'Rechazar todas'}
                    </button>
                    
                    <div class="cookie-banner-actions-right">
                        ${this.isAllAccepted() ? '' : `
                            <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-accept-all" data-action="accept">
                                ${translations.acceptAll || 'Aceptar todas'}
                            </button>
                        `}
                        <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-save-selection" data-action="save">
                            ${translations.allowSelection || 'Permitir selección'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderDetailsTab() {
        const { translations } = this.config;
        
        return `
            <div class="cookie-banner-tab-content">
                <div class="cookie-banner-categories">
                    ${this.renderCookieCategory('necessary', 'shield', translations.necessary || 'Necesario', translations.necessaryDesc || 'Las cookies necesarias ayudan a hacer una página web utilizable.', true, true)}
                    ${this.renderCookieCategory('preferences', 'settings', translations.preferences || 'Preferencias', translations.preferencesDesc || 'Las cookies de preferencias permiten recordar información que cambia la forma en que la página se comporta.', this.consent.preferences, false)}
                    ${this.renderCookieCategory('analytics', 'bar-chart', translations.statistics || 'Estadística', translations.statisticsDesc || 'Las cookies estadísticas ayudan a comprender cómo interactúan los visitantes.', this.consent.analytics, false)}
                    ${this.renderCookieCategory('marketing', 'target', translations.marketing || 'Marketing', translations.marketingDesc || 'Las cookies de marketing se utilizan para rastrear visitantes y mostrar anuncios relevantes.', this.consent.marketing, false)}
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-reject-all" data-action="reject">
                        ${translations.rejectAll || 'Rechazar todas'}
                    </button>
                    
                    <div class="cookie-banner-actions-right">
                        ${this.isAllAccepted() ? '' : `
                            <button class="cookie-banner-btn cookie-banner-btn-ghost cookie-banner-accept-all" data-action="accept">
                                ${translations.acceptAll || 'Aceptar todas'}
                            </button>
                        `}
                        <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-save-selection" data-action="save">
                            ${translations.allowSelection || 'Permitir selección'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    renderAboutTab() {
        const { translations, aboutCookiesUrl } = this.config;
        const aboutLink = aboutCookiesUrl ? 
            `<a href="${aboutCookiesUrl}" target="_blank" class="cookie-banner-link">${translations.aboutCookies || 'Acerca de las Cookies'}</a>` : '';
        
        return `
            <div class="cookie-banner-tab-content">
                <div class="cookie-banner-about-content">
                    <p>${translations.aboutDescription || 'Las cookies son pequeños archivos de texto que se almacenan en el dispositivo del usuario cuando visita un sitio web.'}</p>
                    <p><strong>${translations.cookieTypes || 'Tipos de cookies que utilizamos:'}</strong></p>
                    <ul>
                        <li><strong>Cookies técnicas o necesarias:</strong> Son esenciales para el funcionamiento básico del sitio web.</li>
                        <li><strong>Cookies de preferencias:</strong> Permiten recordar las configuraciones del usuario.</li>
                        <li><strong>Cookies estadísticas:</strong> Recopilan información de forma anónima sobre la interacción.</li>
                        <li><strong>Cookies de marketing:</strong> Se utilizan para mostrar publicidad relevante.</li>
                    </ul>
                    <p>${translations.gdprCompliance || 'En cumplimiento del RGPD, solicitamos su consentimiento para cookies no esenciales.'}</p>
                    <p>${translations.moreInfo || 'Para más información sobre nuestra política de privacidad, consulte nuestra política completa.'} ${aboutLink ? `${translations.detailedInfo || 'Para información detallada sobre cookies, visite'} ${aboutLink}.` : ''}</p>
                </div>
                
                <div class="cookie-banner-separator"></div>
                
                <div class="cookie-banner-actions">
                    <button class="cookie-banner-btn cookie-banner-btn-outline cookie-banner-reject-all" data-action="reject">
                        ${translations.rejectAll || 'Rechazar'}
                    </button>
                    
                    <button class="cookie-banner-btn cookie-banner-btn-primary cookie-banner-accept-all" data-action="accept">
                        ${translations.acceptAll || 'Aceptar'}
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
                        ${this.createSVGIcon(icon)}
                    </div>
                    <div class="cookie-category-text">
                        <h4>${title}</h4>
                        ${disabled ? '<span class="cookie-banner-badge">Obligatorias</span>' : ''}
                        <p>${description}</p>
                    </div>
                </div>
                <button class="cookie-switch ${checked ? 'checked' : ''}" ${disabled ? 'disabled' : ''} data-category="${type}">
                    <div class="cookie-switch-thumb"></div>
                </button>
            </div>
        `;
    }
    
    attachEventListeners() {
        if (!this.container) return;
        
        // Botones principales
        this.container.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.closest('[data-action]').dataset.action;
                this.handleAction(action);
            });
        });
        
        // Tabs
        this.container.querySelectorAll('[data-tab]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.currentTab = e.target.dataset.tab;
                this.render();
            });
        });
        
        // Cookie toggles
        this.container.querySelectorAll('[data-category]').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                const category = e.target.closest('[data-category]').dataset.category;
                if (category !== 'necessary') {
                    this.toggleConsent(category);
                    e.target.closest('[data-category]').classList.toggle('checked');
                }
            });
        });
    }
    
    handleAction(action) {
        switch (action) {
            case 'accept':
                this.acceptAll();
                break;
            case 'reject':
                this.rejectAll();
                break;
            case 'save':
                this.saveCustomSettings();
                break;
            case 'customize':
                this.showSettings = true;
                this.render();
                break;
            case 'close':
                this.showSettings = false;
                this.render();
                break;
        }
    }
    
    acceptAll() {
        this.consent = {
            necessary: true,
            analytics: true,
            marketing: true,
            preferences: true
        };
        this.saveConsent('accept_all');
    }
    
    rejectAll() {
        this.consent = {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false
        };
        this.saveConsent('reject_all');
    }
    
    saveCustomSettings() {
        this.saveConsent('custom_selection');
    }
    
    toggleConsent(type) {
        if (type !== 'necessary') {
            this.consent[type] = !this.consent[type];
        }
    }
    
    saveConsent(action) {
        localStorage.setItem('cookieConsent', JSON.stringify(this.consent));
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
        
        this.updateConsentMode(this.consent, action);
        this.showBanner = false;
        this.showSettings = false;
        this.render();
        
        // Mostrar mini banner después de un tiempo
        setTimeout(() => this.showMiniBanner(), 1000);
        
        // Disparar evento
        window.dispatchEvent(new CustomEvent('consentUpdated', { 
            detail: { consent: this.consent, action } 
        }));
    }
    
    updateConsentMode(consent, action) {
        if (typeof window !== 'undefined' && window.gtag) {
            window.gtag('consent', 'update', {
                'ad_storage': consent.marketing ? 'granted' : 'denied',
                'ad_user_data': consent.marketing ? 'granted' : 'denied',
                'ad_personalization': consent.marketing ? 'granted' : 'denied',
                'analytics_storage': consent.analytics ? 'granted' : 'denied',
                'functionality_storage': consent.preferences ? 'granted' : 'denied',
                'personalization_storage': consent.preferences ? 'granted' : 'denied',
            });
        }
        
        // Push to dataLayer
        if (action && typeof window !== 'undefined') {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                'event': 'consent_update',
                'consent': {
                    'ad_storage': consent.marketing ? 'granted' : 'denied',
                    'analytics_storage': consent.analytics ? 'granted' : 'denied',
                    'functionality_storage': consent.preferences ? 'granted' : 'denied',
                    'personalization_storage': consent.preferences ? 'granted' : 'denied',
                    'security_storage': 'granted',
                    'ad_user_data': consent.marketing ? 'granted' : 'denied',
                    'ad_personalization': consent.marketing ? 'granted' : 'denied'
                },
                'consent_action': action,
                'timestamp': new Date().toISOString()
            });
        }
    }
    
    showMiniBanner() {
        if (!this.miniBanner) {
            this.miniBanner = document.createElement('div');
            this.miniBanner.className = 'cookie-banner-minimized';
            this.miniBanner.innerHTML = `
                <div class="cookie-banner-minimized-icon">
                    ${this.createSVGIcon('cookie')}
                </div>
            `;
            this.miniBanner.title = 'Gestionar cookies';
            this.miniBanner.addEventListener('click', () => this.reopenBanner());
            document.body.appendChild(this.miniBanner);
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
        this.showSettings = false;
        this.hideMiniBanner();
        this.render();
    }
    
    resetConsent() {
        localStorage.removeItem('cookieConsent');
        localStorage.removeItem('cookieConsentDate');
        this.consent = {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false
        };
        this.hideMiniBanner();
        this.showBanner = true;
        this.render();
    }
    
    isAllAccepted() {
        return this.consent.necessary && this.consent.analytics && 
               this.consent.marketing && this.consent.preferences;
    }
    
    createSVGIcon(iconName) {
        const icons = {
            cookie: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5v.01"/><path d="M16 15.5v.01"/><path d="M12 12v.01"/><path d="M11 17v.01"/><path d="M7 14v.01"/></svg>',
            settings: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
            close: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>',
            shield: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-8 7.5s-8-2.5-8-7.5c0-1 0-3 0-3s3.5-3 8-3 8 2 8 3 0 2 0 3"/><path d="m9 12 2 2 4-4"/></svg>',
            'bar-chart': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="20" y2="10"/><line x1="18" x2="18" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="16"/></svg>',
            target: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>'
        };
        return icons[iconName] || icons.cookie;
    }
}

// Global initialization - Singleton pattern
let cookieBannerInstance = null;

function initializeCookieBannerUnified() {
    if (cookieBannerInstance) return cookieBannerInstance;
    
    const config = window.cookieBannerConfig || {};
    cookieBannerInstance = new CookieBannerUnified(config);
    
    // Global functions
    window.showCookieBanner = () => {
        cookieBannerInstance.reopenBanner();
    };
    
    window.resetCookieConsent = () => {
        cookieBannerInstance.resetConsent();
    };
    
    return cookieBannerInstance;
}

// Simple, clean initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCookieBannerUnified);
} else {
    initializeCookieBannerUnified();
}