export const t = (key: string): string => {
  if (typeof window !== 'undefined') {
    const data = (window as Window & { cckData?: { translations?: Record<string, string> } }).cckData;
    const translations = data?.translations;
    if (translations && translations[key]) {
      return translations[key];
    }
  }
  return key;
};

export default t;
