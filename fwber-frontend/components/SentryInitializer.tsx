'use client'

import { useEffect, useRef } from 'react'

export default function SentryInitializer() {
  const initialized = useRef(false)

  useEffect(() => {
    if (!initialized.current) {
      // Unregister stale service workers in dev to prevent cached Sentry bundles
      if (process.env.NODE_ENV === 'development' && 'serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
          registrations.forEach(reg => reg.unregister())
        })
      }
      initialized.current = true
    }
  }, [])

  return null
}
