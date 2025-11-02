'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'

export default function HomePage() {
  const { isAuthenticated, isLoading } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      router.push('/dashboard')
    }
  }, [isAuthenticated, isLoading, router])

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-500"></div>
      </div>
    )
  }

  if (isAuthenticated) {
    return null // Will redirect to dashboard
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col items-center justify-center min-h-screen text-center">
          <h1 className="text-6xl font-bold text-gray-900 mb-6">
            Welcome to <span className="text-blue-600">FWBer.me</span>
          </h1>
          <p className="text-xl text-gray-600 mb-8 max-w-2xl">
            A modern adult dating platform with advanced matching algorithms. 
            Connect with compatible people and build meaningful relationships.
          </p>
          
          <div className="flex space-x-4">
            <Link
              href="/register"
              className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition duration-200"
            >
              Get Started
            </Link>
            <Link
              href="/login"
              className="bg-white hover:bg-gray-50 text-blue-600 font-bold py-3 px-8 rounded-lg text-lg border-2 border-blue-600 transition duration-200"
            >
              Sign In
            </Link>
          </div>

          <div className="mt-12">
            <Link
              href="/test-auth"
              className="text-sm text-gray-500 hover:text-gray-700"
            >
              API Test Page
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}
