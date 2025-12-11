import { useState } from 'react';
import { api } from '@/lib/api/client';

interface TranslationResponse {
  original: string;
  translated: string;
  target_language: string;
}

export function useTranslation() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const translate = async (text: string, targetLanguage: string = 'en'): Promise<string | null> => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await api.post<TranslationResponse>('/messages/translate', {
        text,
        target_language: targetLanguage,
      });
      return response.translated;
    } catch (err: any) {
      setError(err.message || 'Translation failed');
      return null;
    } finally {
      setIsLoading(false);
    }
  };

  return { translate, isLoading, error };
}
