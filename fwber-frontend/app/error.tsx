'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { AlertTriangle, Home, RefreshCcw, Mail } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string }
  reset: () => void
}) {
  const router = useRouter()

  useEffect(() => {
    // Log error for debugging
    console.error('App error:', error)

    // TODO: Send error to error tracking service (Sentry, etc.)
    // reportError({ error, digest: error.digest, context: 'error-boundary' })
  }, [error])

  // Determine error type and messaging
  const isNetworkError = error.message.includes('fetch') ||
                         error.message.includes('network') ||
                         error.message.includes('Failed to fetch')

  const isAuthError = error.message.includes('401') ||
                      error.message.includes('403') ||
                      error.message.includes('Unauthorized') ||
                      error.message.includes('authentication')

  const getErrorTitle = () => {
    if (isNetworkError) return 'Connection Problem'
    if (isAuthError) return 'Authentication Error'
    return 'Something Went Wrong'
  }

  const getErrorDescription = () => {
    if (isNetworkError) {
      return 'We\'re having trouble connecting to our servers. Please check your internet connection and try again.'
    }
    if (isAuthError) {
      return 'Your session may have expired. Please log in again to continue.'
    }
    return 'An unexpected error occurred while loading this page. Don\'t worry, we\'ve been notified and are looking into it.'
  }

  const handleGoHome = () => {
    router.push('/')
  }

  const handleContactSupport = () => {
    // TODO: Update with actual support contact method
    window.location.href = 'mailto:support@fwber.me?subject=Error Report&body=' +
      encodeURIComponent(`Error: ${error.message}\nDigest: ${error.digest || 'N/A'}`)
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 to-pink-50 px-4 py-12">
      <Card className="max-w-lg w-full shadow-lg">
        <CardHeader className="text-center pb-4">
          <div className="mx-auto mb-4 w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
            <AlertTriangle className="w-8 h-8 text-red-600" />
          </div>
          <CardTitle className="text-2xl font-bold text-gray-900">
            {getErrorTitle()}
          </CardTitle>
          <CardDescription className="text-base mt-2">
            {getErrorDescription()}
          </CardDescription>
        </CardHeader>

        <CardContent className="space-y-4">
          {/* Error details (collapsible for technical users) */}
          <details className="group">
            <summary className="cursor-pointer text-sm text-gray-600 hover:text-gray-900 flex items-center gap-2">
              <span className="group-open:rotate-90 transition-transform">▶</span>
              Technical Details
            </summary>
            <div className="mt-2 p-3 bg-gray-100 rounded-md text-xs font-mono text-gray-800 overflow-auto max-h-32">
              <p className="break-all">{error.message}</p>
              {error.digest && (
                <p className="mt-2 text-gray-600">
                  Error ID: {error.digest}
                </p>
              )}
            </div>
          </details>

          {/* User guidance based on error type */}
          {isNetworkError && (
            <div className="p-3 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-800">
              <p className="font-semibold mb-1">Quick fixes to try:</p>
              <ul className="list-disc list-inside space-y-1 text-xs">
                <li>Check your internet connection</li>
                <li>Disable VPN if you're using one</li>
                <li>Try a different network</li>
                <li>Wait a moment and try again</li>
              </ul>
            </div>
          )}

          {isAuthError && (
            <div className="p-3 bg-amber-50 border border-amber-200 rounded-md text-sm text-amber-800">
              <p>For security, sessions expire after a period of inactivity. Please log in again to continue using FWBer.</p>
            </div>
          )}
        </CardContent>

        <CardFooter className="flex flex-col sm:flex-row gap-3">
          <Button
            onClick={reset}
            className="flex-1 bg-orange-600 hover:bg-orange-700"
          >
            <RefreshCcw className="w-4 h-4 mr-2" />
            Try Again
          </Button>
          <Button
            onClick={handleGoHome}
            variant="outline"
            className="flex-1"
          >
            <Home className="w-4 h-4 mr-2" />
            Go Home
          </Button>
          <Button
            onClick={handleContactSupport}
            variant="ghost"
            className="flex-1"
          >
            <Mail className="w-4 h-4 mr-2" />
            Contact Support
          </Button>
        </CardFooter>

        {/* Helpful tip */}
        <div className="px-6 pb-6 text-center">
          <p className="text-xs text-gray-500">
            If the problem persists, please contact our support team with Error ID: {error.digest || 'N/A'}
          </p>
        </div>
      </Card>
    </div>
  )
}
