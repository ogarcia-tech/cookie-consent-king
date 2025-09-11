document.addEventListener('DOMContentLoaded', () => {
    const data = window.cckData || {};
    if (Object.keys(data).length === 0) return;
    
    const texts = data.texts || {};
    const bannerContainer = document.getElementById('cck-banner-container');
    const reopenContainer = document.getElementById('cck-reopen-trigger-container');
    if (!bannerContainer || !reopenContainer) return;

    let consentState = {
        necessary: true,
        preferences: false,
        analytics: false,
        marketing: false,
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

    const buildBanner = () => {
        const iconHtml = data.icon_url ? `<img src="${data.icon_url}" alt="Icon" class="cck-icon">` : '';

        bannerContainer.innerHTML = `
            <div id="cck-banner-backdrop"></div>
            <div id="cck-banner" class="cck-banner">
                <div class="cck-main">
                    <div class="cck-header">${iconHtml}<div class="cck-content"><h3 class="cck-title">${texts.title || ''}</h3><p class="cck-message">${texts.message || ''}</p></div></div>
                    <div class="cck-actions">
                        <button id="cck-personalize-btn" class="cck-btn cck-btn-secondary">${texts.personalize || 'Personalizar'}</button>
                        <button id="cck-reject-btn" class="cck-btn cck-btn-primary">${texts.rejectAll || 'Rechazar todas'}</button>
                        <button id="cck-accept-btn" class="cck-btn cck-btn-primary">${texts.acceptAll || 'Aceptar todas'}</button>
                    </div>
                </div>
                <div class="cck-settings">
                    <div class="cck-settings-header"><h3 class="cck-settings-title">${texts.personalize || 'Personalizar'}</h3><button id="cck-close-btn" class="cck-close-btn">&times;</button></div>
                    <div class="cck-options">
                        <div class="cck-option"><label><strong>Necesario</strong> (Siempre activo)</label><label class="cck-switch"><input type="checkbox" data-consent="necessary" checked disabled><span class="cck-slider"></span></label></div>
                        <div class="cck-option"><label>${texts.preferences || 'Preferencias'}</label><label class="cck-switch"><input type="checkbox" data-consent="preferences"><span class="cck-slider"></span></label></div>
                        <div class="cck-option"><label>${texts.analytics || 'Análisis'}</label><label class="cck-switch"><input type="checkbox" data-consent="analytics"><span class="cck-slider"></span></label></div>
                        <div class="cck-option"><label>${texts.marketing || 'Marketing'}</label><label class="cck-switch"><input type="checkbox" data-consent="marketing"><span class="cck-slider"></span></label></div>
                    </div>
                    <div class="cck-actions"><button id="cck-save-btn" class="cck-btn cck-btn-primary">${texts.savePreferences || 'Guardar preferencias'}</button></div>
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
        const trigger = document.getElementById('cck-reopen-trigger');
        trigger.addEventListener('click', () => {
            if (!document.getElementById('cck-banner')) {
                buildBanner();
            }
            setTimeout(showBanner, 50);
        });
        setTimeout(() => trigger?.classList.add('cck-visible'), 100);
    };

    const saveConsent = (action, details) => {
        setCookie('cck_consent', JSON.stringify(details), 365);
        hideBanner();
        
        if (!document.getElementById('cck-reopen-trigger')) {
            buildReopenTrigger();
        }

        const formData = new URLSearchParams();
        formData.append('action', 'cck_log_consent');
        formData.append('nonce', data.nonce);
        formData.append('consent_action', action);
        formData.append('consent_details', JSON.stringify(details));
        fetch(data.ajax_url, { method: 'POST', body: formData }).catch(error => console.error('Error logging consent:', error));
    };

    const showBanner = () => {
        document.getElementById('cck-banner-backdrop')?.classList.add('cck-visible');
        document.getElementById('cck-banner')?.classList.add('cck-visible');
    };

    const hideBanner = () => {
        document.getElementById('cck-banner-backdrop')?.classList.remove('cck-visible');
        document.getElementById('cck-banner')?.classList.remove('cck-visible');
    };

    const addEventListeners = () => {
        document.getElementById('cck-accept-btn')?.addEventListener('click', () => saveConsent('accept_all', { necessary: true, preferences: true, analytics: true, marketing: true }));
        document.getElementById('cck-reject-btn')?.addEventListener('click', () => saveConsent('reject_all', { necessary: true, preferences: false, analytics: false, marketing: false }));
        
        const settingsView = document.querySelector('.cck-settings');
        const mainView = document.querySelector('.cck-main');

        document.getElementById('cck-personalize-btn')?.addEventListener('click', () => {
            if (mainView) mainView.style.display = 'none';
            if (settingsView) settingsView.style.display = 'block';
        });

        document.getElementById('cck-close-btn')?.addEventListener('click', () => {
            if (settingsView) settingsView.style.display = 'none';
            if (mainView) mainView.style.display = 'block';
        });

        document.querySelectorAll('.cck-switch input').forEach(input => {
            input.addEventListener('change', (e) => {
                if(e.target.dataset.consent !== 'necessary') {
                    consentState[e.target.dataset.consent] = e.target.checked;
                }
            });
        });

        document.getElementById('cck-save-btn')?.addEventListener('click', () => saveConsent('custom_selection', consentState));
    };

    const existingCookie = getCookie('cck_consent');
    if (!existingCookie) {
        buildBanner();
        setTimeout(showBanner, 100);
    } else {
        buildReopenTrigger();
    }
});