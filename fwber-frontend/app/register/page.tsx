'use client'

import { useState, useEffect, Suspense } from 'react'
import { useAuth } from '@/lib/auth-context'
import { useRouter, useSearchParams } from 'next/navigation'
import Link from 'next/link'
import Image from 'next/image'
import { blurFacesOnFile } from '@/lib/faceBlur'
import { isFeatureEnabled } from '@/lib/featureFlags'
import { ThemeToggle } from '@/components/ThemeToggle'
import { Ticket, Loader2 } from 'lucide-react'

function RegisterForm() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    referralCode: '',
  })
  const [avatar, setAvatar] = useState<File | null>(null)
  const [previewUrl, setPreviewUrl] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [validationError, setValidationError] = useState<string | null>(null)
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const [referrer, setReferrer] = useState<{ name: string, avatar?: string, hasGoldenTicket?: boolean } | null>(null)
  
  const { register, error, clearError, isAuthenticated } = useAuth()
  const router = useRouter()
  const searchParams = useSearchParams()

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated) {
      // Use setTimeout to avoid blocking the render cycle
      const timer = setTimeout(() => {
        router.push('/dashboard')
      }, 100)
      return () => clearTimeout(timer)
    }
  }, [isAuthenticated, router])

  // Check for referral code
  useEffect(() => {
    const ref = searchParams.get('ref')
    if (ref) {
      setFormData(prev => ({ ...prev, referralCode: ref }))
      
      // Verify referral code
      fetch(`${process.env.NEXT_PUBLIC_API_URL}/auth/referral/${ref}`)
        .then(res => res.json())
        .then(data => {
          if (data.valid) {
            setReferrer({
              name: data.referrer_name,
              avatar: data.referrer_avatar,
              hasGoldenTicket: data.has_golden_tickets
            })
          }
        })
        .catch(console.error)
    }
  }, [searchParams])

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    })
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0]
      setAvatar(file)
      
      if (previewUrl) {
        URL.revokeObjectURL(previewUrl)
      }
      setPreviewUrl(URL.createObjectURL(file))
    }
  }

  useEffect(() => {
    return () => {
      if (previewUrl) {
        URL.revokeObjectURL(previewUrl)
      }
    }
  }, [previewUrl])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (isLoading) return

    setIsLoading(true)
    clearError()
    setValidationError(null)
    setSuccessMessage(null)

    // Client-side validation
    if (formData.password !== formData.passwordConfirmation) {
      setValidationError('Passwords do not match')
      setIsLoading(false)
      return
    }

    if (formData.password.length < 8) {
      setValidationError('Password must be at least 8 characters')
      setIsLoading(false)
      return
    }

    try {
      let finalAvatar = avatar
      if (finalAvatar && isFeatureEnabled('clientFaceBlur')) {
        try {
          const result = await blurFacesOnFile(finalAvatar)
          if (result.blurred) {
            finalAvatar = result.file
          }
        } catch (e) {
          console.warn('Face blur failed, proceeding with original', e)
        }
      }

      await register(formData.name, formData.email, formData.password, formData.passwordConfirmation, finalAvatar, formData.referralCode)
      setSuccessMessage('Account created successfully! Redirecting...')
      // Note: The useEffect hook will handle the redirect since isAuthenticated becomes true
    } catch (error) {
      setValidationError(error instanceof Error ? error.message : 'Registration failed. Please try again.')
      setIsLoading(false)
    }
    // Note: We don't set isLoading(false) in finally block if successful
  }

  return (
    <div className="max-w-md w-full space-y-8">
      <div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Create your <span className="text-transparent bg-clip-text bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 animate-gradient-chaos">FWBer</span> account
        </h2>
        <p className="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
          Or{' '}
          <Link
            href="/login"
            className="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
          >
            sign in to your existing account
          </Link>
        </p>
      </div>

      {referrer && (
        <div className="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800 animate-in fade-in slide-in-from-top-4 duration-500">
          <div className="flex items-center gap-4">
            {referrer.avatar ? (
              <div className="relative w-12 h-12 rounded-full overflow-hidden border-2 border-amber-400">
                <Image src={referrer.avatar} alt={referrer.name} fill className="object-cover" />
              </div>
            ) : (
              <div className="w-12 h-12 rounded-full bg-amber-200 flex items-center justify-center text-amber-800 font-bold text-xl">
                {referrer.name.charAt(0)}
              </div>
            )}
            <div>
              <p className="text-sm font-medium text-amber-900 dark:text-amber-100">
                You&apos;ve been invited by <span className="font-bold">{referrer.name}</span>!
              </p>
              {referrer.hasGoldenTicket && (
                <div className="flex items-center gap-1 text-xs text-amber-700 dark:text-amber-300 mt-1">
                  <Ticket className="w-3 h-3" />
                  <span>Includes 3 Days of Gold Premium</span>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
      
      <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
        <div className="space-y-4">
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nickname (or first name)
            </label>
            <input
              id="name"
              name="name"
              type="text"
              autoComplete="given-name"
              required
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="What should we call you?"
              value={formData.name}
              onChange={handleChange}
            />
          </div>
          
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Email Address
            </label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="email"
              required
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Enter your email"
              value={formData.email}
              onChange={handleChange}
            />
          </div>

          <div>
            <label htmlFor="avatar" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Profile Photo (Optional)
            </label>
            <input
              id="avatar"
              name="avatar"
              type="file"
              accept="image/*"
              className="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-blue-50 file:text-blue-700
                dark:file:bg-blue-900/20 dark:file:text-blue-400
                hover:file:bg-blue-100 dark:hover:file:bg-blue-900/30"
              onChange={handleFileChange}
            />
            {previewUrl && (
              <div className="mt-4">
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">Preview:</p>
                <div className="relative w-32 h-32 rounded-full overflow-hidden border-2 border-gray-200 dark:border-gray-700">
                  <Image
                    src={previewUrl}
                    alt="Avatar preview"
                    fill
                    className="object-cover"
                  />
                </div>
              </div>
            )}
          </div>

          <div>
            <label htmlFor="referralCode" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Referral Code (Optional)
            </label>
            <input
              id="referralCode"
              name="referralCode"
              type="text"
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Enter referral code if you have one"
              value={formData.referralCode}
              onChange={handleChange}
            />
            <p className="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
              Both you and your referrer earn <span className="font-semibold text-amber-600 dark:text-amber-400">50 bonus tokens</span>! After signing up, find your own code in Settings to invite friends.
            </p>
          </div>
          
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Password
            </label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="new-password"
              required
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Create a password"
              value={formData.password}
              onChange={handleChange}
            />
          </div>
          
          <div>
            <label htmlFor="passwordConfirmation" className="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Confirm Password
            </label>
            <input
              id="passwordConfirmation"
              name="passwordConfirmation"
              type="password"
              autoComplete="new-password"
              required
              className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-800 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              placeholder="Confirm your password"
              value={formData.passwordConfirmation}
              onChange={handleChange}
            />
          </div>
        </div>

        {successMessage && (
          <div className="rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <div className="text-sm font-medium text-green-800 dark:text-green-400">{successMessage}</div>
          </div>
        )}

        {(error || validationError) && (
          <div className="rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <div className="text-sm font-medium text-red-800 dark:text-red-400">{validationError || error}</div>
          </div>
        )}

        <div>
          <button
            type="submit"
            disabled={isLoading}
            className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Creating account...' : 'Create account'}
          </button>
        </div>
      </form>
    </div>
  )
}

export default function RegisterPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 relative">
      <div className="absolute top-4 right-4">
        <ThemeToggle />
      </div>
      <Suspense fallback={
        <div className="flex flex-col items-center justify-center space-y-4">
          <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
          <p className="text-sm text-gray-500">Loading registration...</p>
        </div>
      }>
        <RegisterForm />
      </Suspense>
    </div>
  )
}
