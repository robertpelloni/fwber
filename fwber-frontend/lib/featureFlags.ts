const parseBoolean = (value: string | undefined, fallback = false) => {
  if (typeof value === 'undefined') return fallback
  const normalized = value.trim().toLowerCase()
  return ['1', 'true', 'yes', 'on'].includes(normalized) ? true : ['0', 'false', 'no', 'off'].includes(normalized) ? false : fallback
}

const featureFlags = {
  clientFaceBlur: parseBoolean(process.env.NEXT_PUBLIC_FEATURE_CLIENT_FACE_BLUR, false),
  faceReveal: parseBoolean(process.env.NEXT_PUBLIC_FEATURE_FACE_REVEAL, false),
  localMediaVault: parseBoolean(process.env.NEXT_PUBLIC_FEATURE_LOCAL_MEDIA_VAULT, false),
}

export type FeatureFlagKey = keyof typeof featureFlags

export const isFeatureEnabled = (flag: FeatureFlagKey) => featureFlags[flag]

export const getFeatureFlags = () => ({ ...featureFlags })
