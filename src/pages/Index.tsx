import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import CookieBanner from '@/components/CookieBanner';
import ConsentModeScript from '@/components/ConsentModeScript';
import { useConsentMode } from '@/hooks/useConsentMode';
import { Cookie, Shield, CheckCircle, Settings } from 'lucide-react';

const Index = () => {
  const { consent, isConsentGiven, resetConsent, getConsentDate } = useConsentMode();
  const [showDemo, setShowDemo] = useState(false);

  const handleConsentUpdate = (newConsent: any) => {
    console.log('Consent updated:', newConsent);
    setShowDemo(false); // Cerrar el banner demo cuando se actualice el consentimiento
    // Aquí puedes agregar lógica adicional cuando se actualice el consentimiento
  };

  const consentDate = getConsentDate();

  return (
    <>
      {/* Inicializar Consent Mode v2 - Reemplaza 'GTM-XXXXXXX' con tu ID real */}
      <ConsentModeScript 
        gtmId="GTM-XXXXXXX" 
        gaId="G-XXXXXXXXXX" 
      />
      
      <div className="min-h-screen bg-gradient-to-br from-background to-muted/20 p-6">
        <div className="max-w-4xl mx-auto space-y-8">
          {/* Header */}
          <div className="text-center space-y-4 pt-12">
            <div className="flex items-center justify-center gap-2 mb-4">
              <Cookie className="w-8 h-8 text-primary" />
              <h1 className="text-4xl font-bold bg-gradient-to-r from-primary to-primary/70 bg-clip-text text-transparent">
                Cookie Banner
              </h1>
            </div>
            <p className="text-xl text-muted-foreground max-w-2xl mx-auto">
              Banner de cookies moderno con soporte completo para Google Consent Mode v2
            </p>
            <div className="flex justify-center gap-2">
              <Badge variant="secondary" className="flex items-center gap-1">
                <Shield className="w-3 h-3" />
                GDPR Compliant
              </Badge>
              <Badge variant="outline">Consent Mode v2</Badge>
              <Badge variant="outline">TypeScript</Badge>
            </div>
          </div>

          {/* Features Grid */}
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card className="border-2 hover:border-primary/20 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Shield className="w-5 h-5 text-success" />
                  Cumplimiento GDPR
                </CardTitle>
              </CardHeader>
              <CardContent>
                <CardDescription>
                  Cumple totalmente con las regulaciones GDPR y otras leyes de privacidad internacionales.
                </CardDescription>
              </CardContent>
            </Card>

            <Card className="border-2 hover:border-primary/20 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Settings className="w-5 h-5 text-primary" />
                  Consent Mode v2
                </CardTitle>
              </CardHeader>
              <CardContent>
                <CardDescription>
                  Integración nativa con Google Consent Mode v2 para una gestión avanzada de consentimientos.
                </CardDescription>
              </CardContent>
            </Card>

            <Card className="border-2 hover:border-primary/20 transition-colors">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success" />
                  Fácil Configuración
                </CardTitle>
              </CardHeader>
              <CardContent>
                <CardDescription>
                  Configuración granular de diferentes tipos de cookies con interfaz intuitiva.
                </CardDescription>
              </CardContent>
            </Card>
          </div>

          {/* Status Card */}
          <Card className="max-w-2xl mx-auto">
            <CardHeader>
              <CardTitle>Estado del Consentimiento</CardTitle>
              <CardDescription>
                Información actual sobre las preferencias de cookies
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {isConsentGiven() ? (
                <div className="space-y-3">
                  <div className="flex items-center gap-2">
                    <CheckCircle className="w-5 h-5 text-success" />
                    <span className="font-medium">Consentimiento otorgado</span>
                  </div>
                  
                  {consentDate && (
                    <p className="text-sm text-muted-foreground">
                      Fecha: {consentDate.toLocaleDateString('es-ES', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                    </p>
                  )}

                  <div className="grid grid-cols-2 gap-2 mt-4">
                    <div className="flex justify-between">
                      <span className="text-sm">Necesarias:</span>
                      <Badge variant={consent?.necessary ? "success" : "destructive"} className="text-xs">
                        {consent?.necessary ? "Activo" : "Inactivo"}
                      </Badge>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-sm">Análisis:</span>
                      <Badge variant={consent?.analytics ? "success" : "destructive"} className="text-xs">
                        {consent?.analytics ? "Activo" : "Inactivo"}
                      </Badge>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-sm">Marketing:</span>
                      <Badge variant={consent?.marketing ? "success" : "destructive"} className="text-xs">
                        {consent?.marketing ? "Activo" : "Inactivo"}
                      </Badge>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-sm">Preferencias:</span>
                      <Badge variant={consent?.preferences ? "success" : "destructive"} className="text-xs">
                        {consent?.preferences ? "Activo" : "Inactivo"}
                      </Badge>
                    </div>
                  </div>

                  <Button 
                    onClick={resetConsent} 
                    variant="outline" 
                    size="sm"
                    className="w-full mt-4"
                  >
                    Restablecer Consentimiento
                  </Button>
                </div>
              ) : (
                <div className="text-center space-y-3">
                  <p className="text-muted-foreground">
                    No se ha otorgado consentimiento aún
                  </p>
                  <div className="flex gap-2 justify-center">
                    <Button 
                      onClick={() => setShowDemo(true)} 
                      variant="cookie"
                    >
                      Mostrar Banner de Cookies
                    </Button>
                    <Button 
                      onClick={resetConsent} 
                      variant="outline"
                    >
                      Limpiar y Probar
                    </Button>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Demo Banner - Force show for testing */}
          <CookieBanner 
            onConsentUpdate={handleConsentUpdate} 
            forceShow={showDemo}
          />
        </div>
      </div>
    </>
  );
};

export default Index;
