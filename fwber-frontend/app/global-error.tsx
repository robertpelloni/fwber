'use client'
 
import { useEffect } from 'react'

// TODO: Re-enable Sentry when @sentry/nextjs is compatible with Next.js 16 + Turbopack
// import * as Sentry from "@sentry/nextjs"
 
export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    // Sentry.captureException(error)
    console.error('Global error:', error)
  }, [error])
 
  return (
    <html>
      <body>
        <h2>Something went wrong!</h2>
        <button onClick={() => reset()}>Try again</button>
      </body>
    </html>
  )
}
