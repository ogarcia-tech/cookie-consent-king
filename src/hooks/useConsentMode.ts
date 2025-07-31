import { useState, useEffect } from 'react';

interface ConsentSettings {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  preferences: boolean;
}

declare global {
  interface Window {
    gtag?: (...args: any[]) => void;
    dataLayer?: any[];
  }
}

export const useConsentMode = () => {
  const [consent, setConsent] = useState<ConsentSettings | null>(null);
  const [isLoaded, setIsLoaded] = useState(false);

  useEffect(() => {
    // Load saved consent from localStorage
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