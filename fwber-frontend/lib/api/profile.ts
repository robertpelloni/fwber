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
    relationship_style: string | null;
    looking_for: string[];
    location: {
      latitude: number | null;
      longitude: number | null;
      max_distance: number;
      city: string | null;
      state: string | null;
    };
    // Travel Mode
    is_travel_mode?: boolean;
    travel_latitude?: number | null;
    travel_longitude?: number | null;
    travel_location_name?: string | null;
    
    // New Optional Attributes
    love_language?: string;
    personality_type?: string;
    chronotype?: string;
    social_media?: Record<string, string>;
    communication_style?: string;
    blood_type?: string;
    sti_status?: Record<string, any>;
    family_plans?: string;
    relationship_goals?: string;
    languages?: string[];
    zodiac_sign?: string;
    pronouns?: string;
    sexual_orientation?: string;
    drinking_status?: string;
    smoking_status?: string;
    cannabis_status?: string;
    dietary_preferences?: string;
    exercise_habits?: string;
    sleep_habits?: string;
    pets?: string[];
    children?: string;
    religion?: string;
    political_views?: string;
    
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
  relationship_style?: string;
  looking_for?: string[];
  location?: {
    latitude?: number;
    longitude?: number;
    max_distance?: number;
    city?: string;
    state?: string;
  };
  // Travel Mode
  is_travel_mode?: boolean;
  travel_location?: {
    latitude?: number;
    longitude?: number;
    name?: string;
  };
  // New Optional Attributes
  love_language?: string;
  personality_type?: string;
  chronotype?: string;
  social_media?: Record<string, string>;
  communication_style?: string;
  blood_type?: string;
  sti_status?: Record<string, any>;
  family_plans?: string;
  relationship_goals?: string;
  languages?: string[];
  zodiac_sign?: string;
  pronouns?: string;
  sexual_orientation?: string;
  drinking_status?: string;
  smoking_status?: string;
  cannabis_status?: string;
  dietary_preferences?: string;
  exercise_habits?: string;
  sleep_habits?: string;
  pets?: string[];
  children?: string;
  religion?: string;
  political_views?: string;
  
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
 * Fetch a public user profile by ID
 */
export async function getPublicProfile(token: string, userId: number): Promise<UserProfile> {
  const response = await fetch(`${API_BASE_URL}/users/${userId}`, {
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
 * Fetch authenticated user's profile
 */
export async function getUserProfile(token: string): Promise<UserProfile> {
  const response = await fetch(`${API_BASE_URL}/profile`, {
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
  // Map frontend fields to backend expected fields
  const payload: any = { ...updates };
  
  if (updates.date_of_birth) {
    payload.birthdate = updates.date_of_birth;
    delete payload.date_of_birth;
  }

  const response = await fetch(`${API_BASE_URL}/profile`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update profile' }));
    console.error('Profile update failed:', error);
    throw new Error(error.message || error.error || 'Failed to update profile');
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
 * Search for users by name or email
 */
export async function searchUsers(token: string, searchTerm: string): Promise<UserProfile[]> {
  const response = await fetch(`${API_BASE_URL}/users/search?q=${searchTerm}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to search for users');
  }

  const data = await response.json();
  return data.data || data;
}

export interface OnboardingStatus {
  completed: boolean;
  completed_at: string | null;
}

export async function getOnboardingStatus(token: string): Promise<OnboardingStatus> {
  const response = await fetch(`${API_BASE_URL}/onboarding/status`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to get onboarding status');
  }

  return response.json();
}

export async function completeOnboarding(token: string): Promise<void> {
  const response = await fetch(`${API_BASE_URL}/onboarding/complete`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to complete onboarding');
  }
}
