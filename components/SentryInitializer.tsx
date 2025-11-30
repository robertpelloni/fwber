'use client'

import { useEffect } from 'react'
import { register } from '@/instrumentation-client'

export default function SentryInitializer() {
  useEffect(() => {
    register()
  }, [])

  return null
}
