import { cookieManager, CookieManager } from '../cookieManager';
import { describe, it, expect, beforeEach, vi } from 'vitest';

const sampleConsent = {
  necessary: true,
  analytics: true,
  marketing: false,
  preferences: true,
};

describe('cookieManager', () => {
  beforeEach(() => {
    localStorage.clear();
    cookieManager.resetConsent();
  });

  it('updateConsent stores consent and dispatches event', () => {
    const handler = vi.fn();
    window.addEventListener('consentUpdated', handler);
    cookieManager.updateConsent(sampleConsent);

    expect(cookieManager.getConsent()).toEqual(sampleConsent);
    expect(localStorage.getItem('cookieConsent')).toEqual(JSON.stringify(sampleConsent));
    expect(localStorage.getItem('cookieConsentDate')).not.toBeNull();
    expect(handler).toHaveBeenCalled();
    window.removeEventListener('consentUpdated', handler);
  });

  it('resetConsent clears consent and dispatches event', () => {
    cookieManager.updateConsent(sampleConsent);
    const handler = vi.fn();
    window.addEventListener('consentReset', handler);
    cookieManager.resetConsent();

    expect(cookieManager.getConsent()).toBeNull();
    expect(localStorage.getItem('cookieConsent')).toBeNull();
    expect(localStorage.getItem('cookieConsentDate')).toBeNull();
    expect(handler).toHaveBeenCalled();
    window.removeEventListener('consentReset', handler);
  });

  it('onConsentChange notifies listeners and allows unsubscribe', () => {
    const listener = vi.fn();
    const unsubscribe = cookieManager.onConsentChange(listener);

    cookieManager.updateConsent(sampleConsent);
    expect(listener).toHaveBeenCalledWith(sampleConsent);

    listener.mockClear();
    unsubscribe();
    cookieManager.updateConsent({ ...sampleConsent, marketing: true });
    expect(listener).not.toHaveBeenCalled();
  });

  it('initializeConsentMode sets default consent in gtag', () => {
    const gtagSpy = vi.fn();
    // @ts-expect-error test only
    window.gtag = gtagSpy;

    cookieManager.initializeConsentMode();

    expect(gtagSpy).toHaveBeenCalledWith('consent', 'default', {
      ad_storage: 'denied',
      ad_user_data: 'denied',
      ad_personalization: 'denied',
      analytics_storage: 'denied',
      functionality_storage: 'denied',
      personalization_storage: 'denied',
      security_storage: 'granted',
      wait_for_update: 500,
    });

    // @ts-expect-error cleanup
    delete window.gtag;
  });

  it('updateConfig merges new configuration', () => {
    const originalConfig = cookieManager.getConfig();
    cookieManager.updateConfig({ gtmId: 'GTM-TEST', position: 'top' });

    expect(cookieManager.getConfig()).toMatchObject({
      gtmId: 'GTM-TEST',
      position: 'top',
    });

    cookieManager.updateConfig(originalConfig);
  });

  it('handles localStorage errors when loading consent', () => {
    const getItemSpy = vi
      .spyOn(Storage.prototype, 'getItem')
      .mockImplementation(() => {
        throw new Error('failure');
      });
    const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

    // @ts-expect-error accessing private method for test
    cookieManager.loadSavedConsent();

    expect(errorSpy).toHaveBeenCalled();

    getItemSpy.mockRestore();
    errorSpy.mockRestore();
  });

  it('getConsentDate returns stored date and null after reset', () => {
    cookieManager.updateConsent(sampleConsent);
    const date = cookieManager.getConsentDate();
    expect(date).toBeInstanceOf(Date);
    expect(date!.toISOString()).toEqual(
      localStorage.getItem('cookieConsentDate')
    );

    cookieManager.resetConsent();
    expect(cookieManager.getConsentDate()).toBeNull();
  });
});
