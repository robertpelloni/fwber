import { api } from './client';

export interface RoastRequest {
  mode: 'roast' | 'hype';
}

export interface PublicRoastRequest {
  name: string;
  job: string;
  trait: string;
  mode: 'roast' | 'hype';
}

export interface RoastResponse {
  roast: string;
  share_id: string;
}

export interface PublicRoastResponse {
  roast: string;
  is_preview: boolean;
  cta: string;
}

export interface VibeCheckResponse {
  red_flags: string[];
  green_flags: string[];
  share_id: string;
}

export interface FortuneResponse {
  fortune: string;
  share_id: string;
}

export interface CosmicMatchResponse {
  sign: string;
  reason: string;
  share_id: string;
}

export const wingmanApi = {
  /**
   * Generate a roast or hype for the authenticated user's profile
   */
  roastProfile: (data: RoastRequest) => {
    return api.post<RoastResponse>('/wingman/roast', data);
  },

  /**
   * Generate a generic roast/hype for public users (not authenticated)
   */
  roastPublic: (data: PublicRoastRequest) => {
    return api.post<PublicRoastResponse>('/public/roast', data);
  },

  /**
   * Generate Red/Green flags analysis
   */
  checkVibe: () => {
    return api.post<VibeCheckResponse>('/wingman/vibe-check');
  },

  /**
   * Generate a dating fortune
   */
  predictFortune: () => {
    return api.post<FortuneResponse>('/wingman/fortune');
  },

  /**
   * Get cosmic match prediction
   */
  getCosmicMatch: () => {
    return api.post<CosmicMatchResponse>('/wingman/cosmic-match');
  },
  
  /**
   * Analyze a specific quirk
   */
  checkQuirk: (quirk: string) => {
    return api.post<any>('/wingman/quirk-check', { quirk });
  }
};
