'use client'

import { useEffect } from 'react'

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  useEffect(() => {
    // Log error for debugging
    console.error('Global error:', error)

    // TODO: Send error to error tracking service (Sentry, etc.)
    // reportError({ error, digest: error.digest, context: 'global-error-boundary' })
  }, [error])

  // Determine error type
  const isNetworkError = error.message.includes('fetch') ||
                         error.message.includes('network') ||
                         error.message.includes('Failed to fetch')

  const getErrorTitle = () => {
    if (isNetworkError) return 'Connection Problem'
    return 'Critical Error'
  }

  const getErrorDescription = () => {
    if (isNetworkError) {
      return 'We\'re having trouble connecting to our servers. Please check your internet connection and try again.'
    }
    return 'A critical error occurred while loading the application. Our team has been notified and is working on a fix.'
  }

  const handleReload = () => {
    window.location.href = '/'
  }

  const handleContactSupport = () => {
    window.location.href = 'mailto:support@fwber.me?subject=Critical Error Report&body=' +
      encodeURIComponent(`Critical Error: ${error.message}\nDigest: ${error.digest || 'N/A'}`)
  }

  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Error - FWBer</title>
        <style dangerouslySetInnerHTML={{
          __html: `
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; }
            .container { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #fff5f0 0%, #ffe4e6 100%); padding: 1rem; }
            .card { background: white; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); max-width: 36rem; width: 100%; padding: 2rem; }
            .icon-container { width: 4rem; height: 4rem; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
            .icon { width: 2rem; height: 2rem; color: #dc2626; }
            h1 { font-size: 1.875rem; font-weight: 700; color: #111827; text-align: center; margin-bottom: 1rem; }
            p { color: #6b7280; text-align: center; margin-bottom: 2rem; line-height: 1.625; }
            .details { margin-bottom: 1.5rem; }
            .details summary { cursor: pointer; font-size: 0.875rem; color: #6b7280; padding: 0.5rem; user-select: none; }
            .details summary:hover { color: #111827; }
            .details-content { margin-top: 0.5rem; padding: 0.75rem; background: #f3f4f6; border-radius: 0.5rem; font-size: 0.75rem; font-family: monospace; color: #374151; max-height: 8rem; overflow: auto; word-break: break-all; }
            .hint { background: #dbeafe; border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1.5rem; }
            .hint p { color: #1e40af; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; text-align: left; }
            .hint ul { color: #1e3a8a; font-size: 0.75rem; list-style: disc; margin-left: 1.5rem; text-align: left; }
            .hint li { margin-bottom: 0.25rem; }
            .buttons { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
            @media (min-width: 640px) { .buttons { flex-direction: row; } }
            button { flex: 1; padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; }
            .btn-primary { background: #ea580c; color: white; }
            .btn-primary:hover { background: #c2410c; }
            .btn-secondary { background: white; color: #374151; border: 1px solid #d1d5db; }
            .btn-secondary:hover { background: #f9fafb; }
            .btn-ghost { background: transparent; color: #6b7280; }
            .btn-ghost:hover { background: #f9fafb; color: #111827; }
            .footer { text-align: center; font-size: 0.75rem; color: #9ca3af; }
          `
        }} />
      </head>
      <body>
        <div className="container">
          <div className="card">
            <div className="icon-container">
              <svg className="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>

            <h1>{getErrorTitle()}</h1>
            <p>{getErrorDescription()}</p>

            <details className="details">
              <summary>▶ Technical Details</summary>
              <div className="details-content">
                <div>{error.message}</div>
                {error.digest && (
                  <div style={{ marginTop: '0.5rem', color: '#6b7280' }}>
                    Error ID: {error.digest}
                  </div>
                )}
              </div>
            </details>

            {isNetworkError && (
              <div className="hint">
                <p>Quick fixes to try:</p>
                <ul>
                  <li>Check your internet connection</li>
                  <li>Disable VPN if you're using one</li>
                  <li>Try a different network</li>
                  <li>Wait a moment and try again</li>
                </ul>
              </div>
            )}

            <div className="buttons">
              <button onClick={reset} className="btn-primary">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Try Again
              </button>
              <button onClick={handleReload} className="btn-secondary">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Go Home
              </button>
              <button onClick={handleContactSupport} className="btn-ghost">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Contact Support
              </button>
            </div>

            <div className="footer">
              If the problem persists, please contact our support team with Error ID: {error.digest || 'N/A'}
            </div>
          </div>
        </div>
      </body>
    </html>
  )
}
