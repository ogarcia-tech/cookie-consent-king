document.addEventListener('DOMContentLoaded', () => {
    const data = window.cckData || {};
    if (Object.keys(data).length === 0) {
        console.warn('Cookie Consent King: Data object not found.');
        return;
    }
    
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

    const defaultCookieCategories = {
        necessary: {
            label: texts.necessary || 'Necesario',
            patterns: ['cck_consent', 'wordpress_logged_in_*', 'wp-settings-*', 'wp-settings-time-*'],
            showInDetails: true,
            fallback: false,
        },
        preferences: {
            label: texts.preferences || 'Preferencias',
            patterns: [],
            showInDetails: true,
        },
        analytics: {
            label: texts.analytics || 'Análisis',
            patterns: ['_ga', '_gid', '_gat', '_gcl_au', '__utma', '__utmb', '__utmc', '__utmt', '__utmz'],
            showInDetails: true,
        },
        marketing: {
            label: texts.marketing || 'Marketing',
            patterns: ['_fbp', 'fr', 'IDE', 'test_cookie', 'YSC', 'VISITOR_INFO1_LIVE', 'CONSENT', 'PREF'],
            showInDetails: true,
        },
        uncategorized: {
            label: texts.uncategorized || 'Sin clasificar',
            patterns: [],
            showInDetails: true,
            fallback: true,
        }
    };

    const mergeCookieCategories = () => {
        const categoriesFromData = data.cookieCategories || {};
        const merged = {};
        Object.entries(defaultCookieCategories).forEach(([key, value]) => {
            merged[key] = {
                ...value,
                patterns: [...(value.patterns || [])]
            };
        });

        Object.entries(categoriesFromData).forEach(([key, value]) => {
            const base = merged[key] || {};
            const normalizedValue = (value && typeof value === 'object') ? value : {};
            const overridePatterns = Array.isArray(normalizedValue.patterns) ? normalizedValue.patterns : null;
            merged[key] = {
                ...base,
                ...normalizedValue,
                patterns: overridePatterns ? [...overridePatterns] : [...(base.patterns || [])]
            };
        });

        if (!Object.values(merged).some(category => category.fallback)) {
            merged.uncategorized = merged.uncategorized || {
                label: texts.uncategorized || 'Sin clasificar',
                patterns: [],
                showInDetails: true,
                fallback: true,
            };
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
        const escaped = pattern.replace(/[.*+?^${}()|[\]\]/g, '\$&').replace(/\\\*/g, '.*');
        return new RegExp(`^${escaped}$`, 'i');
    };

    const patternMatches = (pattern, cookieName) => {
        if (!pattern) return false;
        if (pattern instanceof RegExp) {
            return pattern.test(cookieName);
        }
        if (typeof pattern === 'string') {
            if (pattern.includes('*')) {
                return wildcardToRegExp(pattern).test(cookieName);
            }
            return pattern.toLowerCase() === cookieName.toLowerCase();
        }
        return false;
    };

    const getFallbackCategoryKey = () => {
        const fallbackEntry = Object.entries(cookieCategories).find(([, config]) => config.fallback);
        return fallbackEntry ? fallbackEntry[0] : Object.keys(cookieCategories)[0];
    };

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
            const labelElement = document.createElement('span');
            labelElement.className = 'cck-cookie-detail-label';
            labelElement.textContent = label;
            const countElement = document.createElement('span');
            countElement.className = 'cck-cookie-detail-count';
            countElement.textContent = count.toString();
            row.append(labelElement, countElement);
            listElement.appendChild(row);
        });

        if (emptyElement) {
            if (!cookieSummary.total && hasVisibleCategory) {
                emptyElement.style.display = 'block';
            } else {
                emptyElement.style.display = 'none';
            }
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
                    <div class="cck-settings-tabs">
                        <button class="cck-settings-tab" data-tab="preferences" type="button">${texts.personalize || 'Personalizar'}</button>
                        <button class="cck-settings-tab" data-tab="details" type="button">${texts.details || 'Detalles'}</button>
                    </div>
                    <div class="cck-tab-content" data-tab="preferences">
                        <div class="cck-options">
                            <div class="cck-option"><label><strong>${texts.necessary || 'Necesario'}</strong> (Siempre activo)</label><label class="cck-switch"><input type="checkbox" data-consent="necessary" checked disabled><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.preferences || 'Preferencias'}</label><label class="cck-switch"><input type="checkbox" data-consent="preferences" ${consentState.preferences ? 'checked' : ''}><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.analytics || 'Análisis'}</label><label class="cck-switch"><input type="checkbox" data-consent="analytics" ${consentState.analytics ? 'checked' : ''}><span class="cck-slider"></span></label></div>
                            <div class="cck-option"><label>${texts.marketing || 'Marketing'}</label><label class="cck-switch"><input type="checkbox" data-consent="marketing" ${consentState.marketing ? 'checked' : ''}><span class="cck-slider"></span></label></div>
                        </div>
                        <div class="cck-actions"><button id="cck-save-btn" class="cck-btn cck-btn-primary">${texts.savePreferences || 'Guardar preferencias'}</button></div>
                    </div>
                    <div class="cck-tab-content" data-tab="details">
                        <div class="cck-cookie-summary"><strong>${texts.totalCookies || 'Cookies detectadas'}:</strong> <span id="cck-cookie-total">0</span></div>
                        <p id="cck-cookie-empty" class="cck-cookie-empty">${texts.noCookiesDetected || 'No se detectaron cookies en esta sesión.'}</p>
                        <div id="cck-cookie-details-list" class="cck-cookie-details-list"></div>
                    </div>
                </div>
            </div>
        `;
        addEventListeners();
        renderCookieSummary();
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

    const saveConsent = (action, updatedState) => {
        if (updatedState) {
            consentState = { ...consentState, ...updatedState };
        }

        const summary = scanCookies();
        const cookiesByCategory = Object.entries(summary.cookies).reduce((accumulator, [category, cookies]) => {
            accumulator[category] = Array.isArray(cookies) ? [...cookies] : [];
            return accumulator;
        }, {});

        const categoriesMetadata = Object.entries(cookieCategories).reduce((accumulator, [category, config]) => {
            accumulator[category] = {
                label: config.label,
                showInDetails: config.showInDetails !== false,
            };
            return accumulator;
        }, {});

        const consentPayload = {
            ...consentState,
            detectedCookies: {
                total: summary.total,
                counts: { ...summary.counts },
                cookiesByCategory,
                categories: categoriesMetadata,
            }
        };

        setCookie('cck_consent', JSON.stringify(consentPayload), 365);
        scanCookies();
        hideBanner();
        if (!document.getElementById('cck-reopen-trigger')) {
            buildReopenTrigger();
        }

        const formData = new URLSearchParams();
        formData.append('action', 'cck_log_consent');
        formData.append('nonce', data.nonce);
        formData.append('consent_action', action);
        formData.append('consent_details', JSON.stringify(consentPayload));
        fetch(data.ajax_url, {
            method: 'POST',
            body: formData
        }).catch(error => console.error('Error logging consent:', error));
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
            renderCookieSummary();
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

        const tabButtons = document.querySelectorAll('.cck-settings-tab');
        const tabContents = document.querySelectorAll('.cck-tab-content');

        const activateTab = (tabName) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.tab === tabName;
                button.classList.toggle('cck-tab-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            tabContents.forEach((content) => {
                const isActive = content.dataset.tab === tabName;
                content.classList.toggle('cck-tab-active', isActive);
            });
            if (tabName === 'details') {
                scanCookies();
            }
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => activateTab(button.dataset.tab));
        });

        activateTab('preferences');

        document.getElementById('cck-save-btn')?.addEventListener('click', () => saveConsent('custom_selection'));
    };

    scanCookies();

    const existingCookie = getCookie('cck_consent');
    if (!existingCookie) {
        buildBanner();
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
        }
        buildReopenTrigger();
    }
});
