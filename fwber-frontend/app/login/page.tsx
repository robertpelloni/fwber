'use client'

import { useState, useEffect } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter, useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { ThemeToggle } from '@/components/ThemeToggle'
import { useWallet } from '@solana/wallet-adapter-react'
import { WalletMultiButton } from '@solana/wallet-adapter-react-ui'
import { FederatedAuthModal } from '@/components/auth/FederatedAuthModal'
import { Globe } from 'lucide-react'
import bs58 from 'bs58'

export default function LoginPage() {
  const searchParams = useSearchParams()
  const sessionExpired = searchParams.get('reason') === 'session_expired'
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [twoFactorCode, setTwoFactorCode] = useState('')
  const [recoveryCode, setRecoveryCode] = useState('')
  const [isRecovery, setIsRecovery] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [isFedModalOpen, setIsFedModalOpen] = useState(false)
  const { login, loginWithWallet, verifyTwoFactor, error, clearError, isAuthenticated, requiresTwoFactor } = useAuth()
  const router = useRouter()
  const { publicKey, signMessage } = useWallet()

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated) {
      const user = localStorage.getItem('fwber_user')
      const parsed = user ? JSON.parse(user) : {}
      const needsOnboarding = !parsed.onboarding_completed_at
      const destination = needsOnboarding ? '/onboarding' : '/dashboard'

      // Use setTimeout to avoid blocking the render cycle and ensure state is settled
      const timer = setTimeout(() => {
        router.push(destination)
      }, 100)

      // Safety fallback: if we are still here after 3s, force hard navigation
      const fallbackTimer = setTimeout(() => {
        console.warn('Router push stalled. Forcing hard navigation.');
        window.location.href = destination;
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

      await login(email, password)

      // If 2FA is required, the UI will update automatically due to requiresTwoFactor
    } catch (error) {
      console.error('Login error:', error)
      // Error is handled by the auth context
      setIsLoading(false)
    }
    // Note: We don't set isLoading(false) in finally block if successful
    // to prevent UI flash before redirect
  }

  const handleWalletLogin = async () => {
    if (!publicKey || !signMessage) return
    setIsLoading(true)
    clearError()

    try {
      const message = `Sign this message to login to fwber.\n\nWallet: ${publicKey.toBase58()}\nTimestamp: ${Date.now()}`
      const encodedMessage = new TextEncoder().encode(message)
      const signature = await signMessage(encodedMessage)

      await loginWithWallet(
        publicKey.toBase58(),
        bs58.encode(signature),
        message
      )
    } catch (error) {
      console.error('Wallet login failed:', error)
    } finally {
      setIsLoading(false)
    }
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
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 dark:from-gray-950 dark:via-gray-900 dark:to-violet-950/30 py-12 px-4 sm:px-6 lg:px-8 relative">
        <div className="absolute top-4 right-4">
          <ThemeToggle />
        </div>
        <div className="max-w-md w-full space-y-8">
          <div className="orb orb-purple absolute top-0 right-0 w-96 h-96" />
          <div className="orb orb-pink absolute bottom-0 left-0 w-80 h-80" />
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

            {sessionExpired && (
              <div className="rounded-md bg-amber-50 dark:bg-amber-900/20 p-4">
                <div className="text-sm text-amber-700 dark:text-amber-400">Your session has expired. Please log in again.</div>
              </div>
            )}

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
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 dark:from-gray-950 dark:via-gray-900 dark:to-violet-950/30 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
      <div className="orb orb-purple absolute -top-20 -right-20 w-[500px] h-[500px] animate-blob" />
      <div className="orb orb-pink absolute -bottom-20 -left-20 w-[400px] h-[400px] animate-blob" style={{ animationDelay: '3s' }} />
      <div className="absolute top-4 right-4 z-10">
        <ThemeToggle />
      </div>
      <div className="max-w-md w-full space-y-8 relative z-10">
        <div className="glass rounded-2xl p-8 shadow-premium-lg border border-white/20 dark:border-white/5">
          <h2 className="text-center text-3xl font-black text-gray-900 dark:text-white mb-2">
            Sign in to <span className="text-gradient-pink">FWBer</span>
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Or{' '}
            <Link
              href="/register"
              prefetch={false}
              className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
            >
              create a new account
            </Link>
          </p>
        </div>

        <form className="mt-6 space-y-5" onSubmit={handleSubmit}>
          <div className="space-y-3">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Email address
              </label>
              <input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
                required
                className="block w-full rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 hover:border-purple-300/50"
                placeholder="you@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Password
              </label>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                required
                className="block w-full rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm px-4 py-3 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 hover:border-purple-300/50"
                placeholder="• • • • • • • •"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
            <div className="flex justify-end">
              <Link href="/forgot-password" className="text-sm text-purple-600 hover:text-purple-500 dark:text-purple-400 font-medium transition-colors">
                Forgot password?
              </Link>
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
              className="group relative w-full btn-shiny rounded-xl bg-gradient-to-r from-pink-500 via-purple-500 to-cyan-500 bg-[length:200%_auto] animate-gradient-x py-3 px-4 text-sm font-bold text-white shadow-lg hover:shadow-xl hover:shadow-purple-500/25 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 active:scale-[0.98]"
            >
              {isLoading ? (
                <span className="flex items-center justify-center gap-2">
                  <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  Signing in...
                </span>
              ) : 'Sign in'}
            </button>
          </div>

          <div className="text-center">
            <Link
              href="/test-auth"
              prefetch={false}
              className="text-sm text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
            >
              API Test Page
            </Link>
          </div>

          <div className="relative">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-white/20 dark:border-white/10"></div>
            </div>
            <div className="relative flex justify-center text-sm">
              <span className="px-3 glass-light rounded-full text-gray-500 dark:text-gray-400 text-xs font-medium">
                Or continue with
              </span>
            </div>
          </div>

          <div className="space-y-3">
            <button
              type="button"
              onClick={() => setIsFedModalOpen(true)}
              className="group relative w-full flex justify-center items-center py-3 px-4 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-white/80 dark:hover:bg-gray-900/80 hover:shadow-md transition-all duration-200"
            >
              <Globe className="w-4 h-4 mr-2 text-purple-500" />
              Sign in with ActivityPub
            </button>

            <div className="flex justify-center">
              {!publicKey ? (
                <WalletMultiButton />
              ) : (
                <button
                  type="button"
                  onClick={handleWalletLogin}
                  disabled={isLoading}
                  className="group relative w-full flex justify-center py-3 px-4 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-sm font-bold text-white shadow-lg hover:shadow-xl hover:shadow-purple-500/25 disabled:opacity-50 transition-all duration-200 active:scale-[0.98]"
                >
                  {isLoading ? 'Verifying Wallet...' : 'Login with Wallet'}
                </button>
              )}
            </div>
          </div>

        </form>
      </div>

      <FederatedAuthModal isOpen={isFedModalOpen} onOpenChange={setIsFedModalOpen} />
    </div>
  )
}
