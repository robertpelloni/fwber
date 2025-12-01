'use client'

import { useEffect, useRef } from 'react'
import { register } from '@/instrumentation-client'

export default function SentryInitializer() {
  const initialized = useRef(false)

  useEffect(() => {
    if (!initialized.current) {
      register()
      initialized.current = true
    }
  }, [])

  return null
}
