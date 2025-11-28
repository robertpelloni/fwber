'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'
import SexQuote from '@/components/SexQuote'
import { Shield, Heart, Zap, MapPin, MessageCircle, Users, Menu, X } from 'lucide-react'
import { useState } from 'react'

export default function HomePage() {
  const { isAuthenticated, isLoading } = useAuth()
  const router = useRouter()
  const [isMenuOpen, setIsMenuOpen] = useState(false)

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
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <Link href="/" className="flex-shrink-0 flex items-center">
                <span className="text-2xl font-bold text-blue-600">FWBer</span>
                <span className="text-2xl font-bold text-gray-900">.me</span>
              </Link>
            </div>
            
            {/* Desktop Menu */}
            <div className="hidden md:flex items-center space-x-8">
              <Link href="#features" className="text-gray-600 hover:text-blue-600 font-medium">Features</Link>
              <Link href="#safety" className="text-gray-600 hover:text-blue-600 font-medium">Safety</Link>
              <div className="flex items-center space-x-4">
                <Link href="/login" className="text-gray-900 hover:text-blue-600 font-medium">
                  Log in
                </Link>
                <Link href="/register" className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-full font-medium transition-colors">
                  Sign up
                </Link>
              </div>
            </div>

            {/* Mobile menu button */}
            <div className="md:hidden flex items-center">
              <button
                onClick={() => setIsMenuOpen(!isMenuOpen)}
                className="text-gray-600 hover:text-gray-900 focus:outline-none"
              >
                {isMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Menu */}
        {isMenuOpen && (
          <div className="md:hidden bg-white border-t border-gray-100 py-4">
            <div className="flex flex-col space-y-4 px-4">
              <Link href="#features" className="text-gray-600 font-medium">Features</Link>
              <Link href="#safety" className="text-gray-600 font-medium">Safety</Link>
              <Link href="/login" className="text-gray-900 font-medium">Log in</Link>
              <Link href="/register" className="bg-blue-600 text-white px-5 py-2.5 rounded-lg font-medium text-center">
                Sign up
              </Link>
            </div>
          </div>
        )}
      </nav>

      {/* Hero Section */}
      <div className="relative overflow-hidden bg-gradient-to-b from-blue-50 to-white pt-16 pb-24 lg:pt-32 lg:pb-40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div className="text-center max-w-4xl mx-auto">
            <h1 className="text-5xl md:text-7xl font-extrabold text-gray-900 tracking-tight mb-8">
              Find Your <span className="text-blue-600">Perfect Match</span> Tonight
            </h1>
            
            <div className="mb-8 transform hover:scale-105 transition-transform duration-300">
              <p className="text-2xl md:text-3xl text-orange-600 font-serif italic">
                &ldquo;Everybody wants to get laid.&rdquo;
              </p>
            </div>

            <div className="w-full max-w-2xl mx-auto mb-10 bg-white/50 backdrop-blur-sm rounded-xl p-6 shadow-sm">
              <SexQuote />
            </div>

            <p className="text-xl text-gray-600 mb-10 max-w-2xl mx-auto leading-relaxed">
              Join the most advanced adult dating platform. Smart matching, verified profiles, and real connections waiting for you.
            </p>
            
            <div className="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
              <Link
                href="/register"
                className="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded-full text-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center"
              >
                <Heart className="w-5 h-5 mr-2 fill-current" />
                Start Dating Now
              </Link>
              <Link
                href="/login"
                className="w-full sm:w-auto bg-white hover:bg-gray-50 text-gray-900 font-bold py-4 px-10 rounded-full text-lg border-2 border-gray-200 hover:border-blue-600 transition-all duration-200"
              >
                Member Login
              </Link>
            </div>
          </div>
        </div>
        
        {/* Decorative blobs */}
        <div className="absolute top-0 left-0 -ml-20 -mt-20 w-96 h-96 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
        <div className="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
      </div>

      {/* Features Section */}
      <div id="features" className="py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose FWBer?</h2>
            <p className="text-xl text-gray-600">Everything you need to find exactly what you&apos;re looking for.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-blue-50 transition-colors duration-300">
              <div className="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-6 text-blue-600">
                <Zap className="w-8 h-8" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Smart Matching</h3>
              <p className="text-gray-600 leading-relaxed">
                Our advanced algorithm connects you with people who share your interests and desires. No more wasted time.
              </p>
            </div>

            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-blue-50 transition-colors duration-300">
              <div className="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-6 text-purple-600">
                <MapPin className="w-8 h-8" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Local Connections</h3>
              <p className="text-gray-600 leading-relaxed">
                Find matches in your area instantly. Our location-based features help you meet people nearby.
              </p>
            </div>

            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-blue-50 transition-colors duration-300">
              <div className="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-6 text-green-600">
                <Shield className="w-8 h-8" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Verified & Safe</h3>
              <p className="text-gray-600 leading-relaxed">
                Your safety is our priority. Verified profiles and secure messaging keep your experience worry-free.
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Safety Section */}
      <div id="safety" className="py-24 bg-gray-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row items-center justify-between">
            <div className="md:w-1/2 mb-12 md:mb-0">
              <h2 className="text-3xl md:text-4xl font-bold mb-6">Your Privacy Matters</h2>
              <p className="text-xl text-gray-300 mb-8 leading-relaxed">
                We use state-of-the-art encryption and security measures to protect your data. 
                You&apos;re in control of what you share and who sees it.
              </p>
              <ul className="space-y-4">
                <li className="flex items-center text-gray-300">
                  <Shield className="w-6 h-6 text-green-400 mr-3" />
                  <span>End-to-end encryption for messages</span>
                </li>
                <li className="flex items-center text-gray-300">
                  <Users className="w-6 h-6 text-blue-400 mr-3" />
                  <span>Verified profiles only</span>
                </li>
                <li className="flex items-center text-gray-300">
                  <MessageCircle className="w-6 h-6 text-purple-400 mr-3" />
                  <span>Private and secure chat</span>
                </li>
              </ul>
            </div>
            <div className="md:w-5/12">
              <div className="bg-gray-800 p-8 rounded-2xl border border-gray-700">
                <h3 className="text-2xl font-bold mb-4">Join 50,000+ Members</h3>
                <p className="text-gray-400 mb-8">
                  Create your free account today and start exploring matches in your area.
                </p>
                <Link
                  href="/register"
                  className="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-4 rounded-xl transition-colors"
                >
                  Create Free Account
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-gray-50 border-t border-gray-200 py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-8 md:mb-0">
              <span className="text-2xl font-bold text-gray-900">FWBer.me</span>
              <p className="text-gray-500 mt-2">© 2025 FWBer. All rights reserved.</p>
            </div>
            <div className="flex space-x-8">
              <Link href="/privacy" className="text-gray-600 hover:text-gray-900">Privacy Policy</Link>
              <Link href="/terms" className="text-gray-600 hover:text-gray-900">Terms of Service</Link>
              <Link href="/contact" className="text-gray-600 hover:text-gray-900">Contact Us</Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  )
}
