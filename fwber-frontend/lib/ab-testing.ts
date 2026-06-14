'use client'

const STORAGE_KEY = 'fwber_ab_landing_variant'
const EVENTS_KEY = 'fwber_ab_events'

export function getStoredVariant(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem(STORAGE_KEY)
}

export function trackEvent(event: string, data: Record<string, string>) {
  try {
    const events = JSON.parse(localStorage.getItem(EVENTS_KEY) || '[]')
    events.push({ event, ...data, timestamp: new Date().toISOString() })
    localStorage.setItem(EVENTS_KEY, JSON.stringify(events.slice(-100)))
  } catch {
    /* noop */
  }
}

export function trackCTAClick(ctaName: string) {
  const variant = getStoredVariant() || 'unknown'
  trackEvent('ab_cta_click', { variant, cta: ctaName })
}

export function getABEvents() {
  try {
    return JSON.parse(localStorage.getItem(EVENTS_KEY) || '[]')
  } catch {
    return []
  }
}
