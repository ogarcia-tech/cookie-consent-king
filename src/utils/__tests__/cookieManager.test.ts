import { cookieManager } from '../cookieManager';
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
});
