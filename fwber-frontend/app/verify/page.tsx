'use client'

import { useEffect } from 'react'
import { useSearchParams, useRouter } from 'next/navigation'

export default function VerifyRedirectPage() {
  const searchParams = useSearchParams()
  const router = useRouter()

  useEffect(() => {
    const token = searchParams.get('token')
    if (token) {
      router.replace(`/verify-email?token=${token}`)
    } else {
      router.replace('/verify-email')
    }
  }, [searchParams, router])

  return null
}
