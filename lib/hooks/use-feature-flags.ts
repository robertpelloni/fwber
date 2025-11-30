/**
 * Feature Flag hooks for frontend
 *
 * Provides client-side feature flag management with:
 * 1. Environment variable checks (NEXT_PUBLIC_FEATURE_*)
 * 2. Optional backend feature flag status sync (for runtime toggling)
 *
 * Usage:
 * const { isEnabled } = useFeatureFlag('face_reveal');
 * const flags = useFeatureFlags(['face_reveal', 'local_media_vault', 'client_face_blur']);
 */

import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api/client';

/**
 * Feature flags available on the frontend
 * These map to NEXT_PUBLIC_FEATURE_* environment variables
 */
export type FeatureFlagName =
  | 'client_face_blur'
  | 'face_reveal'
  | 'local_media_vault';

/**
 * Environment variable mapping for feature flags
 */
const ENV_VAR_MAP: Record<FeatureFlagName, string> = {
  client_face_blur: 'NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR',
  face_reveal: 'NEXT_PUBLIC_FEATURE_FACE_REVEAL',
  local_media_vault: 'NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT',
};

/**
 * Check if a feature flag is enabled via environment variable
 * Note: We must access process.env.NEXT_PUBLIC_* explicitly for Next.js/Webpack to inline the values.
 * Dynamic access like process.env[key] will return undefined in the browser.
 */
function getEnvFeatureFlag(name: FeatureFlagName): boolean {
  let value: string | undefined;

  switch (name) {
    case 'client_face_blur':
      value = process.env.NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR;
      break;
    case 'face_reveal':
      value = process.env.NEXT_PUBLIC_FEATURE_FACE_REVEAL;
      break;
    case 'local_media_vault':
      value = process.env.NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT;
      break;
  }

  return value === 'true' || value === '1';
}

/**
 * Get all frontend feature flag values from environment
 */
export function getFeatureFlags(): Record<FeatureFlagName, boolean> {
  return {
    client_face_blur: getEnvFeatureFlag('client_face_blur'),
    face_reveal: getEnvFeatureFlag('face_reveal'),
    local_media_vault: getEnvFeatureFlag('local_media_vault'),
  };
}

/**
 * Hook to check if a single feature flag is enabled
 *
 * @param name - The feature flag name to check
 * @returns Object with isEnabled boolean and loading state
 *
 * @example
 * ```tsx
 * const { isEnabled } = useFeatureFlag('face_reveal');
 * if (!isEnabled) return null;
 * ```
 */
export function useFeatureFlag(name: FeatureFlagName) {
  const isEnabled = useMemo(() => getEnvFeatureFlag(name), [name]);

  return {
    isEnabled,
    isLoading: false, // Sync from env, no loading
  };
}

/**
 * Hook to check multiple feature flags at once
 *
 * @param names - Array of feature flag names to check
 * @returns Object mapping flag names to their enabled status
 *
 * @example
 * ```tsx
 * const flags = useFeatureFlags(['face_reveal', 'client_face_blur']);
 * if (flags.face_reveal) { ... }
 * ```
 */
export function useFeatureFlags(names: FeatureFlagName[]) {
  const flags = useMemo(() => {
    const result: Partial<Record<FeatureFlagName, boolean>> = {};
    for (const name of names) {
      result[name] = getEnvFeatureFlag(name);
    }
    return result as Record<FeatureFlagName, boolean>;
  }, [names.join(',')]); // eslint-disable-line react-hooks/exhaustive-deps

  return flags;
}

/**
 * Backend feature flag response type
 * When backend provides feature flag status
 */
interface BackendFeatureFlags {
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

/**
 * Hook to fetch feature flags from backend
 * Useful for admin panels or when runtime flag changes are needed
 *
 * Note: This is optional and requires a backend endpoint to be implemented
 * The backend would need to expose GET /api/config/features
 */
export function useBackendFeatureFlags(options?: { enabled?: boolean }) {
  return useQuery<BackendFeatureFlags>({
    queryKey: ['backend-feature-flags'],
    queryFn: async () => {
      // This endpoint would need to be created on the backend
      // For now, return static defaults based on what we know
      return api.get<BackendFeatureFlags>('/config/features');
    },
    enabled: options?.enabled ?? false, // Disabled by default, enable when endpoint exists
    staleTime: 5 * 60 * 1000, // 5 minutes
    gcTime: 10 * 60 * 1000, // 10 minutes
    retry: 1,
    refetchOnWindowFocus: false,
  });
}

/**
 * Hook that combines frontend env flags with backend flags
 * Frontend env flags take precedence for safety (if disabled locally, stay disabled)
 */
export function useCombinedFeatureFlags() {
  const frontendFlags = useMemo(() => getFeatureFlags(), []);
  const backendQuery = useBackendFeatureFlags({ enabled: false });

  // Combine: feature is enabled only if BOTH frontend AND backend allow it
  // This prevents accidentally enabling features that backend doesn't support
  const combinedFlags = useMemo(() => {
    if (!backendQuery.data) {
      return frontendFlags;
    }

    return {
      client_face_blur: frontendFlags.client_face_blur, // Frontend-only
      face_reveal:
        frontendFlags.face_reveal && (backendQuery.data.face_reveal ?? false),
      local_media_vault:
        frontendFlags.local_media_vault &&
        (backendQuery.data.local_media_vault ?? false),
    };
  }, [frontendFlags, backendQuery.data]);

  return {
    flags: combinedFlags,
    isLoading: backendQuery.isLoading,
    error: backendQuery.error,
  };
}
