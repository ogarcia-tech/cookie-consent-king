import { useEffect } from 'react';

interface ConsentModeScriptProps {
  gtmId?: string;
  gaId?: string;
}

const ConsentModeScript: React.FC<ConsentModeScriptProps> = ({ gtmId, gaId }) => {
  useEffect(() => {
    // Initialize dataLayer if it doesn't exist
    if (typeof window !== 'undefined') {
      window.dataLayer = window.dataLayer || [];
      
      // gtag function
      window.gtag = function() {
        window.dataLayer!.push(arguments);
      };

      // Set default consent mode before any tags load
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

      // Initialize gtag with your GA4 ID if provided
      if (gaId) {
        window.gtag('config', gaId);
      }
    }

    // Load Google Tag Manager if gtmId is provided
    if (gtmId && typeof window !== 'undefined') {
      const script = document.createElement('script');
      script.async = true;
      script.src = `https://www.googletagmanager.com/gtm.js?id=${gtmId}`;
      document.head.appendChild(script);

      // GTM noscript fallback
      const noscript = document.createElement('noscript');
      const iframe = document.createElement('iframe');
      iframe.src = `https://www.googletagmanager.com/ns.html?id=${gtmId}`;
      iframe.height = '0';
      iframe.width = '0';
      iframe.style.display = 'none';
      iframe.style.visibility = 'hidden';
      noscript.appendChild(iframe);
      document.body.insertBefore(noscript, document.body.firstChild);

      return () => {
        // Cleanup
        const existingScript = document.querySelector(`script[src*="${gtmId}"]`);
        if (existingScript) {
          existingScript.remove();
        }
        const existingNoscript = document.querySelector(`noscript iframe[src*="${gtmId}"]`)?.parentElement;
        if (existingNoscript) {
          existingNoscript.remove();
        }
      };
    }

    // Load Google Analytics directly if gaId is provided without GTM
    if (gaId && !gtmId && typeof window !== 'undefined') {
      const script = document.createElement('script');
      script.async = true;
      script.src = `https://www.googletagmanager.com/gtag/js?id=${gaId}`;
      document.head.appendChild(script);

      return () => {
        const existingScript = document.querySelector(`script[src*="${gaId}"]`);
        if (existingScript) {
          existingScript.remove();
        }
      };
    }
  }, [gtmId, gaId]);

  return null;
};

export default ConsentModeScript;