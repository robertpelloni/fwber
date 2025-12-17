'use client'

import { useState, useEffect } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { ThemeToggle } from '@/components/ThemeToggle'

export default function LoginPage() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [twoFactorCode, setTwoFactorCode] = useState('')
  const [recoveryCode, setRecoveryCode] = useState('')
  const [isRecovery, setIsRecovery] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { login, verifyTwoFactor, error, clearError, isAuthenticated, requiresTwoFactor } = useAuth()
  const router = useRouter()

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated) {
      // Use setTimeout to avoid blocking the render cycle and ensure state is settled
      const timer = setTimeout(() => {
        router.push('/dashboard')
      }, 100)

      // Safety fallback: if we are still here after 3s, force hard navigation
      const fallbackTimer = setTimeout(() => {
         console.warn('Router push stalled. Forcing hard navigation.');
         window.location.href = '/dashboard';
      }, 3000);

      return () => {
        clearTimeout(timer);
        clearTimeout(fallbackTimer);
      }
    }
  }, [isAuthenticated, router])

  // Reset loading state when 2FA is required
  useEffect(() => {
    if (requiresTwoFactor) {
      setIsLoading(false)
    }
  }, [requiresTwoFactor])

  // Safety timeout for loading state
  useEffect(() => {
    if (isLoading) {
      const timer = setTimeout(() => {
        if (!isAuthenticated && !requiresTwoFactor) {
           setIsLoading(false);
           console.warn('Login timed out or stalled.');
        }
      }, 15000);
      return () => clearTimeout(timer);
    }
  }, [isLoading, isAuthenticated, requiresTwoFactor]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (isLoading) return

    setIsLoading(true)
    clearError()

    try {
      console.log('Attempting login...');
      await login(email, password)
      console.log('Login successful, waiting for redirect or 2FA...');
      // If 2FA is required, the UI will update automatically due to requiresTwoFactor
    } catch (error) {
      console.error('Login error:', error)
      // Error is handled by the auth context
      setIsLoading(false)
    }
    // Note: We don't set isLoading(false) in finally block if successful
    // to prevent UI flash before redirect
  }

  const handleTwoFactorSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (isLoading) return

    setIsLoading(true)
    clearError()

    try {
      await verifyTwoFactor(twoFactorCode, isRecovery ? recoveryCode : undefined)
      // If successful, isAuthenticated becomes true and the useEffect redirects
    } catch (error) {
      console.error('2FA error:', error)
      // Error is handled by the auth context
      setIsLoading(false)
    }
    // Note: We don't set isLoading(false) in finally block if successful
  }

  if (requiresTwoFactor) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 relative">
        <div className="absolute top-4 right-4">
          <ThemeToggle />
        </div>
        <div className="max-w-md w-full space-y-8">
          <div>
            <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
              Two-Factor Authentication
            </h2>
            <p className="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
              Please confirm access to your account by entering the authentication code provided by your authenticator application.
            </p>
          </div>

          <form className="mt-8 space-y-6" onSubmit={handleTwoFactorSubmit}>
            <div className="rounded-md shadow-sm -space-y-px">
              {!isRecovery ? (
                <div>
                  <label htmlFor="code" className="sr-only">
                    Code
                  </label>
                  <input
                    id="code"
                    name="code"
                    type="text"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    required
                    className="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                    placeholder="Authentication Code"
                    value={twoFactorCode}
                    onChange={(e) => setTwoFactorCode(e.target.value)}
                  />
                </div>
              ) : (
                <div>
                  <label htmlFor="recovery_code" className="sr-only">
                    Recovery Code
                  </label>
                  <input
                    id="recovery_code"
                    name="recovery_code"
                    type="text"
                    autoComplete="off"
                    required
                    className="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                    placeholder="Recovery Code"
                    value={recoveryCode}
                    onChange={(e) => setRecoveryCode(e.target.value)}
                  />
                </div>
              )}
            </div>

            {error && (
              <div className="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
                <div className="text-sm text-red-700 dark:text-red-400">{error}</div>
              </div>
            )}

            <div>
              <button
                type="submit"
                disabled={isLoading}
                className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isLoading ? 'Verifying...' : 'Verify'}
              </button>
            </div>

            <div className="text-center">
              <button
                type="button"
                className="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
                onClick={() => setIsRecovery(!isRecovery)}
              >
                {isRecovery
                  ? 'Use an authentication code'
                  : 'Use a recovery code'}
              </button>
            </div>
          </form>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 relative">
      <div className="absolute top-4 right-4">
        <ThemeToggle />
      </div>
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Sign in to FWBer
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Or{' '}
            <Link
              href="/register"
              className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
            >
              create a new account
            </Link>
          </p>
        </div>
        
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          <div className="rounded-md shadow-sm -space-y-px">
            <div>
              <label htmlFor="email" className="sr-only">
                Email address
              </label>
              <input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
                required
                className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Email address"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            <div>
              <label htmlFor="password" className="sr-only">
                Password
              </label>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                required
                className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>

          {error && (
            <div className="rounded-md bg-red-50 dark:bg-red-900/20 p-4">
              <div className="text-sm text-red-700 dark:text-red-400">{error}</div>
            </div>
          )}

          <div>
            <button
              type="submit"
              disabled={isLoading}
              className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? 'Signing in...' : 'Sign in'}
            </button>
          </div>

          <div className="text-center">
            <Link
              href="/test-auth"
              className="text-sm text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
            >
              API Test Page
            </Link>
          </div>
        </form>
      </div>
    </div>
  )
}
