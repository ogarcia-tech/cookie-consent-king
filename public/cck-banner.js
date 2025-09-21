/**
 * Cookie Consent King Banner
 *
 * @version 2.0.0
 * @author  Oscar Garcia
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. CONFIGURACIÓN Y ESTADO INICIAL
    // ----------------------------------------------------
    const config = window.cckData || {};
    if (!config.texts || !config.ajax_url) {
        console.warn('Cookie Consent King: Data object (cckData) not found or incomplete.');
        return;
    }

    const state = {
        consent: {
            necessary: true,
            preferences: false,
            analytics: false,
            marketing: false,
        },
        hasInitialConsent: false,
    };

    const DOM = {
        bannerContainer: document.getElementById('cck-banner-container'),
        reopenContainer: document.getElementById('cck-reopen-trigger-container'),
    };

    const log = (...args) => {
        if (config.debug) {
            console.log('[Cookie Consent King]', ...args);
        }
    };

    // 2. LÓGICA DE COOKIES Y CONSENTIMIENTO
    // ----------------------------------------------------
    const consentManager = {
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },
        setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "; expires=" + date.toUTCString();
            document.cookie = `${name}=${value || ""}${expires}; path=/; SameSite=Lax`;
        },
        deleteCookie(name) {
            document.cookie = `${name}=; Max-Age=-99999999; path=/; SameSite=Lax`;
        },
        loadConsent() {
            const cookie = this.getCookie('cck_consent');
            if (cookie) {
                try {
                    const parsed = JSON.parse(decodeURIComponent(cookie));
                    // Solo actualizamos las categorías conocidas para evitar datos corruptos
                    Object.keys(state.consent).forEach(key => {
                        if (typeof parsed[key] === 'boolean') {
                            state.consent[key] = parsed[key];
                        }
                    });
                    state.hasInitialConsent = true;
                    log('Loaded consent from cookie:', state.consent);
                } catch (e) {
                    log('Error parsing consent cookie. Using defaults.');
                    state.hasInitialConsent = false;
                }
            } else {
                log('No consent cookie found. Using defaults.');
            }
        },
        saveConsent(action) {
            this.setCookie('cck_consent', JSON.stringify(state.consent), 365);
            log('Consent saved:', { action, consent: state.consent });
            
            // Lógica de restauración de scripts y notificación al servidor
            scriptManager.restoreBlockedScripts();
            this.logConsentToServer(action);

            // Oculta el banner y muestra el botón para reabrir
            ui.hideBanner();
            if (!DOM.reopenContainer.hasChildNodes()) {
                ui.buildReopenTrigger();
            }
        },
        logConsentToServer(action) {
            const formData = new URLSearchParams({
                action: 'cck_log_consent',
                nonce: config.nonce,
                consent_action: action,
                consent_details: JSON.stringify(state.consent)
            });
            fetch(config.ajax_url, { method: 'POST', body: formData })
                .catch(error => console.error('Error logging consent:', error));
        }
    };

    // 3. MANEJO DE SCRIPTS BLOQUEADOS
    // ----------------------------------------------------
    const scriptManager = {
        executedCallbacks: new Set(),
        isCategoryAllowed(category) {
            return category === 'necessary' || state.consent[category] === true;
        },
        restoreBlockedScripts() {
            document.querySelectorAll('script[type="text/plain"][data-cck-consent]').forEach(script => {
                const category = script.dataset.cckConsent;
                if (this.isCategoryAllowed(category) && !script.dataset.cckRestored) {
                    this.unblockScript(script);
                }
            });
            log('Restored scripts based on consent:', state.consent);
            document.dispatchEvent(new CustomEvent('cck:consent-applied', { detail: { consent: state.consent } }));
        },
        unblockScript(blockedScript) {
            const replacement = document.createElement('script');
            // Copiamos todos los atributos excepto los de control
            ['src', 'id', 'class', 'async', 'defer'].forEach(attr => {
                if(blockedScript.dataset[attr]) {
                    replacement[attr] = blockedScript.dataset[attr];
                }
            });

            replacement.textContent = blockedScript.textContent;
            replacement.type = blockedScript.dataset.cckOrigType || 'text/javascript';

            blockedScript.parentNode.replaceChild(replacement, blockedScript);
            blockedScript.dataset.cckRestored = 'true'; // Marcamos como restaurado
        }
    };

    // 4. MANEJO DE LA INTERFAZ DE USUARIO (UI)
    // ----------------------------------------------------
    const ui = {
        buildBanner() {
            const iconHtml = config.icon_url ? `<img src="${config.icon_url}" alt="Icon" class="cck-icon">` : '';
            const testControlsHtml = config.testButton.text ? `
                <div class="cck-test-controls">
                    <button id="cck-test-btn" class="cck-btn cck-btn-tertiary">${config.testButton.text}</button>
                    ${config.testButton.helpUrl ? `<a href="${config.testButton.helpUrl}" class="cck-test-link" target="_blank" rel="noopener noreferrer">${config.testButton.helpLabel}</a>` : ''}
                </div>` : '';

            DOM.bannerContainer.innerHTML = `
                <div id="cck-banner-backdrop"></div>
                <div id="cck-banner" class="cck-banner">
                    <div class="cck-header">
                        ${iconHtml}
                        <div>
                            <h2 class="cck-title">${config.texts.title}</h2>
                            <p class="cck-message">${config.texts.message}</p>
                        </div>
                    </div>
                    <div id="cck-main-view">
                        <div class="cck-actions">
                            <button id="cck-personalize-btn" class="cck-btn cck-btn-secondary">${config.texts.personalize}</button>
                            <button id="cck-reject-btn" class="cck-btn cck-btn-secondary">${config.texts.rejectAll}</button>
                            <button id="cck-accept-btn" class="cck-btn cck-btn-primary">${config.texts.acceptAll}</button>
                        </div>
                    </div>
                    <div id="cck-settings-view" style="display: none;">
                        <h3 class="cck-settings-title">${config.texts.settingsTitle}</h3>
                        <div class="cck-options">
                            <div class="cck-option"><label><strong>${config.texts.necessary}</strong> (Siempre activo)</label><label class="cck-switch"><input type="checkbox" data-consent="necessary" checked disabled><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${config.texts.preferences}</label><label class="cck-switch"><input type="checkbox" data-consent="preferences"><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${config.texts.analytics}</label><label class="cck-switch"><input type="checkbox" data-consent="analytics"><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${config.texts.marketing}</label><label class="cck-switch"><input type="checkbox" data-consent="marketing"><span class="cck-slider"></span></label></div>
                        </div>
                        <div class="cck-actions">
                             <button id="cck-back-btn" class="cck-btn cck-btn-secondary">${config.texts.back}</button>
                             <button id="cck-save-btn" class="cck-btn cck-btn-primary">${config.texts.savePreferences}</button>
                        </div>
                    </div>
                    ${testControlsHtml}
                </div>`;
            this.addBannerEventListeners();
            this.updateToggles();
        },
        buildReopenTrigger() {
            const label = config.texts.reopenTrigger;
            const iconMarkup = config.reopen_icon_url ? `<img src="${config.reopen_icon_url}" alt="${label}">` : `<span class="cck-reopen-arrow" aria-hidden="true">🍪</span>`;
            DOM.reopenContainer.innerHTML = `<div id="cck-reopen-trigger" role="button" tabindex="0" aria-label="${label}">${iconMarkup}</div>`;

            DOM.reopenContainer.querySelector('#cck-reopen-trigger').addEventListener('click', () => {
                if (!DOM.bannerContainer.hasChildNodes()) {
                    this.buildBanner();
                }
                this.showBanner();
            });
        },
        addBannerEventListeners() {
            // Botones de acción principal
            document.getElementById('cck-accept-btn')?.addEventListener('click', () => {
                Object.keys(state.consent).forEach(key => state.consent[key] = true);
                consentManager.saveConsent('accept_all');
            });
            document.getElementById('cck-reject-btn')?.addEventListener('click', () => {
                Object.keys(state.consent).forEach(key => state.consent[key] = (key === 'necessary'));
                consentManager.saveConsent('reject_all');
            });
            document.getElementById('cck-save-btn')?.addEventListener('click', () => {
                consentManager.saveConsent('custom_selection');
            });

            // Navegación entre vistas
            document.getElementById('cck-personalize-btn')?.addEventListener('click', () => this.toggleView(true));
            document.getElementById('cck-back-btn')?.addEventListener('click', () => this.toggleView(false));

            // Toggles de consentimiento
            document.querySelectorAll('#cck-settings-view .cck-switch input').forEach(input => {
                input.addEventListener('change', (e) => {
                    const consentKey = e.target.dataset.consent;
                    if (consentKey !== 'necessary') {
                        state.consent[consentKey] = e.target.checked;
                        log(`Preference changed: ${consentKey} -> ${e.target.checked}`);
                    }
                });
            });
            
            // Controles de prueba
            document.getElementById('cck-test-btn')?.addEventListener('click', () => {
                log('Resetting consent for testing.');
                consentManager.deleteCookie('cck_consent');
                DOM.reopenContainer.innerHTML = '';
                this.buildBanner();
                this.showBanner();
            });
        },
        toggleView(showSettings) {
            document.getElementById('cck-main-view').style.display = showSettings ? 'none' : 'block';
            document.getElementById('cck-settings-view').style.display = showSettings ? 'block' : 'none';
        },
        updateToggles() {
            document.querySelectorAll('#cck-settings-view .cck-switch input').forEach(input => {
                const key = input.dataset.consent;
                if (key in state.consent) {
                    input.checked = state.consent[key];
                }
            });
        },
        showBanner() {
            setTimeout(() => {
                document.getElementById('cck-banner-backdrop')?.classList.add('cck-visible');
                document.getElementById('cck-banner')?.classList.add('cck-visible');
                log('Banner is now visible.');
            }, 50);
        },
        hideBanner() {
            const banner = document.getElementById('cck-banner');
            if (banner) {
                document.getElementById('cck-banner-backdrop')?.classList.remove('cck-visible');
                banner.classList.remove('cck-visible');
                // Opcional: eliminar el banner del DOM después de ocultarlo para limpiar
                setTimeout(() => {
                    if(!config.forceShow) DOM.bannerContainer.innerHTML = '';
                }, 500);
            }
            log('Banner hidden.');
        }
    };

    // 5. INICIALIZACIÓN
    // ----------------------------------------------------
    function init() {
        log('Initializing Cookie Consent King...', { config });
        consentManager.loadConsent();

        if (state.hasInitialConsent && !config.forceShow) {
            log('Consent already exists. Applying scripts and showing reopen trigger.');
            scriptManager.restoreBlockedScripts();
            ui.buildReopenTrigger();
        } else {
            log('No consent or forceShow is active. Building and showing the banner.');
            ui.buildBanner();
            ui.showBanner();
        }
    }

    // Arrancamos el script
    if (DOM.bannerContainer && DOM.reopenContainer) {
        init();
    } else {
        console.error('Cookie Consent King: Critical containers (#cck-banner-container or #cck-reopen-trigger-container) not found in the DOM.');
    }
});
