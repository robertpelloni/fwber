/**
 * Location API Client
 *
 * AI Model: Claude 4.5 - Multi-AI Orchestration
 * Phase: 5A - Location-Based Social Features
 * Purpose: Type-safe API client for location tracking and proximity discovery
 *
 * Created: 2025-01-19
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export interface LocationData {
  id: number;
  latitude: number;
  longitude: number;
  accuracy?: number;
  heading?: number;
  speed?: number;
  altitude?: number;
  privacy_level: 'public' | 'friends' | 'private';
  privacy_level_display: string;
  last_updated: string;
  is_active: boolean;
  is_recent: boolean;
}

export interface NearbyUser {
  id: number;
  display_name: string;
  age?: number;
  gender?: string;
  location: {
    latitude: number;
    longitude: number;
    accuracy?: number;
    distance: string;
    last_updated: string;
  };
  privacy_level: 'public' | 'friends' | 'private';
  is_recent: boolean;
}

export interface NearbyUsersResponse {
  success: boolean;
  data: NearbyUser[];
  meta: {
    total: number;
    radius: number;
    center: {
      latitude: number;
      longitude: number;
    };
  };
}

/**
 * Get current user's location
 */
export async function getCurrentLocation(token: string): Promise<LocationData> {
  const response = await fetch(`${API_BASE_URL}/location`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch location' }));
    throw new Error(error.message || 'Failed to fetch location');
  }

  const data = await response.json();
  return data.data;
}

/**
 * Update user's current location
 */
export async function updateLocation(
  token: string, 
  locationData: {
    latitude: number;
    longitude: number;
    accuracy?: number;
    heading?: number;
    speed?: number;
    altitude?: number;
    privacy_level?: 'public' | 'friends' | 'private';
  }
): Promise<LocationData> {
  const response = await fetch(`${API_BASE_URL}/location`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(locationData),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update location' }));
    throw new Error(error.message || 'Failed to update location');
  }

  const data = await response.json();
  return data.data;
}

/**
 * Get nearby users within radius
 */
export async function getNearbyUsers(
  token: string,
  params: {
    latitude: number;
    longitude: number;
    radius?: number;
    limit?: number;
  }
): Promise<NearbyUsersResponse> {
  const searchParams = new URLSearchParams({
    latitude: params.latitude.toString(),
    longitude: params.longitude.toString(),
    ...(params.radius && { radius: params.radius.toString() }),
    ...(params.limit && { limit: params.limit.toString() }),
  });

  const response = await fetch(`${API_BASE_URL}/location/nearby?${searchParams}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to fetch nearby users' }));
    throw new Error(error.message || 'Failed to fetch nearby users');
  }

  return await response.json();
}

/**
 * Update location privacy settings
 */
export async function updateLocationPrivacy(
  token: string,
  privacyLevel: 'public' | 'friends' | 'private'
): Promise<{ privacy_level: string; privacy_level_display: string }> {
  const response = await fetch(`${API_BASE_URL}/location/privacy`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ privacy_level: privacyLevel }),
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to update privacy settings' }));
    throw new Error(error.message || 'Failed to update privacy settings');
  }

  const data = await response.json();
  return data.data;
}

/**
 * Clear location history
 */
export async function clearLocationHistory(token: string): Promise<void> {
  const response = await fetch(`${API_BASE_URL}/location`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Failed to clear location history' }));
    throw new Error(error.message || 'Failed to clear location history');
  }
}

/**
 * Get user's current geolocation from browser
 */
export function getCurrentGeolocation(): Promise<GeolocationPosition> {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation is not supported by this browser'));
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => resolve(position),
      (error) => reject(error),
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000, // 5 minutes
      }
    );
  });
}

/**
 * Watch user's geolocation changes
 */
export function watchGeolocation(
  onSuccess: (position: GeolocationPosition) => void,
  onError: (error: GeolocationPositionError) => void
): number {
  if (!navigator.geolocation) {
    onError({
      code: 0,
      message: 'Geolocation is not supported by this browser',
      PERMISSION_DENIED: 1,
      POSITION_UNAVAILABLE: 2,
      TIMEOUT: 3,
    });
    return -1;
  }

  return navigator.geolocation.watchPosition(
    onSuccess,
    onError,
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000, // 1 minute
    }
  );
}

/**
 * Stop watching geolocation
 */
export function clearGeolocationWatch(watchId: number): void {
  if (navigator.geolocation && watchId !== -1) {
    navigator.geolocation.clearWatch(watchId);
  }
}
