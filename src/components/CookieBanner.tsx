
import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Cookie, Settings, Shield, BarChart3, Target, X } from 'lucide-react';

interface ConsentSettings {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  preferences: boolean;
}

interface CookieBannerProps {
  onConsentUpdate?: (consent: ConsentSettings) => void;
  forceShow?: boolean; // Nueva prop para forzar mostrar el banner en demo
}

// Consent Mode v2 integration
declare global {
  interface Window {
    gtag?: (...args: any[]) => void;
    dataLayer?: any[];
  }
}

const CookieBanner: React.FC<CookieBannerProps> = ({ onConsentUpdate, forceShow = false }) => {
  const [showBanner, setShowBanner] = useState(false);
  const [showSettings, setShowSettings] = useState(false);
  const [consent, setConsent] = useState<ConsentSettings>({
    necessary: true, // Always true
    analytics: false,
    marketing: false,
    preferences: false,
  });

  useEffect(() => {
    // Check if user has already made a choice
    const savedConsent = localStorage.getItem('cookieConsent');
    console.log('CookieBanner: checking saved consent', savedConsent);
    
    if (!savedConsent) {
      console.log('CookieBanner: No saved consent, showing banner');
      setShowBanner(true);
      // Initialize Google Consent Mode v2 with default values
      initializeConsentMode();
    } else {
      console.log('CookieBanner: Found saved consent, parsing and applying');
      const parsedConsent = JSON.parse(savedConsent);
      setConsent(parsedConsent);
      updateConsentMode(parsedConsent);
      setShowBanner(false); // Explicitly set to false when consent exists
    }
  }, []);

  const initializeConsentMode = () => {
    if (typeof window !== 'undefined' && window.gtag) {
      // Set default consent state (denied)
      window.gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied',
        'functionality_storage': 'denied',
        'personalization_storage': 'denied',
        'security_storage': 'granted', // Usually granted by default
        'wait_for_update': 500,
      });
    }
  };

  const getConsentAction = (consentSettings: ConsentSettings): string => {
    const { analytics, marketing, preferences } = consentSettings;
    
    if (analytics && marketing && preferences) {
      return 'accept_all';
    } else if (!analytics && !marketing && !preferences) {
      return 'reject_all';
    } else {
      return 'custom_selection';
    }
  };

  const pushDataLayerEvent = (consentSettings: ConsentSettings, action: string) => {
    // Ensure dataLayer exists
    if (typeof window !== 'undefined') {
      window.dataLayer = window.dataLayer || [];
      
      const eventData = {
        'event': 'consent_update',
        'consent': {
          'ad_storage': consentSettings.marketing ? 'granted' : 'denied',
          'analytics_storage': consentSettings.analytics ? 'granted' : 'denied',
          'functionality_storage': consentSettings.preferences ? 'granted' : 'denied',
          'personalization_storage': consentSettings.preferences ? 'granted' : 'denied',
          'security_storage': 'granted', // Always granted
          'ad_user_data': consentSettings.marketing ? 'granted' : 'denied',
          'ad_personalization': consentSettings.marketing ? 'granted' : 'denied'
        },
        'consent_action': action,
        'timestamp': new Date().toISOString()
      };

      console.log('Pushing to dataLayer:', eventData);
      window.dataLayer.push(eventData);
    }
  };

  const updateConsentMode = (consentSettings: ConsentSettings, action?: string) => {
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

    // Push consent_update event to dataLayer
    if (action) {
      pushDataLayerEvent(consentSettings, action);
    }

    onConsentUpdate?.(consentSettings);
  };

  const saveConsent = (consentSettings: ConsentSettings, action: string) => {
    console.log('Saving consent:', consentSettings, 'Action:', action);
    
    localStorage.setItem('cookieConsent', JSON.stringify(consentSettings));
    localStorage.setItem('cookieConsentDate', new Date().toISOString());
    
    // Update consent mode and push dataLayer event
    updateConsentMode(consentSettings, action);
    
    setConsent(consentSettings);
    setShowBanner(false);
    setShowSettings(false);
  };

  const acceptAll = () => {
    const allAccepted = {
      necessary: true,
      analytics: true,
      marketing: true,
      preferences: true,
    };
    saveConsent(allAccepted, 'accept_all');
  };

  const acceptNecessary = () => {
    const necessaryOnly = {
      necessary: true,
      analytics: false,
      marketing: false,
      preferences: false,
    };
    saveConsent(necessaryOnly, 'reject_all');
  };

  const saveCustomSettings = () => {
    saveConsent(consent, 'custom_selection');
  };

  const toggleConsent = (type: keyof ConsentSettings) => {
    if (type === 'necessary') return; // Necessary cookies cannot be disabled
    setConsent(prev => ({
      ...prev,
      [type]: !prev[type]
    }));
  };

  // Show banner if forceShow is true OR if showBanner state is true
  if (!forceShow && !showBanner) {
    return null;
  }

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end justify-center p-4">
      <Card className="w-full max-w-2xl bg-cookie-banner border-cookie-banner-border shadow-floating animate-in slide-in-from-bottom-4 duration-300">
        <div className="p-6">
          {!showSettings ? (
            // Main banner
            <div className="space-y-4">
              <div className="flex items-start gap-3">
                <div className="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                  <Cookie className="w-5 h-5 text-primary" />
                </div>
                <div className="flex-1 space-y-3">
                  <div>
                    <h3 className="text-lg font-semibold text-foreground mb-2">
                      Gestión de Cookies
                    </h3>
                    <p className="text-sm text-muted-foreground leading-relaxed">
                      Utilizamos cookies para mejorar tu experiencia de navegación, 
                      personalizar contenido y anuncios, proporcionar funciones de redes sociales 
                      y analizar nuestro tráfico. También compartimos información sobre tu uso 
                      de nuestro sitio con nuestros socios de análisis y publicidad.
                    </p>
                  </div>
                  
                  <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary" className="flex items-center gap-1">
                      <Shield className="w-3 h-3" />
                      Cumple GDPR
                    </Badge>
                    <Badge variant="outline" className="text-xs">
                      Consent Mode v2
                    </Badge>
                  </div>
                </div>
              </div>

              <Separator />

              <div className="flex flex-col sm:flex-row gap-2 justify-between">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setShowSettings(true)}
                  className="flex items-center gap-2"
                >
                  <Settings className="w-4 h-4" />
                  Personalizar
                </Button>
                
                <div className="flex gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={acceptNecessary}
                  >
                    Rechazar todas
                  </Button>
                  <Button
                    variant="cookie"
                    size="sm"
                    onClick={acceptAll}
                  >
                    Aceptar todas
                  </Button>
                </div>
              </div>
            </div>
          ) : (
            // Settings panel
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-foreground">
                  Configuración de Cookies
                </h3>
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => setShowSettings(false)}
                >
                  <X className="w-4 h-4" />
                </Button>
              </div>

              <div className="space-y-4">
                {/* Necessary Cookies */}
                <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
                  <div className="flex items-start gap-3 flex-1">
                    <Shield className="w-5 h-5 text-success mt-0.5" />
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <h4 className="font-medium text-sm">Cookies Necesarias</h4>
                        <Badge variant="secondary" className="text-xs">Obligatorias</Badge>
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Esenciales para el funcionamiento básico del sitio web.
                      </p>
                    </div>
                  </div>
                  <Switch checked={true} disabled />
                </div>

                {/* Analytics Cookies */}
                <div className="flex items-center justify-between p-3 rounded-lg border">
                  <div className="flex items-start gap-3 flex-1">
                    <BarChart3 className="w-5 h-5 text-primary mt-0.5" />
                    <div className="space-y-1">
                      <h4 className="font-medium text-sm">Cookies de Análisis</h4>
                      <p className="text-xs text-muted-foreground">
                        Nos ayudan a entender cómo interactúas con nuestro sitio web.
                      </p>
                    </div>
                  </div>
                  <Switch
                    checked={consent.analytics}
                    onCheckedChange={() => toggleConsent('analytics')}
                  />
                </div>

                {/* Marketing Cookies */}
                <div className="flex items-center justify-between p-3 rounded-lg border">
                  <div className="flex items-start gap-3 flex-1">
                    <Target className="w-5 h-5 text-warning mt-0.5" />
                    <div className="space-y-1">
                      <h4 className="font-medium text-sm">Cookies de Marketing</h4>
                      <p className="text-xs text-muted-foreground">
                        Se utilizan para mostrar anuncios relevantes y medir campañas.
                      </p>
                    </div>
                  </div>
                  <Switch
                    checked={consent.marketing}
                    onCheckedChange={() => toggleConsent('marketing')}
                  />
                </div>

                {/* Preferences Cookies */}
                <div className="flex items-center justify-between p-3 rounded-lg border">
                  <div className="flex items-start gap-3 flex-1">
                    <Settings className="w-5 h-5 text-muted-foreground mt-0.5" />
                    <div className="space-y-1">
                      <h4 className="font-medium text-sm">Cookies de Preferencias</h4>
                      <p className="text-xs text-muted-foreground">
                        Guardan tus preferencias de personalización y configuración.
                      </p>
                    </div>
                  </div>
                  <Switch
                    checked={consent.preferences}
                    onCheckedChange={() => toggleConsent('preferences')}
                  />
                </div>
              </div>

              <Separator />

              <div className="flex flex-col sm:flex-row gap-2 justify-between">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={acceptNecessary}
                >
                  Rechazar todas
                </Button>
                
                <div className="flex gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={acceptAll}
                  >
                    Aceptar todas
                  </Button>
                  <Button
                    variant="cookie"
                    size="sm"
                    onClick={saveCustomSettings}
                  >
                    Guardar configuración
                  </Button>
                </div>
              </div>
            </div>
          )}
        </div>
      </Card>
    </div>
  );
};

export default CookieBanner;
