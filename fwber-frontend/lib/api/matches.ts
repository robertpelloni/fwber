/**
 * Matches API Client
 * 
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 3B - Matching System Integration
 * Purpose: Type-safe API client for match operations
 * 
 * Created: 2025-01-19
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface Match {
  id: number;
  user_id: number;
  matched_user_id: number;
  compatibility_score: number;
  created_at: string;
  updated_at: string;
  profile?: {
    display_name: string | null;
    bio: string | null;
    age: number | null;
    gender: string | null;
    looking_for: string[];
    location: {
      latitude: number | null;
      longitude: number | null;
      max_distance: number;
      city: string | null;
      state: string | null;
    };
    photos?: Array<{
      id: number;
      url: string;
      is_private: boolean;
      is_primary: boolean;
    }>;
    profile_complete: boolean;
    completion_percentage: number;
  };
}

export type MatchAction = 'like' | 'pass' | 'super_like';

export interface MatchActionRequest {
  action: MatchAction;
}

export interface MatchActionResponse {
  message: string;
  match_created?: boolean;
  match_id?: number;
}

/**
 * Fetch potential matches for the authenticated user
 */
export async function getMatches(token: string, filters: any = {}): Promise<Match[]> {
  const queryParams = new URLSearchParams(filters).toString();
  const response = await fetch(`${API_BASE_URL}/matches?${queryParams}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch matches' }));
    throw new Error(error.message || 'Failed to fetch matches');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Perform an action on a potential match (like, pass, super like)
 */
export async function performMatchAction(
  token: string, 
  matchId: number, 
  action: MatchAction
): Promise<MatchActionResponse> {
  const response = await fetch(`${API_BASE_URL}/matches/action`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      match_id: matchId,
      action: action,
    }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to perform match action' }));
    throw new Error(error.message || 'Failed to perform match action');
  }

  const data = await response.json();
  return data;
}

/**
 * Get match history for the authenticated user
 */
export async function getMatchHistory(token: string): Promise<Match[]> {
  const response = await fetch(`${API_BASE_URL}/matches/history`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch match history' }));
    throw new Error(error.message || 'Failed to fetch match history');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Get mutual matches (both users liked each other)
 */
export async function getMutualMatches(token: string): Promise<Match[]> {
  const response = await fetch(`${API_BASE_URL}/matches/mutual`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch mutual matches' }));
    throw new Error(error.message || 'Failed to fetch mutual matches');
  }

  const data = await response.json();
  return data.data || data;
}
