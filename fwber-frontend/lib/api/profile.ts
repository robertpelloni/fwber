/**
 * Profile API Client
 * 
 * AI Model: Gemini 2.5 Flash - Simulated (Parallel Implementation)
 * Phase: 3A - Next.js to Laravel Integration
 * Purpose: Type-safe API client for profile operations
 * 
 * Created: 2025-10-18
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface UserProfile {
  id: number;
  email: string;
  email_verified: boolean;
  created_at: string;
  last_online: string;
  profile: {
    display_name: string | null;
    bio: string | null;
    date_of_birth?: string | null;
    age: number | null;
    gender: string | null;
    pronouns: string | null;
    sexual_orientation: string | null;
    relationship_style: string | null;
    looking_for: string[];
    location: {
      latitude: number | null;
      longitude: number | null;
      max_distance: number;
      city: string | null;
      state: string | null;
    };
    preferences: {
      // Lifestyle preferences
      smoking?: string;
      drinking?: string;
      exercise?: string;
      diet?: string;
      pets?: string;
      children?: string;
      education?: string;
      occupation?: string;
      income?: string;
      // Dating preferences
      age_range_min?: number;
      age_range_max?: number;
      height_min?: string;
      height_max?: string;
      body_type?: string;
      ethnicity?: string[];
      religion?: string;
      politics?: string;
      // Interests
      hobbies?: string[];
      music?: string[];
      movies?: string[];
      books?: string[];
      sports?: string[];
      travel?: string;
      // Communication
      communication_style?: string;
      response_time?: string;
      meeting_preference?: string;
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

export interface ProfileUpdateData {
  display_name?: string;
  bio?: string;
  // ISO date string YYYY-MM-DD
  date_of_birth?: string;
  gender?: string;
  pronouns?: string;
  sexual_orientation?: string;
  relationship_style?: string;
  looking_for?: string[];
  location?: {
    latitude?: number;
    longitude?: number;
    max_distance?: number;
    city?: string;
    state?: string;
  };
  preferences?: {
    // Lifestyle preferences
    smoking?: string;
    drinking?: string;
    exercise?: string;
    diet?: string;
    pets?: string;
    children?: string;
    education?: string;
    occupation?: string;
    income?: string;
    // Dating preferences
    age_range_min?: number;
    age_range_max?: number;
    height_min?: string;
    height_max?: string;
    body_type?: string;
    ethnicity?: string[];
    religion?: string;
    politics?: string;
    // Interests
    hobbies?: string[];
    music?: string[];
    movies?: string[];
    books?: string[];
    sports?: string[];
    travel?: string;
    // Communication
    communication_style?: string;
    response_time?: string;
    meeting_preference?: string;
  };
}

export interface ProfileCompletenessResponse {
  percentage: number;
  completed_fields: number;
  total_fields: number;
  missing_fields: string[];
  is_complete: boolean;
}

/**
 * Fetch authenticated user's profile
 */
export async function getUserProfile(token: string): Promise<UserProfile> {
  const response = await fetch(`${API_BASE_URL}/user`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch profile' }));
    throw new Error(error.message || 'Failed to fetch profile');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Update user profile
 */
export async function updateUserProfile(
  token: string,
  updates: ProfileUpdateData
): Promise<UserProfile> {
  const response = await fetch(`${API_BASE_URL}/user`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(updates),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update profile' }));
    throw new Error(error.message || 'Failed to update profile');
  }

  const data = await response.json();
  return data.data || data;
}

/**
 * Get profile completeness status
 */
export async function getProfileCompleteness(token: string): Promise<ProfileCompletenessResponse> {
  const response = await fetch(`${API_BASE_URL}/profile/completeness`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to check profile completeness');
  }

  return response.json();
}

/**
 * Generate a new AI avatar
 */
export async function generateAvatar(
  token: string,
  style: string,
  prompt: string
): Promise<{ url: string }> {
  const response = await fetch(`${API_BASE_URL}/profile/avatar/generate`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ style, prompt }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to generate avatar' }));
    throw new Error(error.message || 'Failed to generate avatar');
  }

  return response.json();
}

