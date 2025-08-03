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
    dataLayer?: DataLayer;

  }
}

export const useConsentMode = () => {
  const [consent, setConsent] = useState<ConsentSettings | null>(null);
  const [isLoaded, setIsLoaded] = useState(false);

  useEffect(() => {
    const loadConsent = () => {
      if (typeof window === 'undefined') {
        return;
      }
      try {
        const savedConsent = window.localStorage.getItem('cookieConsent');
        if (savedConsent) {
          const parsedConsent = JSON.parse(savedConsent);
          setConsent(parsedConsent);
          updateGoogleConsentMode(parsedConsent);
        }
      } catch (error) {
        console.error('Error loading saved consent:', error);
      }
    };

    // Load initial consent
    loadConsent();
    setIsLoaded(true);

    if (typeof window === 'undefined') {
      return;
    }

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
    if (typeof window !== 'undefined') {
      try {
        window.localStorage.setItem('cookieConsent', JSON.stringify(newConsent));
        window.localStorage.setItem('cookieConsentDate', new Date().toISOString());
      } catch (error) {
        console.error('Error saving consent:', error);
      }
    }
    updateGoogleConsentMode(newConsent);
  };

  const resetConsent = () => {
    if (typeof window !== 'undefined') {
      try {
        window.localStorage.removeItem('cookieConsent');
        window.localStorage.removeItem('cookieConsentDate');
      } catch (error) {
        console.error('Error clearing consent:', error);
      }
    }
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
    if (typeof window === 'undefined') {
      return null;
    }
    try {
      const dateString = window.localStorage.getItem('cookieConsentDate');
      return dateString ? new Date(dateString) : null;
    } catch (error) {
      console.error('Error reading consent date:', error);
      return null;
    }
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