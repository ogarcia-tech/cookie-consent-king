
import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
            // Settings panel with tabs
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

              <Tabs defaultValue="consentimiento" className="w-full">
                <TabsList className="grid w-full grid-cols-3">
                  <TabsTrigger value="consentimiento">Consentimiento</TabsTrigger>
                  <TabsTrigger value="detalles">Detalles</TabsTrigger>
                  <TabsTrigger value="acerca">Acerca de las cookies</TabsTrigger>
                </TabsList>

                {/* Tab Content: Consentimiento */}
                <TabsContent value="consentimiento" className="space-y-4 mt-4">
                  <div className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                      Utilizamos cookies propias y de terceros con el fin de analizar y comprender el uso que haces de nuestro sitio web para 
                      hacerlo más intuitivo y para mostrarte publicidad personalizada con base en un perfil elaborado a partir las páginas 
                      webs que visitas y los productos y servicios por los que te interesas.
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Puedes aceptar todas las cookies pulsando el botón "Aceptar", rechazar todas las cookies pulsando sobre el botón "Rechazar" o 
                      configurarlas su uso pulsando el botón "Configuración de cookies".
                    </p>
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
                        Permitir selección
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
                          <div className="flex items-center gap-2">
                            <h4 className="font-medium text-sm">Necesario</h4>
                            <Badge variant="secondary" className="text-xs">55</Badge>
                          </div>
                          <p className="text-xs text-muted-foreground">
                            Las cookies necesarias ayudan a hacer una página web utilizable activando funciones básicas como la navegación en 
                            la página y el acceso a áreas seguras de la página web. La página web no puede funcionar adecuadamente sin estas cookies.
                          </p>
                        </div>
                      </div>
                      <Switch checked={true} disabled />
                    </div>

                    {/* Preferences Cookies */}
                    <div className="flex items-center justify-between p-3 rounded-lg border">
                      <div className="flex items-start gap-3 flex-1">
                        <Settings className="w-5 h-5 text-muted-foreground mt-0.5" />
                        <div className="space-y-1">
                          <div className="flex items-center gap-2">
                            <h4 className="font-medium text-sm">Preferencias</h4>
                            <Badge variant="outline" className="text-xs">13</Badge>
                          </div>
                          <p className="text-xs text-muted-foreground">
                            Las cookies de preferencias permiten a la página web recordar información que cambia la forma en que la página se 
                            comporta o el aspecto que tiene, como su idioma preferido o la región en la que usted se encuentra.
                          </p>
                        </div>
                      </div>
                      <Switch
                        checked={consent.preferences}
                        onCheckedChange={() => toggleConsent('preferences')}
                      />
                    </div>

                    {/* Analytics Cookies */}
                    <div className="flex items-center justify-between p-3 rounded-lg border">
                      <div className="flex items-start gap-3 flex-1">
                        <BarChart3 className="w-5 h-5 text-primary mt-0.5" />
                        <div className="space-y-1">
                          <div className="flex items-center gap-2">
                            <h4 className="font-medium text-sm">Estadística</h4>
                            <Badge variant="outline" className="text-xs">39</Badge>
                          </div>
                          <p className="text-xs text-muted-foreground">
                            Las cookies estadísticas ayudan a los propietarios de páginas web a comprender cómo interactúan los visitantes con las páginas web reuniendo y proporcionando información de forma anónima.
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
                          <div className="flex items-center gap-2">
                            <h4 className="font-medium text-sm">Marketing</h4>
                            <Badge variant="outline" className="text-xs">25</Badge>
                          </div>
                          <p className="text-xs text-muted-foreground">
                            Las cookies de marketing se utilizan para rastrear a los visitantes en las páginas web. La intención es mostrar anuncios relevantes y atractivos para el usuario individual.
                          </p>
                        </div>
                      </div>
                      <Switch
                        checked={consent.marketing}
                        onCheckedChange={() => toggleConsent('marketing')}
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
                        Permitir selección
                      </Button>
                    </div>
                  </div>
                </TabsContent>

                {/* Tab Content: Acerca de las cookies */}
                <TabsContent value="acerca" className="space-y-4 mt-4">
                  <div className="space-y-4 text-sm text-muted-foreground">
                    <p>
                      Las cookies son ficheros que se almacenan en los dispositivos que utilizan los usuarios, como un ordenador, un teléfono 
                      móvil o una tableta, cuando se accede desde éstos a una plataforma, aplicación informática ("app") o una página web, y 
                      sirven para almacenar y recuperar información relativa a la navegación de los usuarios a través de sus dispositivos para 
                      después utilizarla con distintas finalidades.
                    </p>
                    <p>
                      Las referencias realizadas en la presente Política de Cookies al término "cookies" deben entenderse hechas a cualquier tipo 
                      de dispositivo de almacenamiento y recuperación de datos que se utilice en el equipo terminal de los usuarios, abarcando, 
                      por tanto, tanto las cookies como cualquier otro tipo de tecnología similar.
                    </p>
                    <p>
                      El uso de esta tecnología conlleva el tratamiento de datos personales cuando: (i) el usuario esté identificado por un nombre o 
                      dirección de email (habitualmente, por tratarse de un usuario registrado en una página web a la que accede), (ii) almacenen 
                      la dirección IP del dispositivo desde el que se accede, o (iii) en aquellos casos en los que se utilicen identificadores únicos 
                      que permitan distinguir a unos usuarios de otros y realizar un seguimiento individualizado de los mismos (por ejemplo, un ID 
                      de publicidad).
                    </p>
                    <p>
                      En la página web <span className="text-primary font-medium">www.laboralkutxa.com</span> utilizamos cookies propias y de terceros según la descripción que se realiza a 
                      continuación, siempre que nos lo hayas permitido o resulte necesario para que esta página web pueda funcionar 
                      correctamente.
                    </p>
                  </div>

                  <Separator />

                  <div className="flex flex-col sm:flex-row gap-2 justify-between">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={acceptNecessary}
                    >
                      Rechazar
                    </Button>
                    
                    <Button
                      variant="cookie"
                      size="sm"
                      onClick={acceptAll}
                    >
                      Aceptar
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
