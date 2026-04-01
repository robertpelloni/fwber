'use client'

import { useEffect } from 'react'

const VERSION_KEY = 'fwber_frontend_version'
const RECOVERY_FLAG = 'fwber_asset_recovery_attempted'
const RECOVERY_TTL_MS = 5 * 60 * 1000

async function clearClientCaches(): Promise<void> {
  if (typeof window === 'undefined') {
    return
  }

  if ('serviceWorker' in navigator) {
    const registrations = await navigator.serviceWorker.getRegistrations()
    await Promise.all(registrations.map((registration) => registration.unregister()))
  }

  if ('caches' in window) {
    const cacheNames = await caches.keys()
    await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)))
  }
}

function shouldRecoverAssetError(message: string): boolean {
  const normalized = message.toLowerCase()

  return normalized.includes('loading chunk') ||
    normalized.includes('failed to fetch dynamically imported module') ||
    normalized.includes('/_next/static/') ||
    normalized.includes('.css') ||
    normalized.includes('mime type')
}

export default function AssetRecovery() {
  useEffect(() => {
    if (typeof window === 'undefined') {
      return
    }

    const currentVersion = process.env.NEXT_PUBLIC_FRONTEND_VERSION || process.env.NEXT_PUBLIC_PROJECT_VERSION || 'unknown'
    const previousVersion = localStorage.getItem(VERSION_KEY)

    const getLastRecoveryAttempt = () => {
      const rawValue = localStorage.getItem(RECOVERY_FLAG)

      if (!rawValue) {
        return null
      }

      const [version, timestamp] = rawValue.split(':')
      const parsedTimestamp = Number(timestamp)

      if (!version || Number.isNaN(parsedTimestamp)) {
        localStorage.removeItem(RECOVERY_FLAG)
        return null
      }

      return {
        version,
        timestamp: parsedTimestamp,
      }
    }

    if (previousVersion && previousVersion !== currentVersion) {
      void clearClientCaches().finally(() => {
        localStorage.removeItem(RECOVERY_FLAG)
        localStorage.setItem(VERSION_KEY, currentVersion)
        window.location.reload()
      })
      return
    }

    localStorage.setItem(VERSION_KEY, currentVersion)

    const recoverFromAssetError = (message: string) => {
      const lastRecoveryAttempt = getLastRecoveryAttempt()
      const recoveredRecently = lastRecoveryAttempt?.version === currentVersion
        && Date.now() - lastRecoveryAttempt.timestamp < RECOVERY_TTL_MS

      if (!shouldRecoverAssetError(message) || recoveredRecently) {
        return
      }

      localStorage.setItem(RECOVERY_FLAG, `${currentVersion}:${Date.now()}`)

      void clearClientCaches().finally(() => {
        window.location.reload()
      })
    }

    const onError = (event: ErrorEvent) => {
      const target = event.target

      if (target instanceof HTMLScriptElement || target instanceof HTMLLinkElement) {
        recoverFromAssetError(target.outerHTML)
        return
      }

      recoverFromAssetError(event.message || '')
    }

    const onUnhandledRejection = (event: PromiseRejectionEvent) => {
      const reason = event.reason
      const message = reason instanceof Error
        ? reason.message
        : typeof reason === 'string'
          ? reason
          : JSON.stringify(reason)

      recoverFromAssetError(message)
    }

    window.addEventListener('error', onError, true)
    window.addEventListener('unhandledrejection', onUnhandledRejection)

    return () => {
      window.removeEventListener('error', onError, true)
      window.removeEventListener('unhandledrejection', onUnhandledRejection)
    }
  }, [])

  return null
}
