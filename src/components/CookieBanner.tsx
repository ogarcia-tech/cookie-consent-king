
import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Label } from '@/components/ui/label';
import { Cookie, Settings, Shield, BarChart3, Target, X } from 'lucide-react';
import { t } from '@/utils/i18n';
import type { ConsentSettings } from '@/types/consent';
import { cookieManager } from '@/utils/cookieManager';

interface CookieBannerProps {
  onConsentUpdate?: (consent: ConsentSettings) => void;
  forceShow?: boolean; // Nueva prop para forzar mostrar el banner en demo
  cookiePolicyUrl?: string; // URL personalizable para la política de cookies
  aboutCookiesUrl?: string; // URL personalizable para información detallada sobre cookies
}


// Consent Mode v2 integration
declare global {
  interface Window {
    gtag?: (...args: unknown[]) => void;
    dataLayer?: unknown[];
  }
}


const CookieBanner: React.FC<CookieBannerProps> = ({ onConsentUpdate, forceShow = false, cookiePolicyUrl, aboutCookiesUrl }) => {
  const [showBanner, setShowBanner] = useState(false);
  const [showSettings, setShowSettings] = useState(false);
  const [showMiniBanner, setShowMiniBanner] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [consent, setConsent] = useState<ConsentSettings>({
    necessary: true, // Always true
    analytics: false,
    marketing: false,
    preferences: false,
  });

  const cckData =
    typeof window !== 'undefined' ? ((window as any).cckData || {}) : {};
  const { styles = {}, texts = {}, urls = {} } = cckData;
  const bannerStyle: React.CSSProperties = {
    backgroundColor: styles.bg_color || undefined,
    color: styles.text_color || undefined,
  };
  const heading = texts.title || t('Gestión de Cookies');
  const defaultMessage =
    'Utilizamos cookies para mejorar tu experiencia de navegación, personalizar contenido y anuncios, proporcionar funciones de redes sociales y analizar nuestro tráfico. También compartimos información sobre tu uso de nuestro sitio con nuestros socios de análisis y publicidad.';
  const message = texts.message || t(defaultMessage);
  const cookiePolicyUrlResolved = urls.cookiePolicy || cookiePolicyUrl;
  const aboutCookiesUrlResolved = urls.aboutCookies || aboutCookiesUrl;

  useEffect(() => {
    const handleResize = () => {
      if (typeof window !== 'undefined') {
        setIsMobile(window.innerWidth < 640);
      }
    };
    handleResize();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  useEffect(() => {

    const savedConsent = cookieManager.getConsent();

    if (!savedConsent) {
      setShowBanner(true);
      setShowMiniBanner(false);
      cookieManager.initializeConsentMode();
    } else {
      setConsent(savedConsent);
      setShowBanner(false);
      setShowMiniBanner(true);
      onConsentUpdate?.(savedConsent);
    }
  }, [onConsentUpdate]);

  const logAction = (action: string) => {
    if (typeof window === 'undefined') return;
    const ajax = (window as any).cckAjax;
    if (!ajax?.ajax_url) return;

    try {
      fetch(ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'cck_log_consent',
          consent_action: action,
        }).toString(),
      });
    } catch (error) {
      if (import.meta.env.DEV) {
        console.error('CookieBanner: Error logging action', error);
      }
    }
  };

  const saveConsent = (consentSettings: ConsentSettings, action: string) => {
    if (import.meta.env.DEV) {
      console.log('Saving consent:', consentSettings, 'Action:', action);
    }


    logAction(action);

    // Update consent mode and push dataLayer event
    updateConsentMode(consentSettings, action);


    setConsent(consentSettings);
    setShowBanner(false);
    setShowSettings(false);
    setShowMiniBanner(true);
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
  return (
    <>
      {showBanner && (!isMobile || showSettings) && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end justify-center p-4">
          <Card className="w-full max-w-2xl bg-cookie-banner border-cookie-banner-border shadow-floating animate-in slide-in-from-bottom-4 duration-300" style={bannerStyle}>
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
                            {heading}
                          </h3>
                          <p className="text-sm text-muted-foreground leading-relaxed">
                            {message}
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
                            {cookiePolicyUrlResolved && (
                              <>
                                {t('Si deseas más información pulsa en')}{' '}
                                <a
                                  href={cookiePolicyUrlResolved}
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
                              {aboutCookiesUrlResolved && (
                                <>
                                  {' '}{t('Para información detallada sobre cookies, visite')}{' '}
                                  <a
                                    href={aboutCookiesUrlResolved}
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
      )}

      {showBanner && isMobile && !showSettings && (
          <div className="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 w-full px-4">
            <Card className="bg-cookie-banner border-cookie-banner-border shadow-floating animate-in slide-in-from-bottom-4 duration-300" style={bannerStyle}>
              <div className="p-4 space-y-3">
                <div className="flex items-center gap-2">
                  <Cookie className="w-5 h-5 text-primary" />
                  <p className="text-sm flex-1">
                    {message}
                  </p>
                </div>
                <div className="flex gap-2 justify-end">
                  <Button variant="outline" size="sm" onClick={() => setShowSettings(true)}>
                    {t('Personalizar')}
                  </Button>
                  <Button variant="cookie" size="sm" onClick={acceptAll}>
                    {t('Aceptar')}
                  </Button>
                </div>
              </div>
            </Card>
          </div>
        )}

      {!forceShow && !showBanner && showMiniBanner && (
        <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-[9999] w-full max-w-xs px-4">
          <Card className="bg-cookie-banner border-cookie-banner-border shadow-floating animate-in slide-in-from-bottom-4 duration-300" style={bannerStyle}>
            <div className="flex items-center justify-between p-3">
              <div className="flex items-center gap-2">
                <Cookie className="w-5 h-5 text-primary" />
                <span className="text-sm">{t('Preferencias guardadas')}</span>
              </div>
              <Button variant="ghost" size="sm" onClick={reopenBanner}>
                {t('Cambiar')}
              </Button>
            </div>
          </Card>
        </div>
      )}
    </>
  );
};

export default CookieBanner;
