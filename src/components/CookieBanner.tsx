
import React, { useState, useEffect, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Label } from '@/components/ui/label';
import { Cookie, Settings, Shield, BarChart3, Target, X } from 'lucide-react';
import { t } from '@/utils/i18n';

interface ConsentSettings {
  necessary: boolean;
  analytics: boolean;
  marketing: boolean;
  preferences: boolean;
}

interface CookieBannerProps {
  onConsentUpdate?: (consent: ConsentSettings) => void;
  forceShow?: boolean; // Nueva prop para forzar mostrar el banner en demo
  cookiePolicyUrl?: string; // URL personalizable para la política de cookies
  aboutCookiesUrl?: string; // URL personalizable para información detallada sobre cookies
}

// Consent Mode v2 integration
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

const CookieBanner: React.FC<CookieBannerProps> = ({ onConsentUpdate, forceShow = false, cookiePolicyUrl, aboutCookiesUrl }) => {
  const [showBanner, setShowBanner] = useState(false);
  const [showSettings, setShowSettings] = useState(false);
  const [showMiniBanner, setShowMiniBanner] = useState(false);
  const [consent, setConsent] = useState<ConsentSettings>({
    necessary: true, // Always true
    analytics: false,
    marketing: false,
    preferences: false,
  });

  useEffect(() => {
    // Check if user has already made a choice
    let savedConsent: string | null = null;

    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        savedConsent = window.localStorage.getItem('cookieConsent');
      } catch (error) {
        if (import.meta.env.DEV) {
          console.error('CookieBanner: Error accessing localStorage', error);
        }
      }
    }

    if (import.meta.env.DEV) {
      console.log('CookieBanner: checking saved consent', savedConsent);
    }

    if (!savedConsent) {
      if (import.meta.env.DEV) {
        console.log('CookieBanner: No saved consent, showing banner');
      }
      setShowBanner(true);
      setShowMiniBanner(false);
      // Initialize Google Consent Mode v2 with default values
      initializeConsentMode();
    } else {
      if (import.meta.env.DEV) {
        console.log('CookieBanner: Found saved consent, parsing and applying');
      }
      try {
        const parsedConsent = JSON.parse(savedConsent);
        setConsent(parsedConsent);
        updateConsentMode(parsedConsent);
        setShowBanner(false);
        setShowMiniBanner(true); // Show mini banner when consent exists
      } catch (error) {
        if (import.meta.env.DEV) {
          console.error('CookieBanner: Error parsing saved consent', error);
        }
        if (typeof window !== 'undefined' && window.localStorage) {
          try {
            window.localStorage.removeItem('cookieConsent');
            window.localStorage.removeItem('cookieConsentDate');
          } catch (cleanupError) {
            if (import.meta.env.DEV) {
              console.error('CookieBanner: Error cleaning corrupt consent', cleanupError);
            }
          }
        }
        setConsent({
          necessary: true,
          analytics: false,
          marketing: false,
          preferences: false,
        });
        setShowBanner(true);
        setShowMiniBanner(false);
        initializeConsentMode();
      }
    }
  }, [updateConsentMode]);

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

      if (import.meta.env.DEV) {
        console.log('Pushing to dataLayer:', eventData);
      }
      window.dataLayer.push(eventData);
    }
  };

  const updateConsentMode = useCallback(
    (consentSettings: ConsentSettings, action?: string) => {
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
    },
    [onConsentUpdate]
  );

  const saveConsent = (consentSettings: ConsentSettings, action: string) => {
    if (import.meta.env.DEV) {
      console.log('Saving consent:', consentSettings, 'Action:', action);
    }
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        window.localStorage.setItem('cookieConsent', JSON.stringify(consentSettings));
        window.localStorage.setItem('cookieConsentDate', new Date().toISOString());
      } catch (error) {
        if (import.meta.env.DEV) {
          console.error('CookieBanner: Error saving consent', error);
        }
      }
    }

    if (typeof window !== 'undefined') {
      // Dispatch custom event to notify other components
      window.dispatchEvent(new CustomEvent('consentUpdated', { detail: consentSettings }));
    }

    // Update consent mode and push dataLayer event
    updateConsentMode(consentSettings, action);

    setConsent(consentSettings);
    setShowBanner(false);
    setShowSettings(false);
    setShowMiniBanner(true); // Show mini banner after consent
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

  const isAllAccepted = () => {
    return consent.necessary && consent.analytics && consent.marketing && consent.preferences;
  };

  const reopenBanner = () => {
    setShowBanner(true);
    setShowMiniBanner(false);
    setShowSettings(false);
  };

  // Show banner if forceShow is true OR if showBanner state is true
  if (!forceShow && !showBanner) {
    return showMiniBanner ? (
      // Mini floating cookie icon - simple and fixed positioning
      <div className="fixed bottom-6 left-6 z-[9999]">
        <button
          onClick={reopenBanner}
          className="w-14 h-14 bg-primary hover:bg-primary/90 text-primary-foreground rounded-full shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 flex items-center justify-center border-2 border-white/20"
          title={t('Gestionar cookies')}
          aria-label={t('Abrir configuración de cookies')}
        >
          <Cookie className="w-7 h-7" />
        </button>
      </div>
    ) : null;
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
                      {t('Gestión de Cookies')}
                    </h3>
                    <p className="text-sm text-muted-foreground leading-relaxed">
                      {t('Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico. También compartimos información sobre tu uso de nuestro sitio con nuestros socios de análisis y publicidad.')}
                    </p>
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
                  {t('Personalizar')}
                </Button>
                
                <div className="flex gap-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={acceptNecessary}
                  >
                    {t('Rechazar todas')}
                  </Button>
                  {!isAllAccepted() && (
                    <Button
                      variant="cookie"
                      size="sm"
                      onClick={acceptAll}
                    >
                      {t('Aceptar todas')}
                    </Button>
                  )}
                </div>
              </div>
            </div>
          ) : (
            // Settings panel with tabs
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-foreground">
                  {t('Configuración de Cookies')}
                </h3>
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => setShowSettings(false)}
                >
                  <X className="w-4 h-4" />
                </Button>
              </div>

              <Tabs defaultValue="detalles" className="w-full">
                <TabsList className="grid w-full grid-cols-3">
                  <TabsTrigger value="consentimiento">{t('Consentimiento')}</TabsTrigger>
                  <TabsTrigger value="detalles">{t('Detalles')}</TabsTrigger>
                  <TabsTrigger value="acerca">{t('Acerca de las cookies')}</TabsTrigger>
                </TabsList>

                {/* Tab Content: Consentimiento */}
                <TabsContent value="consentimiento" className="space-y-4 mt-4">
                  <div className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                      {t('Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web para hacerlo más intuitivo y para mostrarte publicidad personalizada con base en un perfil elaborado a partir las páginas webs que visitas y los productos y servicios por los que te interesas.')}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      {t('Puedes aceptar todas las cookies pulsando el botón "Aceptar", rechazar todas las cookies pulsando sobre el botón "Rechazar" o configurarlas su uso pulsando el botón "Configuración de cookies".')}
                      {cookiePolicyUrl && (
                        <>
                          {t('Si deseas más información pulsa en')}{' '}
                          <a
                            href={cookiePolicyUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-primary hover:underline font-medium"
                          >
                            {t('Política de Cookies')}
                          </a>
                          .
                        </>
                      )}
                    </p>
                  </div>

                  <Separator />

                  <div className="flex flex-col sm:flex-row gap-2 justify-between">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={acceptNecessary}
                    >
                      {t('Rechazar todas')}
                    </Button>
                    
                    <div className="flex gap-2">
                      {!isAllAccepted() && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={acceptAll}
                        >
                          {t('Aceptar todas')}
                        </Button>
                      )}
                      <Button
                        variant="cookie"
                        size="sm"
                        onClick={saveCustomSettings}
                      >
                        {t('Permitir selección')}
                      </Button>
                    </div>
                  </div>
                </TabsContent>

                {/* Tab Content: Detalles */}
                <TabsContent value="detalles" className="space-y-4 mt-4">
                  <div className="space-y-4">
                    {/* Necessary Cookies */}
                    <div className="flex items-center justify-between p-3 rounded-lg bg-muted/50">
                      <div className="flex items-start gap-3 flex-1">
                        <Shield className="w-5 h-5 text-success mt-0.5" />
                          <div className="space-y-1">
                            <h4 className="font-medium text-sm">{t('Necesario')}</h4>
                          <p id="necessary-cookies-description" className="text-xs text-muted-foreground">
                            {t('Las cookies necesarias ayudan a hacer una página web utilizable activando funciones básicas como la navegación en la página y el acceso a áreas seguras de la página web. La página web no puede funcionar adecuadamente sin estas cookies.')}
                          </p>
                        </div>
                      </div>
                      <Switch 
                        checked={true} 
                        disabled 
                        aria-label={t('Cookies necesarias (siempre activadas)')}
                        aria-describedby="necessary-cookies-description"
                      />
                    </div>

                    {/* Preferences Cookies */}
                    <label className="flex items-center justify-between p-3 rounded-lg border cursor-pointer">
                      <div className="flex items-start gap-3 flex-1">
                        <Settings className="w-5 h-5 text-muted-foreground mt-0.5" />
                          <div className="space-y-1">
                            <h4 className="font-medium text-sm">{t('Preferencias')}</h4>
                          <p id="preferences-cookies-description" className="text-xs text-muted-foreground">
                            {t('Las cookies de preferencias permiten a la página web recordar información que cambia la forma en que la página se comporta o el aspecto que tiene, como su idioma preferido o la región en la que usted se encuentra.')}
                          </p>
                        </div>
                      </div>
                      <Switch
                        checked={consent.preferences}
                        onCheckedChange={() => toggleConsent('preferences')}
                        aria-label={t('Cookies de preferencias')}
                        aria-describedby="preferences-cookies-description"
                      />
                    </label>

                    {/* Analytics Cookies */}
                    <label className="flex items-center justify-between p-3 rounded-lg border cursor-pointer">
                      <div className="flex items-start gap-3 flex-1">
                        <BarChart3 className="w-5 h-5 text-primary mt-0.5" />
                          <div className="space-y-1">
                            <h4 className="font-medium text-sm">{t('Estadística')}</h4>
                          <p id="analytics-cookies-description" className="text-xs text-muted-foreground">
                            {t('Las cookies estadísticas ayudan a los propietarios de páginas web a comprender cómo interactúan los visitantes con las páginas web reuniendo y proporcionando información de forma anónima.')}
                          </p>
                        </div>
                      </div>
                      <Switch
                        checked={consent.analytics}
                        onCheckedChange={() => toggleConsent('analytics')}
                        aria-label={t('Cookies estadísticas')}
                        aria-describedby="analytics-cookies-description"
                      />
                    </label>

                    {/* Marketing Cookies */}
                    <label className="flex items-center justify-between p-3 rounded-lg border cursor-pointer">
                      <div className="flex items-start gap-3 flex-1">
                        <Target className="w-5 h-5 text-warning mt-0.5" />
                          <div className="space-y-1">
                            <h4 className="font-medium text-sm">{t('Marketing')}</h4>
                          <p id="marketing-cookies-description" className="text-xs text-muted-foreground">
                            {t('Las cookies de marketing se utilizan para rastrear a los visitantes en las páginas web. La intención es mostrar anuncios relevantes y atractivos para el usuario individual.')}
                          </p>
                        </div>
                      </div>
                      <Switch
                        checked={consent.marketing}
                        onCheckedChange={() => toggleConsent('marketing')}
                        aria-label={t('Cookies de marketing')}
                        aria-describedby="marketing-cookies-description"
                      />
                    </label>
                  </div>

                  <Separator />

                  <div className="flex flex-col sm:flex-row gap-2 justify-between">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={acceptNecessary}
                    >
                      {t('Rechazar todas')}
                    </Button>
                    
                    <div className="flex gap-2">
                      {!isAllAccepted() && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={acceptAll}
                        >
                          {t('Aceptar todas')}
                        </Button>
                      )}
                      <Button
                        variant="cookie"
                        size="sm"
                        onClick={saveCustomSettings}
                      >
                        {t('Permitir selección')}
                      </Button>
                    </div>
                  </div>
                </TabsContent>

                {/* Tab Content: Acerca de las cookies */}
                <TabsContent value="acerca" className="space-y-4 mt-4">
                  <div className="space-y-4 text-sm text-muted-foreground">
                    <p>
                      {t('Las cookies son pequeños archivos de texto que se almacenan en el dispositivo del usuario cuando visita un sitio web. Estas cookies contienen información sobre la navegación del usuario y se utilizan para mejorar la funcionalidad del sitio web, personalizar la experiencia del usuario y proporcionar información analítica a los propietarios del sitio.')}
                    </p>
                    <p>
                      <strong>{t('Tipos de cookies que utilizamos:')}</strong>
                    </p>
                    <ul className="list-disc pl-5 space-y-1">
                      <li><strong>{t('Cookies técnicas o necesarias:')}</strong> {t('Son esenciales para el funcionamiento básico del sitio web y no se pueden desactivar.')}</li>
                      <li><strong>{t('Cookies de preferencias:')}</strong> {t('Permiten recordar las configuraciones y preferencias del usuario para mejorar su experiencia.')}</li>
                      <li><strong>{t('Cookies estadísticas:')}</strong> {t('Recopilan información de forma anónima sobre cómo los usuarios interactúan con el sitio web para mejorar su rendimiento.')}</li>
                      <li><strong>{t('Cookies de marketing:')}</strong> {t('Se utilizan para mostrar publicidad relevante y medir la efectividad de las campañas publicitarias.')}</li>
                    </ul>
                    <p>
                      {t('En cumplimiento del Reglamento General de Protección de Datos (RGPD), solicitamos su consentimiento para el uso de cookies no esenciales. Puede gestionar sus preferencias de cookies en cualquier momento accediendo a la configuración de privacidad de nuestro sitio web.')}
                    </p>
                    <p>
                      {t('Para más información sobre nuestra política de privacidad y el tratamiento de datos personales, consulte nuestra política de privacidad completa.')}
                      {aboutCookiesUrl && (
                        <>
                          {' '}{t('Para información detallada sobre cookies, visite')}{' '}
                          <a
                            href={aboutCookiesUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-primary hover:underline font-medium"
                          >
                            {t('Acerca de las Cookies')}
                          </a>
                          .
                        </>
                      )}
                    </p>
                  </div>

                  <Separator />

                  <div className="flex flex-col sm:flex-row gap-2 justify-between">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={acceptNecessary}
                    >
                      {t('Rechazar')}
                    </Button>
                    
                    <Button
                      variant="cookie"
                      size="sm"
                      onClick={acceptAll}
                    >
                      {t('Aceptar')}
                    </Button>
                  </div>
                </TabsContent>
              </Tabs>
            </div>
          )}
        </div>
      </Card>
    </div>
  );
};

export default CookieBanner;
