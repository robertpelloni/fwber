import * as Sentry from '@sentry/nextjs'

/**
 * Server/edge Sentry bootstrap for the Next.js App Router runtime.
 *
 * Why this exists:
 * - Next.js loads `instrumentation.ts` for server and edge runtimes
 * - Sentry's modern setup expects the request-error hook to be exported here
 * - centralizing runtime bootstrap removes the old half-disabled placeholder logic
 */
export async function register() {
  if (!process.env.NEXT_PUBLIC_SENTRY_DSN) {
    return
  }

  if (process.env.NEXT_RUNTIME === 'nodejs') {
    await import('./sentry.server.config')
    return
  }

  if (process.env.NEXT_RUNTIME === 'edge') {
    await import('./sentry.edge.config')
  }
}

/**
 * Capture nested React Server Component and App Router request errors with the
 * hook shape expected by the current Sentry Next.js SDK.
 */
export const onRequestError = Sentry.captureRequestError

