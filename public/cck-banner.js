/**
 * Cookie Consent King Banner
 * @version 2.3.0 Final
 */
document.addEventListener('DOMContentLoaded', () => {
    const config = window.cckData || {};
    if (!config.texts || !config.ajax_url) {
        console.warn('Cookie Consent King: Data object not found or incomplete.');
        return;
    }

    const state = { consent: { necessary: true, preferences: false, analytics: false, marketing: false }, hasInitialConsent: false };
    const DOM = { bannerContainer: document.getElementById('cck-banner-container'), reopenContainer: document.getElementById('cck-reopen-trigger-container') };
    const log = (...args) => { if (config.debug) console.log('[Cookie Consent King]', ...args); };

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
            document.cookie = `${name}=${value || ""}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;
        },
        loadConsent() {
            const cookie = this.getCookie('cck_consent');
            if (cookie) {
                try {
                    const parsed = JSON.parse(decodeURIComponent(cookie));
                    Object.keys(state.consent).forEach(key => { if (typeof parsed[key] === 'boolean') state.consent[key] = parsed[key]; });
                    state.hasInitialConsent = true;
                } catch (e) { state.hasInitialConsent = false; }
            }
        },
        saveConsent(action) {
            this.setCookie('cck_consent', JSON.stringify(state.consent), 365);
            scriptManager.restoreBlockedScripts();
            this.logConsentToServer(action);
            ui.hideBanner();
            if (!DOM.reopenContainer.hasChildNodes()) ui.buildReopenTrigger();
        },
        logConsentToServer(action) {
            const formData = new URLSearchParams({ action: 'cck_log_consent', nonce: config.nonce, consent_action: action, consent_details: JSON.stringify(state.consent) });
            fetch(config.ajax_url, { method: 'POST', body: formData }).catch(error => console.error('Error logging consent:', error));
        }
    };

    const scriptManager = {
        isCategoryAllowed(category) { return category === 'necessary' || state.consent[category] === true; },
        restoreBlockedScripts() {
            document.querySelectorAll('script[type="text/plain"][data-cck-consent]').forEach(script => {
                if (this.isCategoryAllowed(script.dataset.cckConsent) && !script.dataset.cckRestored) this.unblockScript(script);
            });
            document.dispatchEvent(new CustomEvent('cck:consent-applied', { detail: { consent: state.consent } }));
        },
        unblockScript(blockedScript) {
            const replacement = document.createElement('script');
            ['src', 'id', 'class', 'async', 'defer'].forEach(attr => { if(blockedScript.dataset[attr]) replacement[attr] = blockedScript.dataset[attr]; });
            replacement.textContent = blockedScript.textContent;
            replacement.type = blockedScript.dataset.cckOrigType || 'text/javascript';
            blockedScript.parentNode.replaceChild(replacement, blockedScript);
            blockedScript.dataset.cckRestored = 'true';
        }
    };

    const ui = {
        buildOption(key, title, info = '', description = '') {
            const isNecessary = key === 'necessary';
            const switchHtml = isNecessary ? '' : `<label class="cck-switch"><input type="checkbox" data-consent="${key}"><span class="cck-slider"></span></label>`;
            const descriptionHtml = description ? `<div class="cck-option-description">${description}</div>` : '';
            const toggleHtml = description ? `<button class="cck-option-toggle" aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg></button>` : `<span class="cck-option-toggle-placeholder"></span>`;

            return `
                <div class="cck-option">
                    <div class="cck-option-main">
                        <div class="cck-option-label">
                            <label><strong>${title}</strong> ${info}</label>
                            ${toggleHtml}
                        </div>
                        ${switchHtml}
                    </div>
                    ${descriptionHtml}
                </div>`;
        },
        buildBanner() {
            const testControlsHtml = config.testButton.text ? `<div class="cck-test-controls"><button id="cck-test-btn" class="cck-btn cck-btn-secondary">${config.testButton.text}</button></div>` : '';
            DOM.bannerContainer.innerHTML = `
                <div id="cck-banner-backdrop"></div>
                <div id="cck-banner" class="cck-banner">
                    <div class="cck-header"><h2 class="cck-title">${config.texts.title}</h2><p class="cck-message">${config.texts.message}</p></div>
                    <div id="cck-main-view">
                        <div class="cck-actions">
                            <button id="cck-personalize-btn" class="cck-btn">${config.texts.personalize}</button>
                            <button id="cck-reject-btn" class="cck-btn">${config.texts.rejectAll}</button>
                            <button id="cck-accept-btn" class="cck-btn">${config.texts.acceptAll}</button>
                        </div>
                    </div>
                    <div id="cck-settings-view" style="display: none;">
                        <h3 class="cck-settings-title">${config.texts.settingsTitle}</h3>
                        <div class="cck-options">
                            ${this.buildOption('necessary', config.texts.necessary, config.texts.necessaryInfo, config.texts.desc_necessary)}
                            ${this.buildOption('preferences', config.texts.preferences, '', config.texts.desc_preferences)}
                            ${this.buildOption('analytics', config.texts.analytics, '', config.texts.desc_analytics)}
                            ${this.buildOption('marketing', config.texts.marketing, '', config.texts.desc_marketing)}
                        </div>
                        <div class="cck-actions">
                             <button id="cck-back-btn" class="cck-btn cck-btn-secondary">${config.texts.back}</button>
                             <button id="cck-save-btn" class="cck-btn">${config.texts.savePreferences}</button>
                        </div>
                    </div>
                    ${testControlsHtml}
                </div>`;
            this.addBannerEventListeners();
            this.updateToggles();
        },
        buildReopenTrigger() {
            const iconMarkup = config.reopen_icon_url ? `<img src="${config.reopen_icon_url}" alt="${config.texts.reopenTrigger}">` : `<span class="cck-reopen-arrow" aria-hidden="true">🍪</span>`;
            DOM.reopenContainer.innerHTML = `<div id="cck-reopen-trigger" role="button" tabindex="0" aria-label="${config.texts.reopenTrigger}">${iconMarkup}</div>`;
            DOM.reopenContainer.querySelector('#cck-reopen-trigger').addEventListener('click', () => {
                if (!DOM.bannerContainer.hasChildNodes()) this.buildBanner();
                this.showBanner();
            });
        },
        addBannerEventListeners() {
            document.getElementById('cck-accept-btn')?.addEventListener('click', () => {
                Object.keys(state.consent).forEach(key => state.consent[key] = true);
                consentManager.saveConsent('accept_all');
            });
            document.getElementById('cck-reject-btn')?.addEventListener('click', () => {
                Object.keys(state.consent).forEach(key => state.consent[key] = (key === 'necessary'));
                consentManager.saveConsent('reject_all');
            });
            document.getElementById('cck-save-btn')?.addEventListener('click', () => consentManager.saveConsent('custom_selection'));
            document.getElementById('cck-personalize-btn')?.addEventListener('click', () => this.toggleView(true));
            document.getElementById('cck-back-btn')?.addEventListener('click', () => this.toggleView(false));
            document.querySelectorAll('#cck-settings-view .cck-switch input').forEach(input => {
                input.addEventListener('change', (e) => state.consent[e.target.dataset.consent] = e.target.checked);
            });
            document.getElementById('cck-test-btn')?.addEventListener('click', () => {
                consentManager.deleteCookie('cck_consent');
                DOM.reopenContainer.innerHTML = '';
                this.buildBanner();
                this.showBanner();
            });
            document.querySelectorAll('.cck-option-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    const button = e.currentTarget;
                    const desc = button.closest('.cck-option').querySelector('.cck-option-description');
                    if(desc) {
                        const isExpanded = button.getAttribute('aria-expanded') === 'true';
                        button.setAttribute('aria-expanded', !isExpanded);
                        button.classList.toggle('expanded', !isExpanded);
                        desc.style.maxHeight = isExpanded ? null : desc.scrollHeight + "px";
                    }
                });
            });
        },
        toggleView(showSettings) {
            document.getElementById('cck-main-view').style.display = showSettings ? 'none' : 'block';
            document.getElementById('cck-settings-view').style.display = showSettings ? 'block' : 'none';
        },
        updateToggles() {
            document.querySelectorAll('#cck-settings-view .cck-switch input').forEach(input => {
                if (input.dataset.consent in state.consent) input.checked = state.consent[input.dataset.consent];
            });
        },
        showBanner() {
            setTimeout(() => {
                document.getElementById('cck-banner-backdrop')?.classList.add('cck-visible');
                document.getElementById('cck-banner')?.classList.add('cck-visible');
            }, 50);
        },
        hideBanner() {
            const banner = document.getElementById('cck-banner');
            if (banner) {
                document.getElementById('cck-banner-backdrop')?.classList.remove('cck-visible');
                banner.classList.remove('cck-visible');
                setTimeout(() => { if(!config.forceShow) DOM.bannerContainer.innerHTML = ''; }, 500);
            }
        }
    };

    function init() {
        consentManager.loadConsent();
        if (state.hasInitialConsent && !config.forceShow) {
            scriptManager.restoreBlockedScripts();
            ui.buildReopenTrigger();
        } else {
            ui.buildBanner();
            ui.showBanner();
        }
    }
    if (DOM.bannerContainer && DOM.reopenContainer) init();
});
