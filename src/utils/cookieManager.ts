/**
 * Cookie Manager - Sistema unificado de gestión de cookies
 * Compatible con GDPR y Google Consent Mode v2
*/

import type { ConsentSettings } from '@/types/consent';
export type { ConsentSettings } from '@/types/consent';

export interface CookieManagerConfig {
  gtmId?: string;
  position?: string;
}

type DataLayerEvent = Record<string, unknown>;

interface DataLayer extends Array<DataLayerEvent> {
  push: (...args: DataLayerEvent[]) => number;
}

declare global {
  interface Window {
    gtag?: (...args: unknown[]) => void;
    dataLayer?: unknown[];

  }
}

export class CookieManager {
  private static instance: CookieManager;
  private consent: ConsentSettings | null = null;
  private config: CookieManagerConfig;
  private listeners: Array<(consent: ConsentSettings) => void> = [];

  private constructor(config: CookieManagerConfig = {}) {
    this.config = { ...config };
    this.loadConsent();
  }

  public static getInstance(config?: CookieManagerConfig): CookieManager {
    if (!CookieManager.instance) {
      CookieManager.instance = new CookieManager(config);
    } else if (config) {
      CookieManager.instance.setConfig(config);
    }
    return CookieManager.instance;
  }

  public loadConsent(): ConsentSettings | null {
    try {
      const savedConsent = localStorage.getItem('cookieConsent');
      if (savedConsent) {
        this.consent = JSON.parse(savedConsent);
        this.updateGoogleConsentMode(this.consent!);
        return this.consent;
      }
      this.initializeConsentMode();
    } catch (error) {
      console.error('Error loading saved consent:', error);
    }
    return null;
  }

  public getConsent(): ConsentSettings | null {
    return this.consent;
  }

  public hasConsent(): boolean {
    return this.consent !== null;
  }

  public hasConsentType(type: keyof ConsentSettings): boolean {
    return this.consent ? this.consent[type] : false;
  }

  public saveConsent(newConsent: ConsentSettings, action: string = 'custom'): void {
    this.consent = newConsent;

    if (typeof window !== 'undefined') {
      try {
        localStorage.setItem('cookieConsent', JSON.stringify(newConsent));
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
      } catch (error) {
        console.error('Error saving consent:', error);
      }
    }

    // Actualizar Google Consent Mode
    this.updateGoogleConsentMode(newConsent);

    // Enviar evento al dataLayer
    this.pushDataLayerEvent(newConsent, action);

    // Notificar a los listeners
    this.notifyListeners(newConsent);

    // Disparar evento personalizado
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('consentUpdated', {
        detail: { consent: newConsent, action }
      }));
    }
  }

  public resetConsent(): void {

    localStorage.removeItem('cookieConsent');
    localStorage.removeItem('cookieConsentDate');
    const resetConsent: ConsentSettings = {
      necessary: false,
      analytics: false,
      marketing: false,
      preferences: false,
    };
    this.consent = null;

    // Notificar a los listeners del reinicio
    this.notifyListeners(resetConsent);

    // Limpiar listeners registrados
    this.clearListeners();


    // Notificar reset
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('consentReset'));
    }
  }

  public clearListeners(): void {
    this.listeners = [];
  }

  public onChange(listener: (consent: ConsentSettings) => void): () => void {
    this.listeners.push(listener);
    
    // Devolver función para desuscribirse
    return () => {
      const index = this.listeners.indexOf(listener);
      if (index > -1) {
        this.listeners.splice(index, 1);
      }
    };
  }

  private notifyListeners(consent: ConsentSettings): void {
    this.listeners.forEach(listener => {
      try {
        listener(consent);
      } catch (error) {
        console.error('Error in consent listener:', error);
      }
    });
  }

  private updateGoogleConsentMode(consent: ConsentSettings): void {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('consent', 'update', {
        'ad_storage': consent.marketing ? 'granted' : 'denied',
        'ad_user_data': consent.marketing ? 'granted' : 'denied',
        'ad_personalization': consent.marketing ? 'granted' : 'denied',
        'analytics_storage': consent.analytics ? 'granted' : 'denied',
        'functionality_storage': consent.preferences ? 'granted' : 'denied',
        'personalization_storage': consent.preferences ? 'granted' : 'denied',
      });
    }
  }

  private pushDataLayerEvent(consent: ConsentSettings, action: string): void {
    if (typeof window !== 'undefined') {
      window.dataLayer = window.dataLayer || [];
      
      const eventData = {
        'event': 'consent_update',
        'consent': {
          'ad_storage': consent.marketing ? 'granted' : 'denied',
          'analytics_storage': consent.analytics ? 'granted' : 'denied',
          'functionality_storage': consent.preferences ? 'granted' : 'denied',
          'personalization_storage': consent.preferences ? 'granted' : 'denied',
          'security_storage': 'granted',
          'ad_user_data': consent.marketing ? 'granted' : 'denied',
          'ad_personalization': consent.marketing ? 'granted' : 'denied'
        },
        'consent_action': action,
        'timestamp': new Date().toISOString()
      };

      window.dataLayer.push(eventData);
    }
  }

  public initializeConsentMode(): void {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted',
        'wait_for_update': 500,
      });
    }
    this.loadGtmScript();
  }

  public getConsentDate(): Date | null {
    if (typeof window !== 'undefined') {
      try {
        const dateString = localStorage.getItem('cookieConsentDate');
        return dateString ? new Date(dateString) : null;
      } catch (error) {
        console.error('Error getting consent date:', error);
        return null;
      }
    }
    return null;
  }

  public setConfig(newConfig: Partial<CookieManagerConfig>): void {
    this.config = { ...this.config, ...newConfig };
    if (newConfig.gtmId) {
      this.loadGtmScript();
    }
  }

  public updateConfig(newConfig: Partial<CookieManagerConfig>): void {
    this.setConfig(newConfig);
  }

  public getConfig(): CookieManagerConfig {
    return { ...this.config };
  }

  private loadGtmScript(): void {
    if (typeof document === 'undefined' || typeof window === 'undefined') {
      return;
    }
    const { gtmId } = this.config;
    if (!gtmId) {
      return;
    }
    if (!document.querySelector(`script[src*="${gtmId}"]`)) {
      const script = document.createElement('script');
      script.async = true;
      script.src = `https://www.googletagmanager.com/gtm.js?id=${gtmId}`;
      document.head.appendChild(script);

      const noscript = document.createElement('noscript');
      const iframe = document.createElement('iframe');
      iframe.src = `https://www.googletagmanager.com/ns.html?id=${gtmId}`;
      iframe.height = '0';
      iframe.width = '0';
      iframe.style.display = 'none';
      iframe.style.visibility = 'hidden';
      noscript.appendChild(iframe);
      document.body.appendChild(noscript);
    }
  }
}

// Export para uso directo
export const cookieManager = CookieManager.getInstance();
