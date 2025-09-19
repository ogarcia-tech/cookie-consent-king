document.addEventListener('DOMContentLoaded', () => {
    const data = window.cckData || {};
    if (Object.keys(data).length === 0) {
        console.warn('Cookie Consent King: Data object not found.');
        return;
    }

    const texts = data.texts || {};
    const debugEnabled = Boolean(data.debug);
    const log = (...args) => {
        if (debugEnabled) {
            console.log('[Cookie Consent King]', ...args);
        }
    };

    if (debugEnabled) {
        log('Debug mode activo.');
    }

    const testButtonConfig = data.testButton || {};
    const testButtonLabel = testButtonConfig.text || texts.testButton || 'Limpiar y Probar';
    const testButtonHelpUrl = testButtonConfig.helpUrl || '';
    const testButtonHelpLabel = testButtonConfig.helpLabel || texts.testHelp || '';

    const bannerContainer = document.getElementById('cck-banner-container');
    const reopenContainer = document.getElementById('cck-reopen-trigger-container');
    if (!bannerContainer || !reopenContainer) return;

    let consentState = {
        necessary: true,
        preferences: false,
        analytics: false,
        marketing: false,
    };

    const resetConsentState = () => {
        consentState = {
            necessary: true,
            preferences: false,
            analytics: false,
            marketing: false,
        };
    };

    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    };

    const setCookie = (name, value, days) => {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
    };

    const deleteCookie = (name) => {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Lax`;
    };

    const buildBanner = () => {
        resetConsentState();
        const iconHtml = data.icon_url ? `<img src="${data.icon_url}" alt="Icon" class="cck-icon">` : '';
        log('Construyendo banner principal.');

        bannerContainer.innerHTML = `
            <div id="cck-banner-backdrop"></div>
            <div id="cck-banner" class="cck-banner">
                <div class="cck-tab-nav" role="tablist">
                    <button class="cck-tab-btn cck-active" data-tab="consent" role="tab" aria-selected="true">${texts.consentTab || 'Consentimiento'}</button>
                    <button class="cck-tab-btn" data-tab="details" role="tab" aria-selected="false">${texts.detailsTab || 'Detalles'}</button>
                    <button class="cck-tab-btn" data-tab="about" role="tab" aria-selected="false">${texts.aboutTab || 'Acerca de las cookies'}</button>
                </div>
                <div class="cck-tab-panels">
                    <section class="cck-tab-panel cck-active" data-tab-panel="consent" role="tabpanel">
                        <div class="cck-header">${iconHtml}<div class="cck-content"><h3 class="cck-title">${texts.title || ''}</h3><p class="cck-message">${texts.message || ''}</p></div></div>
                        <div class="cck-actions">
                            <button id="cck-personalize-btn" class="cck-btn cck-btn-secondary">${texts.personalize || 'Personalizar'}</button>
                            <button id="cck-reject-btn" class="cck-btn cck-btn-primary">${texts.rejectAll || 'Rechazar todas'}</button>
                            <button id="cck-accept-btn" class="cck-btn cck-btn-primary">${texts.acceptAll || 'Aceptar todas'}</button>
                        </div>
                    </section>
                    <section class="cck-tab-panel" data-tab-panel="details" role="tabpanel" aria-hidden="true">
                        <p class="cck-tab-description">${texts.detailsDescription || ''}</p>
                        <div class="cck-options">
                            <div class="cck-option"><label><strong>Necesario</strong> (Siempre activo)</label><label class="cck-switch"><input type="checkbox" data-consent="necessary" checked disabled><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.preferences || 'Preferencias'}</label><label class="cck-switch"><input type="checkbox" data-consent="preferences"><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.analytics || 'Análisis'}</label><label class="cck-switch"><input type="checkbox" data-consent="analytics"><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.marketing || 'Marketing'}</label><label class="cck-switch"><input type="checkbox" data-consent="marketing"><span class="cck-slider"></span></label></div>
                        </div>
                        <div class="cck-actions"><button id="cck-save-btn" class="cck-btn cck-btn-primary">${texts.savePreferences || 'Guardar preferencias'}</button></div>
                    </section>
                    <section class="cck-tab-panel" data-tab-panel="about" role="tabpanel" aria-hidden="true">
                        <p class="cck-tab-description">${texts.aboutDescription || ''}</p>
                    </section>
                </div>
                <div class="cck-test-controls">
                    <button id="cck-reset-consent-btn" class="cck-btn cck-btn-tertiary">${testButtonLabel}</button>
                    ${testButtonHelpUrl ? `<a href="${testButtonHelpUrl}" target="_blank" rel="noopener noreferrer" class="cck-test-link">${testButtonHelpLabel || testButtonHelpUrl}</a>` : ''}
                </div>
            </div>
        `;
        addEventListeners();
    };

    const buildReopenTrigger = () => {
        if (!data.reopen_icon_url) return;
        reopenContainer.innerHTML = `
            <div id="cck-reopen-trigger">
                <img src="${data.reopen_icon_url}" alt="${texts.personalize}">
            </div>
        `;
        log('Renderizando disparador para reabrir el banner.');
        const trigger = document.getElementById('cck-reopen-trigger');
        trigger.addEventListener('click', () => {
            if (!document.getElementById('cck-banner')) {
                buildBanner();
            }
            setTimeout(showBanner, 50);
            log('Banner reabierto manualmente desde el disparador.');
        });
        setTimeout(() => trigger?.classList.add('cck-visible'), 100);
    };

    const pushConsentUpdateEvent = (details, action) => {
        window.dataLayer = window.dataLayer || [];

        const consentPayload = {
            functionality_storage: details.necessary ? 'granted' : 'denied',
            personalization_storage: details.preferences ? 'granted' : 'denied',
            analytics_storage: details.analytics ? 'granted' : 'denied',
            ad_storage: details.marketing ? 'granted' : 'denied',
            security_storage: 'granted',
        };

        window.dataLayer.push({
            event: 'consent_update',
            consent: consentPayload,
            consent_action: action,
            timestamp: new Date().toISOString(),
        });
    };

    const saveConsent = (action, details) => {
        window.dataLayer = window.dataLayer || [];
        setCookie('cck_consent', JSON.stringify(details), 365);
        hideBanner();
        log(`Guardando consentimiento con la acción: ${action}.`, details);
        if (!document.getElementById('cck-reopen-trigger')) {
            buildReopenTrigger();
        }

        pushConsentUpdateEvent(details, action);

        const formData = new URLSearchParams();
        formData.append('action', 'cck_log_consent');
        formData.append('nonce', data.nonce);
        formData.append('consent_action', action);
        formData.append('consent_details', JSON.stringify(details));
        fetch(data.ajax_url, {
            method: 'POST',
            body: formData
        }).catch(error => console.error('Error logging consent:', error));
    };

    const showBanner = () => {
        document.getElementById('cck-banner-backdrop')?.classList.add('cck-visible');
        document.getElementById('cck-banner')?.classList.add('cck-visible');
        log('Banner visible.');
    };

    const hideBanner = () => {
        document.getElementById('cck-banner-backdrop')?.classList.remove('cck-visible');
        document.getElementById('cck-banner')?.classList.remove('cck-visible');
        log('Banner ocultado tras una decisión explícita.');
    };

    const resetConsentForTesting = () => {
        log('Limpiando cookies/localStorage para pruebas manuales.');
        deleteCookie('cck_consent');
        try {
            localStorage.removeItem('cck_consent');
            log('Clave "cck_consent" eliminada de localStorage.');
        } catch (error) {
            log('No fue posible acceder a localStorage:', error);
        }
        reopenContainer.innerHTML = '';
        buildBanner();
        setTimeout(showBanner, 50);
    };

    const addEventListeners = () => {
        document.getElementById('cck-accept-btn')?.addEventListener('click', () => saveConsent('accept_all', { necessary: true, preferences: true, analytics: true, marketing: true }));
        document.getElementById('cck-reject-btn')?.addEventListener('click', () => saveConsent('reject_all', { necessary: true, preferences: false, analytics: false, marketing: false }));

        const settingsView = document.querySelector('.cck-settings');
        const mainView = document.querySelector('.cck-main');

        document.getElementById('cck-personalize-btn')?.addEventListener('click', () => {
            if (mainView) mainView.style.display = 'none';
            if (settingsView) settingsView.style.display = 'block';
            log('Vista de personalización abierta.');
        });

        document.getElementById('cck-close-btn')?.addEventListener('click', () => {
            if (settingsView) settingsView.style.display = 'none';
            if (mainView) mainView.style.display = 'block';
            log('Vista principal restaurada sin cerrar el banner.');

        });

        document.getElementById('cck-personalize-btn')?.addEventListener('click', () => setActiveTab('details'));

        document.querySelectorAll('.cck-switch input').forEach(input => {
            input.addEventListener('change', (e) => {
                if(e.target.dataset.consent !== 'necessary') {
                    consentState[e.target.dataset.consent] = e.target.checked;
                    log(`Preferencia modificada: ${e.target.dataset.consent} -> ${e.target.checked}`);
                }
            });
        });

        document.getElementById('cck-save-btn')?.addEventListener('click', () => saveConsent('custom_selection', consentState));
        document.getElementById('cck-reset-consent-btn')?.addEventListener('click', (event) => {
            event.preventDefault();
            resetConsentForTesting();
        });
    };

    const existingCookie = getCookie('cck_consent');
    if (!existingCookie || data.forceShow) {
        buildBanner();
        if (existingCookie && data.forceShow) {
            log('Banner forzado a mostrarse ignorando la cookie previa.');
        }
        setTimeout(showBanner, 100);
    } else {
        try {
            const storedConsent = JSON.parse(existingCookie);
            consentState = { ...consentState, ...storedConsent };
            pushConsentUpdateEvent(consentState, 'load_existing');
        } catch (error) {
            console.error('Error parsing stored consent:', error);
        }
        buildReopenTrigger();
        log('Cookie de consentimiento detectada, banner oculto hasta nueva interacción.');
    }
});
