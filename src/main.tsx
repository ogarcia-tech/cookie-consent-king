import { createRoot } from 'react-dom/client';
import App from './App.tsx';
import CookieBanner from './components/CookieBanner';
import './index.css';

declare global {
  interface Window {
    cckPreview?: boolean;
    cckForceShow?: boolean;
    cckOptions?: {
      title?: string;
      message?: string;
      cookiePolicyUrl?: string;
      aboutCookiesUrl?: string;
    };
  }
}

const rootElement = document.getElementById('root')!;

if (window.cckPreview) {
  const opts = window.cckOptions || {};
  createRoot(rootElement).render(
    <CookieBanner
      forceShow={window.cckForceShow ?? true}
      cookiePolicyUrl={opts.cookiePolicyUrl}
      aboutCookiesUrl={opts.aboutCookiesUrl}
      title={opts.title}
      message={opts.message}
    />
  );
} else {
  createRoot(rootElement).render(<App />);
}

