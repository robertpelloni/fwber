'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'
import SexQuote from '@/components/SexQuote'
import { Shield, Heart, Zap, MapPin, MessageCircle, Users, Menu, X, Lock, Ghost, Layers, FileText, Code, Building2 } from 'lucide-react'
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
              <Link href="/" className="flex-shrink-0 flex items-center gap-0.5 hover:scale-105 transition-transform duration-200">
                <span className="text-4xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-pink-500 to-pink-700 [-webkit-text-stroke:1.5px_black] drop-shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">F</span>
                <span className="text-4xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-green-400 to-green-600 [-webkit-text-stroke:1.5px_black] drop-shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">W</span>
                <span className="text-4xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-cyan-400 to-blue-600 [-webkit-text-stroke:1.5px_black] drop-shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">B</span>
                <span className="text-4xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-purple-500 to-purple-700 [-webkit-text-stroke:1.5px_black] drop-shadow-[2px_2px_0px_rgba(0,0,0,0.2)]">er</span>
                <span className="text-xl font-bold text-gray-400 ml-1 self-end mb-1">.me</span>
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

      {/* Notable Features Section */}
      <div id="features" className="py-24 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Notable Features</h2>
            <p className="text-xl text-gray-600">Unique tools designed for the modern dating experience.</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {/* Proximity */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-blue-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-6 text-blue-600">
                <MapPin className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Real-Time Proximity</h3>
              <p className="text-gray-600">
                See who&apos;s nearby right now. Our proximity artifacts let you discover matches within walking distance.
              </p>
            </div>

            {/* AI Avatars */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-purple-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-6 text-purple-600">
                <Ghost className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">No Embarrassment</h3>
              <p className="text-gray-600">
                Browse anonymously with AI-generated avatars. Reveal your true self only when you&apos;re ready.
              </p>
            </div>

            {/* Gamified Tiers */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-pink-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center mb-6 text-pink-600">
                <Layers className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Gamified Tiers</h3>
              <p className="text-gray-600">
                Unlock more features as you build trust. Progress from Discovery to Verified status to access exclusive perks.
              </p>
            </div>

            {/* Backpage Replacement */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-orange-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6 text-orange-600">
                <FileText className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Classifieds Are Back</h3>
              <p className="text-gray-600">
                Miss the old days? Our Bulletin Boards bring back the classic, no-nonsense classifieds experience you loved.
              </p>
            </div>

            {/* Client-side Encryption */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-green-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-6 text-green-600">
                <Lock className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Client-Side Encryption</h3>
              <p className="text-gray-600">
                Your secrets stay yours. Photos and messages are encrypted on your device before they ever touch our servers.
              </p>
            </div>

            {/* Venue Support */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-indigo-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-6 text-indigo-600">
                <Building2 className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Venue Check-Ins</h3>
              <p className="text-gray-600">
                Meet safely in public. Check in to partner venues and see who else is there looking for a connection.
              </p>
            </div>

             {/* Open Source */}
             <div className="p-8 rounded-2xl bg-gray-50 hover:bg-gray-100 transition-colors duration-300 border border-gray-100 md:col-span-2 lg:col-span-3">
              <div className="flex flex-col md:flex-row items-center text-center md:text-left">
                <div className="w-12 h-12 bg-gray-200 rounded-xl flex items-center justify-center mb-4 md:mb-0 md:mr-6 text-gray-700 shrink-0">
                  <Code className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">Open Source & Transparent</h3>
                  <p className="text-gray-600">
                    We have nothing to hide. Our code is open source, so you can verify our security claims yourself. 
                    Built by the community, for the community.
                  </p>
                </div>
              </div>
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
              <div className="flex items-baseline gap-0.5">
                <span className="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-pink-500 to-pink-700 [-webkit-text-stroke:1px_black] drop-shadow-sm">F</span>
                <span className="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-green-400 to-green-600 [-webkit-text-stroke:1px_black] drop-shadow-sm">W</span>
                <span className="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-cyan-400 to-blue-600 [-webkit-text-stroke:1px_black] drop-shadow-sm">B</span>
                <span className="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-purple-500 to-purple-700 [-webkit-text-stroke:1px_black] drop-shadow-sm">er</span>
                <span className="text-lg font-bold text-gray-400 ml-1">.me</span>
              </div>
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
