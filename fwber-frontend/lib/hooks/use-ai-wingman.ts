import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

interface WingmanSuggestion {
  suggestions: string[];
}

export interface DateIdea {
  title: string;
  description: string;
  reason: string;
}

interface DateIdeasResponse {
  ideas: DateIdea[];
}

export interface VibeCheckResponse {
  green_flags: string[];
  red_flags: string[];
}

export interface FortuneResponse {
  fortune: string;
}

export interface CosmicMatchResponse {
  best_match: string;
  best_reason: string;
  worst_match: string;
  worst_reason: string;
}

export interface NemesisResponse {
  nemesis_type: string;
  clashing_traits: string[];
  why_it_would_fail: string;
  scientific_explanation: string;
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

  const getDateIdeas = useMutation({
    mutationFn: async ({ matchId, location }: { matchId: string; location?: string }) => {
      const params = location ? { location } : undefined;
      return api.get<DateIdeasResponse>(`/wingman/date-ideas/${matchId}`, { params });
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to get date ideas');
    },
  });

  const roastProfile = useMutation({
    mutationFn: async (mode: 'roast' | 'hype' = 'roast') => {
      return api.post<{ roast: string }>('/wingman/roast', { mode });
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to generate content');
    },
  });

  const checkVibe = useMutation({
    mutationFn: async () => {
      return api.get<VibeCheckResponse>('/wingman/vibe-check');
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to check vibe');
    },
  });

  const predictFortune = useMutation({
    mutationFn: async () => {
      return api.get<FortuneResponse>('/wingman/fortune');
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to predict fortune');
    },
  });

  const getCosmicMatch = useMutation({
    mutationFn: async () => {
      return api.get<CosmicMatchResponse>('/wingman/cosmic-match');
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to get cosmic match');
    },
  });

  const findNemesis = useMutation({
    mutationFn: async () => {
      return api.get<NemesisResponse>('/wingman/nemesis');
    },
    onError: (err: any) => {
      setError(err.message || 'Failed to find nemesis');
    },
  });

  return {
    getIceBreakers,
    getReplySuggestions,
    getDateIdeas,
    roastProfile,
    checkVibe,
    predictFortune,
    getCosmicMatch,
    findNemesis,
    error,
    setError,
  };
}
