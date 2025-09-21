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

    const executedCallbackTokens = new Set();
    let consentState = Object.assign({
        necessary: true,
        preferences: false,
        analytics: false,
        marketing: false,
    }, data.consentState || {});

    const resetConsentState = () => {
        consentState = {
            necessary: true,
            preferences: false,
            analytics: false,
            marketing: false,
        };
    };

    const defaultCookieCategories = {
        necessary: { label: texts.necessary || 'Necesario', patterns: ['cck_consent', 'wordpress_logged_in_*', 'wp-settings-*', 'wp-settings-time-*'], showInDetails: true, fallback: false },
        preferences: { label: texts.preferences || 'Preferencias', patterns: [], showInDetails: true },
        analytics: { label: texts.analytics || 'Análisis', patterns: ['_ga', '_gid', '_gat', '_gcl_au', '__utma', '__utmb', '__utmc', '__utmt', '__utmz'], showInDetails: true },
        marketing: { label: texts.marketing || 'Marketing', patterns: ['_fbp', 'fr', 'IDE', 'test_cookie', 'YSC', 'VISITOR_INFO1_LIVE', 'CONSENT', 'PREF'], showInDetails: true },
        uncategorized: { label: texts.uncategorized || 'Sin clasificar', patterns: [], showInDetails: true, fallback: true }
    };

    const mergeCookieCategories = () => {
        const categoriesFromData = data.cookieCategories || {};
        const merged = JSON.parse(JSON.stringify(defaultCookieCategories));

        Object.entries(categoriesFromData).forEach(([key, value]) => {
            if (!value || typeof value !== 'object') return;
            const base = merged[key] || {};
            const overridePatterns = Array.isArray(value.patterns) ? value.patterns : null;
            merged[key] = {
                ...base,
                ...value,
                patterns: overridePatterns ? [...overridePatterns] : [...(base.patterns || [])]
            };
        });

        if (!Object.values(merged).some(category => category.fallback)) {
            merged.uncategorized = merged.uncategorized || { label: texts.uncategorized || 'Sin clasificar', patterns: [], showInDetails: true, fallback: true };
            merged.uncategorized.fallback = true;
        }

        return merged;
    };

    const cookieCategories = mergeCookieCategories();

    const buildEmptySummary = () => {
        const counts = {};
        const cookies = {};
        Object.keys(cookieCategories).forEach((category) => {
            counts[category] = 0;
            cookies[category] = [];
        });
        return { counts, cookies, total: 0 };
    };

    let cookieSummary = buildEmptySummary();

    const wildcardToRegExp = (pattern) => {
        if (typeof pattern !== 'string') return null;
        const escapeRegExp = (segment) => segment.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const normalizedPattern = pattern.split('*').map(escapeRegExp).join('.*');
        try {
            return new RegExp(`^${normalizedPattern}$`, 'i');
        } catch (error) {
            log('Error creando RegExp para el patrón con comodines', pattern, error);
            return null;
        }
    };

    const patternMatches = (pattern, cookieName) => {
        if (!pattern) return false;
        if (pattern instanceof RegExp) return pattern.test(cookieName);
        if (typeof pattern === 'string') {
            if (pattern.includes('*')) {
                const wildcardRegex = wildcardToRegExp(pattern);
                return wildcardRegex ? wildcardRegex.test(cookieName) : false;
            }
            return pattern.toLowerCase() === cookieName.toLowerCase();
        }
        return false;
    };

    const getFallbackCategoryKey = () => Object.entries(cookieCategories).find(([, config]) => config.fallback)?.[0] || Object.keys(cookieCategories)[0];

    const categorizeCookie = (cookieName) => {
        const normalizedName = cookieName.trim();
        for (const [categoryKey, config] of Object.entries(cookieCategories)) {
            if (config?.patterns?.some(pattern => patternMatches(pattern, normalizedName))) {
                return categoryKey;
            }
        }
        return getFallbackCategoryKey();
    };

    const renderCookieSummary = () => {
        const totalElement = document.getElementById('cck-cookie-total');
        const listElement = document.getElementById('cck-cookie-details-list');
        const emptyElement = document.getElementById('cck-cookie-empty');
        if (!totalElement || !listElement) return;

        totalElement.textContent = cookieSummary.total.toString();
        listElement.innerHTML = '';
        let hasVisibleCategory = false;

        Object.entries(cookieCategories).forEach(([categoryKey, config]) => {
            if (config.showInDetails === false) return;
            hasVisibleCategory = true;
            const count = cookieSummary.counts[categoryKey] || 0;
            const label = config.label || categoryKey;
            const row = document.createElement('div');
            row.className = 'cck-cookie-detail';
            row.innerHTML = `<span class="cck-cookie-detail-label">${label}</span><span class="cck-cookie-detail-count">${count}</span>`;
            listElement.appendChild(row);
        });

        if (emptyElement) {
            emptyElement.style.display = (!cookieSummary.total && hasVisibleCategory) ? 'block' : 'none';
        }
    };

    const scanCookies = () => {
        const summary = buildEmptySummary();
        const rawCookies = document.cookie ? document.cookie.split(';') : [];

        rawCookies.forEach((rawCookie) => {
            const trimmed = rawCookie.trim();
            if (!trimmed) return;
            const separatorIndex = trimmed.indexOf('=');
            const rawName = separatorIndex >= 0 ? trimmed.slice(0, separatorIndex) : trimmed;
            let cookieName = rawName.trim();
            try {
                cookieName = decodeURIComponent(cookieName);
            } catch (error) {
                cookieName = rawName.trim();
            }
            if (!cookieName) return;
            const category = categorizeCookie(cookieName);
            summary.counts[category] = (summary.counts[category] || 0) + 1;
            summary.cookies[category] = summary.cookies[category] || [];
            summary.cookies[category].push(cookieName);
            summary.total += 1;
        });

        cookieSummary = summary;
        renderCookieSummary();
        return summary;
    };

    const getCookie = (name) => {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    };

    const parseJSON = (value, fallback = null) => {
        try {
            return JSON.parse(value);
        } catch (e) {
            return fallback;
        }
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

    const resolveFunction = (path) => {
        if (!path || typeof path !== 'string') return null;
        return path.split('.').reduce((context, segment) => context?.[segment], window);
    };

    const isCategoryAllowed = (category, state) => {
        if (!category || category === 'necessary') return true;
        return !!(state && state[category]);
    };

    const runConsentCallback = (callbackName, meta, state) => {
        if (!callbackName) return;
        const token = [callbackName, meta?.handle || '', meta?.category || ''].join('|');
        if (executedCallbackTokens.has(token)) return;

        const callback = resolveFunction(callbackName);
        if (typeof callback === 'function') {
            try {
                callback(meta || {}, state || {});
            } catch (error) {
                console.error(`Cookie Consent King: error executing callback "${callbackName}"`, error);
            }
        } else {
            console.warn(`Cookie Consent King: callback "${callbackName}" not found in window scope.`);
        }
        executedCallbackTokens.add(token);
    };

    const restoreScriptNode = (blockedNode, state) => {
        if (!blockedNode || blockedNode.dataset.cckRestored === '1') return;
        const category = blockedNode.dataset.cckConsent;
        if (!isCategoryAllowed(category, state)) return;

        const parent = blockedNode.parentNode;
        if (!parent) return;

        const replacement = document.createElement('script');
        const originalAttrs = blockedNode.dataset.cckOrigAttrs ? parseJSON(blockedNode.dataset.cckOrigAttrs, {}) : {};
        const originalType = blockedNode.dataset.cckOrigType || originalAttrs?.type || '';

        if (blockedNode.dataset.cckSrc) replacement.src = blockedNode.dataset.cckSrc;
        else replacement.textContent = blockedNode.textContent;

        if (originalType) replacement.type = originalType;
        else replacement.removeAttribute('type');

        Object.entries(originalAttrs || {}).forEach(([name, value]) => {
            if (['src', 'type'].includes(name)) return;
            replacement.setAttribute(name, value === '' ? '' : value);
        });

        blockedNode.dataset.cckRestored = '1';
        parent.replaceChild(replacement, blockedNode);

        runConsentCallback(blockedNode.dataset.cckCallback, {
            handle: blockedNode.dataset.cckHandle,
            category,
            type: blockedNode.dataset.cckSrc ? 'external' : 'inline',
        }, state);
    };

    const restoreBlockedScripts = (state) => {
        document.querySelectorAll('script[data-cck-blocked="1"]').forEach(node => restoreScriptNode(node, state));
        (window.cckData?.blockedScripts || []).forEach(item => {
            if (item && isCategoryAllowed(item.category, state)) {
                runConsentCallback(item.callback, item, state);
            }
        });
        document.dispatchEvent(new CustomEvent('cck:consent-applied', { detail: { consent: state } }));
    };

    const syncTogglesWithState = () => {
        document.querySelectorAll('.cck-switch input').forEach(input => {
            const key = input.dataset.consent;
            if (key && key !== 'necessary') {
                input.checked = !!consentState[key];
            }
        });
    };

    const buildBanner = () => {
        resetConsentState();
        const iconHtml = data.icon_url ? `<img src="${data.icon_url}" alt="Icon" class="cck-icon">` : '';
        log('Construyendo banner principal.');

        const helpLinkHtml = testButtonHelpUrl ? `<a href="${testButtonHelpUrl}" class="cck-test-link" target="_blank" rel="noopener noreferrer">${testButtonHelpLabel || testButtonHelpUrl}</a>` : '';
        const showTestControls = Boolean(testButtonLabel);

        bannerContainer.innerHTML = `
            <div id="cck-banner-backdrop"></div>
            <div id="cck-banner" class="cck-banner">
                <div class="cck-tab-nav" role="tablist">
                    <button class="cck-tab-btn cck-active" data-tab="consent" role="tab" aria-selected="true">${texts.consentTab || 'Consentimiento'}</button>
                    <button class="cck-tab-btn" data-tab="details" role="tab" aria-selected="false">${texts.detailsTab || 'Detalles'}</button>
                    <button class="cck-tab-btn" data-tab="about" role="tab" aria-selected="false">${texts.aboutTab || 'Acerca de las cookies'}</button>
                </div>
                <div class="cck-main" data-tab-panel="consent">
                    <div class="cck-tab-panels">
                        <div class="cck-tab-panel cck-active" data-panel="consent">
                            <div class="cck-header">
                                ${iconHtml}
                                <div>
                                    <h2 class="cck-title">${texts.title || 'Política de Cookies'}</h2>
                                    <p class="cck-message">${texts.message || ''}</p>
                                </div>
                            </div>
                            <div class="cck-actions">
                                <button id="cck-reject-btn" class="cck-btn cck-btn-secondary">${texts.rejectAll || 'Rechazar todas'}</button>
                                <button id="cck-personalize-btn" class="cck-btn cck-btn-secondary">${texts.personalize || 'Personalizar'}</button>
                                <button id="cck-accept-btn" class="cck-btn cck-btn-primary">${texts.acceptAll || 'Aceptar todas'}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cck-settings" data-tab-panel="settings" style="display: none;">
                    <div class="cck-settings-header"><h3 class="cck-settings-title">${texts.personalize || 'Personalizar'}</h3><button id="cck-close-btn" class="cck-close-btn">&times;</button></div>
                    <div class="cck-settings-tabs">
                        <button class="cck-settings-tab" data-tab="preferences" type="button">${texts.personalize || 'Personalizar'}</button>
                        <button class="cck-settings-tab" data-tab="details" type="button">${texts.details || 'Detalles'}</button>
                    </div>
                    <div class="cck-tab-content" data-tab="preferences">
                        <div class="cck-options">
                            <div class="cck-option"><label><strong>${texts.necessary || 'Necesario'}</strong> (Siempre activo)</label><label class="cck-switch"><input type="checkbox" data-consent="necessary" checked disabled><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.preferences || 'Preferencias'}</label><label class="cck-switch"><input type="checkbox" data-consent="preferences"><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.analytics || 'Análisis'}</label><label class="cck-switch"><input type="checkbox" data-consent="analytics"><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.marketing || 'Marketing'}</label><label class="cck-switch"><input type="checkbox" data-consent="marketing"><span class="cck-slider"></span></label></div>
                        </div>
                        <div class="cck-actions"><button id="cck-save-btn" class="cck-btn cck-btn-primary">${texts.savePreferences || 'Guardar preferencias'}</button></div>
                    </div>
                    <div class="cck-tab-content" data-tab="details">
                        <div class="cck-cookie-summary"><strong>${texts.totalCookies || 'Cookies detectadas'}:</strong> <span id="cck-cookie-total">0</span></div>
                        <p id="cck-cookie-empty" class="cck-cookie-empty">${texts.noCookiesDetected || 'No se detectaron cookies en esta sesión.'}</p>
                        <div id="cck-cookie-details-list" class="cck-cookie-details-list"></div>
                    </div>
                </div>
                ${showTestControls ? `
                <div class="cck-test-controls">
                    <button id="cck-test-btn" class="cck-btn cck-btn-tertiary" type="button">${testButtonLabel}</button>
                    ${helpLinkHtml}
                </div>` : ''}
            </div>
        `;
        addEventListeners();
        syncTogglesWithState();
        renderCookieSummary();
    };

    const buildReopenTrigger = () => {
        const label = texts.reopenTrigger || texts.personalize || 'Reabrir preferencias';
        const iconMarkup = data.reopen_icon_url ? `<img src="${data.reopen_icon_url}" alt="${label}">` : `<span class="cck-reopen-arrow" aria-hidden="true">↺</span>`;

        reopenContainer.innerHTML = `
            <div id="cck-reopen-trigger" role="button" tabindex="0" aria-label="${label}">
                ${iconMarkup}
                <span class="cck-visually-hidden">${label}</span>
            </div>
        `;
        log('Renderizando disparador para reabrir el banner.');
        const trigger = document.getElementById('cck-reopen-trigger');
        const activateTrigger = () => {
            if (!document.getElementById('cck-banner')) buildBanner();
            setTimeout(showBanner, 50);
        };

        trigger.addEventListener('click', activateTrigger);
        trigger.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                activateTrigger();
            }
        });
        setTimeout(() => trigger?.classList.add('cck-visible'), 100);
    };

    const saveConsent = (action, updatedState) => {
        consentState = { ...consentState, ...updatedState };
        const summary = scanCookies();
        const cookiesByCategory = Object.entries(summary.cookies).reduce((acc, [cat, cookies]) => ({ ...acc, [cat]: [...(cookies || [])] }), {});
        const categoriesMetadata = Object.entries(cookieCategories).reduce((acc, [cat, conf]) => ({ ...acc, [cat]: { label: conf.label, showInDetails: conf.showInDetails !== false } }), {});

        const consentPayload = { ...consentState, detectedCookies: { total: summary.total, counts: { ...summary.counts }, cookiesByCategory, categories: categoriesMetadata } };
        setCookie('cck_consent', JSON.stringify(consentPayload), 365);
        hideBanner();

        log('Guardando consentimiento.', { action, consent: consentPayload });
        if (!document.getElementById('cck-reopen-trigger')) buildReopenTrigger();
        restoreBlockedScripts(consentState);

        const formData = new URLSearchParams({
            action: 'cck_log_consent',
            nonce: data.nonce,
            consent_action: action,
            consent_details: JSON.stringify(consentPayload)
        });

        fetch(data.ajax_url, { method: 'POST', body: formData }).catch(error => console.error('Error logging consent:', error));
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
        log('Limpiando cookies para pruebas manuales.');
        deleteCookie('cck_consent');
        reopenContainer.innerHTML = '';
        buildBanner();
        setTimeout(showBanner, 50);
    };

    const addEventListeners = () => {
        document.getElementById('cck-accept-btn')?.addEventListener('click', () => saveConsent('accept_all', { necessary: true, preferences: true, analytics: true, marketing: true }));
        document.getElementById('cck-reject-btn')?.addEventListener('click', () => saveConsent('reject_all', { necessary: true, preferences: false, analytics: false, marketing: false }));

        const settingsView = document.querySelector('.cck-settings');
        const mainView = document.querySelector('.cck-main');
        const personalizeBtn = document.getElementById('cck-personalize-btn');
        const closeBtn = document.getElementById('cck-close-btn');
        const saveBtn = document.getElementById('cck-save-btn');
        const testBtn = document.getElementById('cck-test-btn');

        const activateTab = (tabName) => {
            settingsView.querySelectorAll('.cck-settings-tab').forEach(btn => btn.classList.toggle('cck-tab-active', btn.dataset.tab === tabName));
            settingsView.querySelectorAll('.cck-tab-content').forEach(content => content.classList.toggle('cck-tab-active', content.dataset.tab === tabName));
            if (tabName === 'details') scanCookies();
        };

        personalizeBtn?.addEventListener('click', () => {
            if (mainView) mainView.style.display = 'none';
            if (settingsView) settingsView.style.display = 'block';
            activateTab('preferences');
        });

        closeBtn?.addEventListener('click', () => {
            if (settingsView) settingsView.style.display = 'none';
            if (mainView) mainView.style.display = 'block';
        });

        settingsView?.querySelectorAll('.cck-settings-tab').forEach(button => {
            button.addEventListener('click', () => activateTab(button.dataset.tab));
        });

        document.querySelectorAll('.cck-switch input').forEach(input => {
            input.addEventListener('change', (e) => {
                if (e.target.dataset.consent !== 'necessary') {
                    consentState[e.target.dataset.consent] = e.target.checked;
                    log(`Preferencia modificada: ${e.target.dataset.consent} -> ${e.target.checked}`);
                }
            });
        });

        saveBtn?.addEventListener('click', () => saveConsent('custom_selection', {}));
        testBtn?.addEventListener('click', resetConsentForTesting);
    };

    const init = () => {
        scanCookies();
        const existingCookie = getCookie('cck_consent');
        const hasConsent = existingCookie !== null;

    const existingCookie = getCookie('cck_consent');
    const shouldForceShow = Boolean(data.forceShow);

    if (existingCookie) {
        const parsed = parseJSON(existingCookie);
        if (parsed) {
            consentState = Object.assign({}, consentState, parsed);
        }
    }

    const shouldDisplayBanner = !existingCookie || shouldForceShow;

    if (shouldDisplayBanner) {
        buildBanner();
        if (shouldForceShow && existingCookie) {
            log('Banner forzado a mostrarse ignorando la cookie previa.');
        }
        setTimeout(showBanner, 100);
    } else {
        try {
            const parsedConsent = JSON.parse(existingCookie);
            ['preferences', 'analytics', 'marketing'].forEach((key) => {
                if (typeof parsedConsent[key] === 'boolean') {
                    consentState[key] = parsedConsent[key];
                }
            });
        } catch (error) {
            console.warn('Cookie Consent King: Unable to parse stored consent.', error);


        if (hasConsent && !data.forceShow) {
            log('Consentimiento previo encontrado. Aplicando scripts.', consentState);
            restoreBlockedScripts(consentState);
            buildReopenTrigger();
        } else {
            log('No hay consentimiento previo o se ha forzado la visualización. Mostrando banner.');
            buildBanner();
            if (hasConsent && data.forceShow) {
                syncTogglesWithState();
            }
            setTimeout(showBanner, 100);
        }
    };

    init();
});
