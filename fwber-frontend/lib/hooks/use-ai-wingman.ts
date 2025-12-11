import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

interface WingmanSuggestion {
  suggestions: string[];
}

export function useAiWingman() {
  const [error, setError] = useState<string | null>(null);

  const getIceBreakers = useMutation({
    mutationFn: async (matchId: string) => {
      return api.get<WingmanSuggestion>(`/wingman/ice-breakers/${matchId}`);
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to get ice breakers');
    },
  });

  const getReplySuggestions = useMutation({
    mutationFn: async (matchId: string) => {
      return api.get<WingmanSuggestion>(`/wingman/replies/${matchId}`);
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to get reply suggestions');
    },
  });

  return {
    getIceBreakers,
    getReplySuggestions,
    error,
    setError,
  };
}
