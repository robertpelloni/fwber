'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'
import { Logo } from '@/components/Logo'
import SexQuote from '@/components/SexQuote'
import PWAInstallPrompt from '@/components/PWAInstallPrompt'
import { ThemeToggle } from '@/components/ThemeToggle'
import { Shield, Heart, Zap, MapPin, MessageCircle, Users, Menu, X, Lock, Ghost, Layers, FileText, Code, Building2, CheckCircle2, Sparkles, GitMerge, Shirt, RefreshCw, Flame, Link as LinkIcon, Rainbow, Video, Gift } from 'lucide-react'
import { useState } from 'react'

export default function HomePage() {
  const { isAuthenticated, isLoading } = useAuth()
  const router = useRouter()
  const [isMenuOpen, setIsMenuOpen] = useState(false)

  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      const timer = setTimeout(() => {
        router.push('/dashboard')
      }, 100)
      return () => clearTimeout(timer)
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
    <div className="min-h-screen bg-white dark:bg-gray-900">
      {/* Navigation */}
      <nav className="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <Link href="/" className="flex-shrink-0 flex items-center gap-1 hover:scale-105 transition-transform duration-200">
                <Logo className="text-4xl" />
              </Link>
            </div>
            
            {/* Desktop Menu */}
            <div className="hidden md:flex items-center space-x-8">
              <Link href="#features" className="text-gray-600 hover:text-blue-600 font-medium">Features</Link>
              <Link href="#safety" className="text-gray-600 hover:text-blue-600 font-medium">Safety</Link>
              <ThemeToggle />
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
            <div className="md:hidden flex items-center gap-4">
              <ThemeToggle />
              <button
                onClick={() => setIsMenuOpen(!isMenuOpen)}
                className="text-gray-600 hover:text-gray-900 focus:outline-none dark:text-gray-300 dark:hover:text-white"
              >
                {isMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Menu */}
        {isMenuOpen && (
          <div className="md:hidden bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 py-4">
            <div className="flex flex-col space-y-4 px-4">
              <Link href="#features" className="text-gray-600 dark:text-gray-300 font-medium">Features</Link>
              <Link href="#safety" className="text-gray-600 dark:text-gray-300 font-medium">Safety</Link>
              <div className="flex items-center justify-between">
                <span className="text-gray-600 dark:text-gray-300 font-medium">Theme</span>
                <ThemeToggle />
              </div>
              <Link href="/login" className="text-gray-900 dark:text-white font-medium">Log in</Link>
              <Link href="/register" className="bg-blue-600 text-white px-5 py-2.5 rounded-lg font-medium text-center">
                Sign up
              </Link>
            </div>
          </div>
        )}
      </nav>

      {/* Hero Section */}
      <div className="relative overflow-hidden bg-gradient-to-b from-blue-50 to-white dark:from-gray-900 dark:to-gray-800 pt-16 pb-4 lg:pt-32 lg:pb-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div className="text-center max-w-4xl mx-auto">
            <div className="mb-6 flex justify-center">
              <Logo className="text-6xl md:text-8xl" showDotMe={true} />
            </div>
            
            <h2 className="text-3xl md:text-5xl font-bold mb-4 animate-gradient-x bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent bg-[length:200%_auto]">
              Adult Social Network
            </h2>
            
            <p className="text-lg md:text-xl font-medium text-black dark:text-gray-300 mb-8 max-w-2xl mx-auto">
              Friends, Dating, Hookups, Ads, Groups, Fun, Love, Lust, and More!
            </p>
            
            <div className="mb-8 transform hover:scale-105 transition-transform duration-300">
              <p className="text-2xl md:text-3xl text-orange-600 dark:text-orange-400 font-serif italic">
                &ldquo;Everybody wants to get laid.&rdquo;
              </p>
            </div>

            <div className="w-full max-w-2xl mx-auto mb-10 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 shadow-sm">
              <SexQuote />
            </div>

            <p className="text-lg md:text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-4xl mx-auto leading-relaxed">
              Find your perfect partner(s) wherever you are—at home, at a convention, or on vacation.
              Whether you&apos;re new to the scene, exploring what&apos;s out there, or passing profiles to your partner for approval, we&apos;ve got you covered.
              Find new best friends, a fling, the whole team, or a life mate. Just for tonight, for right now, or for the rest of your life—we cover all scenarios.
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
                className="w-full sm:w-auto bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white font-bold py-4 px-10 rounded-full text-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-600 dark:hover:border-blue-500 transition-all duration-200"
              >
                Member Login
              </Link>
            </div>

            <div className="mt-12 pt-8 border-t border-gray-200/60 dark:border-gray-700/60">
              <p className="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-6">
                Inclusive Community Support
              </p>
              <div className="flex flex-wrap justify-center gap-3">
                {[
                  { name: 'Straight', icon: Users },
                  { name: 'Couples', icon: Heart },
                  { name: 'Bisexual', icon: GitMerge },
                  { name: 'Gay', icon: Rainbow },
                  { name: 'Trans', icon: Sparkles },
                  { name: 'Crossdress', icon: Shirt },
                  { name: 'Swingers', icon: RefreshCw },
                  { name: 'Mild to Wild', icon: Flame },
                  { name: 'Sex Positive', icon: CheckCircle2 },
                  { name: 'Safer Sex Workers', icon: Shield },
                  { name: 'Fetish Community Support', icon: LinkIcon },
                ].map((item) => (
                  <span 
                    key={item.name}
                    className="flex items-center px-4 py-2 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 cursor-default shadow-sm"
                  >
                    <item.icon className="w-4 h-4 mr-2" />
                    {item.name}
                  </span>
                ))}
              </div>
            </div>
          </div>
        </div>
        
        {/* Decorative blobs */}
        <div className="absolute top-0 left-0 -ml-20 -mt-20 w-96 h-96 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
        <div className="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
      </div>

      {/* Why FWBer Section (Moved Up) */}
      <div className="py-24 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16 max-w-3xl mx-auto">
            <h2 className="text-3xl md:text-5xl font-extrabold text-gray-900 dark:text-white mb-6">
              Yet another hookup site? <br/>
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">What makes this one better?</span>
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-300">
              We&apos;re building the future of adult dating. A perfect blend of privacy, fun, and cutting-edge tech.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <CheckCircle2 className="w-6 h-6 text-green-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Privacy-First Architecture</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Your secrets are safe. Photos are encrypted on your device—we can&apos;t see them, and neither can hackers.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Zap className="w-6 h-6 text-yellow-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Hyper-Local Proximity</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Why wait? Discover matches within walking distance in real-time for spontaneous connections.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Layers className="w-6 h-6 text-pink-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Progressive Trust Tiers</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  The game of seduction. Unlock clearer photos and private details only as you chat and build trust.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Ghost className="w-6 h-6 text-purple-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">AI Avatars</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Be anyone you want. Browse anonymously with a custom AI persona until you&apos;re ready to reveal the real you.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Lock className="w-6 h-6 text-blue-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">The Local Vault</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  A secure, dedicated gallery for your private content that never touches the cloud unencrypted.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Shield className="w-6 h-6 text-green-600" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Verify Your Date</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  No more catfishing. Our Face Reveal system ensures the person you meet matches their profile.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Sparkles className="w-6 h-6 text-indigo-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  Smart Behavioral Matching <span className="text-xs ml-2 bg-green-100 text-green-800 py-0.5 px-2 rounded-full uppercase tracking-wider font-semibold">New</span>
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  An AI matchmaker that learns your true &quot;type&quot; based on who you actually interact with, not just who you say you like.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Shield className="w-6 h-6 text-red-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  AI Safety Guardrails <span className="text-xs ml-2 bg-green-100 text-green-800 py-0.5 px-2 rounded-full uppercase tracking-wider font-semibold">Active</span>
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Advanced AI that blocks unsolicited explicit content before it ruins your mood.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Users className="w-6 h-6 text-orange-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  Social Circles & Events
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Because sometimes the best connections happen in a crowd. Find local events and groups.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <MapPin className="w-6 h-6 text-blue-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  Native Mobile Experience
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  The full FWBer experience in your pocket. Install directly to your home screen.
                </p>
              </div>
            </div>
          </div>
        </div>
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

            {/* Video Chat */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-red-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-6 text-red-600">
                <Video className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Private Video Chat</h3>
              <p className="text-gray-600">
                Verify your chemistry before you meet. Secure, peer-to-peer video calls built right into the app.
              </p>
            </div>

            {/* Virtual Gifts */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-pink-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center mb-6 text-pink-600">
                <Gift className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Virtual Gifts</h3>
              <p className="text-gray-600">
                Break the ice or show appreciation. Send virtual roses, drinks, and more using FWB Tokens.
              </p>
            </div>

            {/* Travel Mode */}
            <div className="p-8 rounded-2xl bg-gray-50 hover:bg-teal-50 transition-colors duration-300 border border-gray-100">
              <div className="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mb-6 text-teal-600">
                <MapPin className="w-6 h-6" />
              </div>
              <h3 className="text-xl font-bold text-gray-900 mb-3">Travel Mode</h3>
              <p className="text-gray-600">
                Planning a trip? Set your location to your destination and start matching with locals before you arrive.
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
                    Built by the community, for the community. <a href="https://github.com/robertpelloni/fwber" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">View on GitHub</a>
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
              <div className="flex items-baseline gap-1">
                <Logo className="text-3xl" />
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
      
      <PWAInstallPrompt />
    </div>
  )
}
