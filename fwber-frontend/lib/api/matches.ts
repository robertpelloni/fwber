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
  name: string;
  email: string;
  avatarUrl: string | null;
  bio: string | null;
  locationDescription: string | null;
  distance: number;
  compatibilityScore: number;
  lastSeenAt: string | null;
  // Optional profile object if needed for compatibility with existing code, 
  // but ideally we should map the flat structure to what the UI expects
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
  target_user_id: number;
}

export interface MatchActionResponse {
  action: string;
  is_match: boolean;
  message: string;
}

/**
 * Fetch potential matches for the authenticated user
 */
export async function getMatches(token: string): Promise<Match[]> {
  const response = await fetch(`${API_BASE_URL}/matches`, {
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
  // The API returns { matches: [...], total: ... }
  // But the component expects an array.
  // Also we need to map the flat structure to the nested profile structure expected by the UI
  const matches = data.matches || [];
  
  return matches.map((m: any) => ({
    ...m,
    // Map flat fields to profile object for UI compatibility
    profile: {
      display_name: m.name,
      bio: m.bio,
      age: m.age || null,
      gender: m.gender || null,
      looking_for: m.looking_for || [],
      location: {
        latitude: null,
        longitude: null,
        max_distance: 0,
        city: m.locationDescription,
        state: null,
      },
      photos: m.avatarUrl ? [{ id: 0, url: m.avatarUrl, is_private: false, is_primary: true }] : [],
      profile_complete: true,
      completion_percentage: 100,
    }
  }));
}

/**
 * Perform an action on a potential match (like, pass, super like)
 */
export async function performMatchAction(
  token: string, 
  targetUserId: number, 
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
      target_user_id: targetUserId,
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
  const response = await fetch(`${API_BASE_URL}/matches/established`, {
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
  const conversations = data.data || [];

  return conversations.map((c: any) => {
    const otherUser = c.other_user;
    const profile = otherUser.profile || {};
    
    return {
      id: otherUser.id, // Use User ID as the ID for consistency with getMatches
      name: profile.display_name || otherUser.name,
      email: otherUser.email,
      avatarUrl: profile.avatar_url,
      bio: profile.bio,
      locationDescription: profile.location_description,
      distance: 0, // Not available in established matches
      compatibilityScore: 0, // Not available in established matches
      lastSeenAt: otherUser.last_seen_at,
      profile: {
        display_name: profile.display_name || otherUser.name,
        bio: profile.bio,
        age: profile.date_of_birth ? new Date().getFullYear() - new Date(profile.date_of_birth).getFullYear() : null,
        gender: profile.gender,
        looking_for: profile.looking_for || [],
        location: {
          latitude: profile.location_latitude,
          longitude: profile.location_longitude,
          max_distance: 0,
          city: profile.location_description,
          state: null,
        },
        photos: otherUser.photos ? otherUser.photos.map((p: any) => ({
            id: p.id,
            url: p.url || p.file_path, // Handle both cases if needed
            is_private: p.is_private,
            is_primary: p.is_primary
        })) : [],
        profile_complete: true,
        completion_percentage: 100,
      }
    };
  });
}
