export const t = (key: string): string => {
  if (typeof window !== 'undefined') {
    const translations = (window as Window).cckTranslations as
      | Record<string, string>
      | undefined;
    if (translations && translations[key]) {
      return translations[key];
    }
  }
  return key;
};

export default t;
