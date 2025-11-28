/**
 * Configuration API client
 * Handles feature flags and system health endpoints
 */

import { api } from './client';

export interface FeatureFlags {
  groups: boolean;
  photos: boolean;
  proximity_artifacts: boolean;
  chatrooms: boolean;
  proximity_chatrooms: boolean;
  face_reveal: boolean;
  local_media_vault: boolean;
  moderation: boolean;
  recommendations: boolean;
  websocket: boolean;
  content_generation: boolean;
  rate_limits: boolean;
  analytics: boolean;
}

export interface FeatureFlagsResponse {
  features: FeatureFlags;
  source: string;
  timestamp: string;
}

export interface FeatureFlagsUpdateResponse {
  message: string;
  features: FeatureFlags;
  updated: string[];
  timestamp: string;
}

export interface SystemHealth {
  status: 'healthy' | 'degraded' | 'unhealthy';
  services: {
    database: 'up' | 'down';
    cache: 'up' | 'down';
    mercure: 'up' | 'down';
    queue: 'up' | 'down' | 'unknown';
  };
  details: {
    mercure_url: string | null;
    cache_driver: string;
    queue_driver: string;
  };
  features_enabled: Partial<FeatureFlags>;
  timestamp: string;
}

/**
 * Get current feature flag states from backend
 */
export function getFeatureFlags(): Promise<FeatureFlagsResponse> {
  return api.get<FeatureFlagsResponse>('/config/features');
}

/**
 * Update feature flag states at runtime
 */
export function updateFeatureFlags(
  features: Partial<FeatureFlags>
): Promise<FeatureFlagsUpdateResponse> {
  return api.put<FeatureFlagsUpdateResponse>('/config/features', { features });
}

/**
 * Get system health status including Mercure
 */
export function getSystemHealth(): Promise<SystemHealth> {
  return api.get<SystemHealth>('/config/health');
}
