'use client'

import { useEffect, useRef } from 'react'
// import * as Sentry from '@sentry/nextjs'

export default function SentryInitializer() {
  const initialized = useRef(false)

  useEffect(() => {
    if (!initialized.current) {
      /*
      const clientOptions: Sentry.BrowserOptions = {
        dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
        tracesSampleRate: 1.0,
        debug: false,
        replaysOnErrorSampleRate: 1.0,
        replaysSessionSampleRate: 0.1,
        integrations: [
          Sentry.browserTracingIntegration({
            traceFetch: false,
            traceXHR: false,
          }),
          Sentry.replayIntegration({
            maskAllText: true,
            blockAllMedia: true,
          }),
        ],
        beforeSend(event, hint) {
          const error = hint?.originalException;
          if (error && typeof error === 'object' && 'message' in error) {
            const message = String((error as { message?: string }).message ?? '').toLowerCase();
            if (
              message.includes('authentication required') ||
              message.includes('not authenticated') ||
              message.includes('unauthenticated')
            ) {
              return null;
            }
          }
      
          return event;
        },
      };

      Sentry.init(clientOptions);
      */
      initialized.current = true
    }
  }, [])

  return null
}
