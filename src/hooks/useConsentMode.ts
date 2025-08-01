import { useState, useEffect } from 'react';

interface ConsentSettings {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  preferences: boolean;
}

interface Gtag {
  (
    command: 'consent' | 'config' | 'event',
    action: string,
    params?: Record<string, unknown>
  ): void;
  (...args: unknown[]): void;
}

type DataLayerEvent = Record<string, unknown>;

interface DataLayer extends Array<DataLayerEvent> {
  push: (...args: DataLayerEvent[]) => number;
}

declare global {
  interface Window {

    gtag?: (...args: unknown[]) => void;
    dataLayer?: any[];

  }
}

export const useConsentMode = () => {
  const [consent, setConsent] = useState<ConsentSettings | null>(null);
  const [isLoaded, setIsLoaded] = useState(false);

  useEffect(() => {
    const loadConsent = () => {
      const savedConsent = localStorage.getItem('cookieConsent');
      if (savedConsent) {
        try {
          const parsedConsent = JSON.parse(savedConsent);
          setConsent(parsedConsent);
          updateGoogleConsentMode(parsedConsent);
        } catch (error) {
          console.error('Error parsing saved consent:', error);
        }
      }
      setIsLoaded(true);
    };

    // Load initial consent
    loadConsent();

    // Listen for storage changes from other tabs or the cookie banner
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key === 'cookieConsent') {
        loadConsent();
      }
    };

    // Listen for custom consent update events
    const handleConsentUpdate = () => {
      loadConsent();
    };

    window.addEventListener('storage', handleStorageChange);
    window.addEventListener('consentUpdated', handleConsentUpdate);

    return () => {
      window.removeEventListener('storage', handleStorageChange);
      window.removeEventListener('consentUpdated', handleConsentUpdate);
    };
  }, []);

  const updateGoogleConsentMode = (consentSettings: ConsentSettings) => {
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
  };

  const updateConsent = (newConsent: ConsentSettings) => {
    setConsent(newConsent);
    localStorage.setItem('cookieConsent', JSON.stringify(newConsent));
    localStorage.setItem('cookieConsentDate', new Date().toISOString());
    updateGoogleConsentMode(newConsent);
  };

  const resetConsent = () => {
    localStorage.removeItem('cookieConsent');
    localStorage.removeItem('cookieConsentDate');
    setConsent(null);
  };

  const hasConsent = (type: keyof ConsentSettings): boolean => {
    return consent ? consent[type] : false;
  };

  const isConsentGiven = (): boolean => {
    return consent !== null;
  };

  // Get consent timestamp
  const getConsentDate = (): Date | null => {
    const dateString = localStorage.getItem('cookieConsentDate');
    return dateString ? new Date(dateString) : null;
  };

  return {
    consent,
    isLoaded,
    updateConsent,
    resetConsent,
    hasConsent,
    isConsentGiven,
    getConsentDate,
  };
};