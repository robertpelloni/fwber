'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'
import { Logo } from '@/components/Logo'
import SexQuote from '@/components/SexQuote'
import { ThemeToggle } from '@/components/ThemeToggle'
import { Shield, Heart, Zap, MapPin, MessageCircle, Users, Menu, X, Lock, Ghost, Layers, FileText, Code, Building2, CheckCircle2, Sparkles, GitMerge, Shirt, RefreshCw, Flame, Link as LinkIcon, Rainbow, Video, Gift, Home, UserPlus, Star } from 'lucide-react'
import { useState, Suspense } from 'react'
import ReferralBanner from '@/components/ReferralBanner'

export default function HomePage() {
  const { isAuthenticated, isLoading } = useAuth()
  const router = useRouter()

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
    return (
      <div className="min-h-screen flex items-center justify-center bg-white dark:bg-gray-900">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
          <p className="text-gray-600 dark:text-gray-300">Redirecting to dashboard...</p>
        </div>
      </div>
    )
  }

  return (
      <div className="min-h-screen bg-white dark:bg-gray-800 dark:bg-slate-950">
      <Suspense fallback={null}>
        <ReferralBanner />
      </Suspense>
      {/* Navigation */}
      <nav className="sticky top-0 z-50 border-b border-slate-200/70 bg-white dark:bg-gray-800/90 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/85">
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
                <Link href="/login" className="text-gray-900 dark:text-white hover:text-blue-600 font-medium">
                  Log in
                </Link>
                <Link href="/register" className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-full font-medium transition-colors">
                  Sign up
                </Link>
              </div>
            </div>

            {/* Mobile Theme Toggle */}
            <div className="md:hidden flex items-center gap-4">
              <ThemeToggle />
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <div className="relative overflow-hidden bg-gradient-to-b from-slate-50 via-white to-fuchsia-50/40 pt-16 pb-4 transition-colors duration-500 dark:from-slate-950 dark:via-slate-900 dark:to-fuchsia-950/15 lg:pt-32 lg:pb-8">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <div className="text-center max-w-4xl mx-auto">
            <div className="mb-6 flex justify-center">
              <Logo className="text-6xl md:text-8xl" showDotMe={true} />
            </div>
            
            <h2 className="text-3xl md:text-5xl font-bold mb-4 animate-gradient-x bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent bg-[length:200%_auto] hover:animate-bounce cursor-default">
              Real Connections. Zero Trace.
            </h2>
            
            <p className="text-lg md:text-xl font-medium text-slate-900 dark:text-slate-300 mb-8 max-w-2xl mx-auto">
              The open-source adult network built on privacy. Meet locals, verify with AI, and chat with end-to-end encryption.
            </p>
            
            <div className="w-full max-w-2xl mx-auto mb-10 rounded-[28px] border border-white/70 bg-white dark:bg-gray-800/70 p-4 shadow-[0_24px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/35">
              <SexQuote />
            </div>

            <div className="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
              <Link
                href="/rate-my-pussy"
                className="w-full sm:w-auto bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-black py-4 px-10 rounded-full text-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center animate-bounce hover:animate-none"
              >
                <Heart className="w-6 h-6 mr-2 fill-current" />
                Rate My Cat
              </Link>
              <Link
                href="/roast"
                className="w-full sm:w-auto bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold py-4 px-10 rounded-full text-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center"
              >
                <Flame className="w-5 h-5 mr-2 fill-current" />
                Roast My Profile
              </Link>
              <Link
                href="/register"
                className="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-10 rounded-full text-lg shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center"
              >
                Create Anonymous Account
              </Link>
            </div>

            <div className="mt-8 mx-auto max-w-3xl rounded-2xl border border-purple-200/70 bg-white/85 p-5 text-left shadow-[0_16px_45px_rgba(168,85,247,0.12)] backdrop-blur-sm dark:border-purple-800/70 dark:bg-gray-900/75">
              <div className="flex items-start gap-3">
                <div className="mt-1 rounded-full bg-gradient-to-br from-purple-100 to-fuchsia-100 p-2 dark:from-purple-900/60 dark:to-fuchsia-900/50">
                  <Gift className="w-5 h-5 text-purple-600 dark:text-purple-300" />
                </div>
                <div>
                  <div className="flex flex-wrap items-center gap-2">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-white">Bring your people with you</h3>
                    <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                      referral rewards
                    </span>
                  </div>
                  <p className="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Send invites, grow your circle faster, and stack FWBcoin as your network moves. As Gold referrals roll through, direct upgrades are designed to unlock a small
                    real-money bonus, with a smaller follow-on reward one level deeper.
                  </p>
                </div>
              </div>
            </div>

            <div className="mt-12 pt-8 border-t border-gray-200/60 dark:border-gray-700/60">
              <p className="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-6">
                A Community for Every Orientation and Identity
              </p>
              <div className="flex flex-wrap justify-center gap-3">
                {[
                  { name: 'Inclusive', icon: Users },
                  { name: 'Kink-Friendly', icon: Flame },
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
        <div className="absolute top-0 left-0 -ml-20 -mt-20 h-96 w-96 rounded-full bg-sky-200/70 opacity-60 blur-3xl transition-colors duration-500 dark:bg-sky-500/10"></div>
        <div className="absolute top-0 right-0 -mr-20 -mt-20 h-96 w-96 rounded-full bg-fuchsia-200/70 opacity-60 blur-3xl transition-colors duration-500 dark:bg-fuchsia-500/10"></div>
      </div>

      {/* Why fwber Section (Moved Up) */}
      <div className="py-24 bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Find Who’s Nearby</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  See active matches in your immediate vicinity without compromising your exact coordinates. Perfect for conventions, clubs, or spontaneous meetups.
                </p>
              </div>
            </div>

            <div className="flex gap-4">
              <div className="flex-shrink-0 mt-1">
                <Layers className="w-6 h-6 text-pink-500" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">Earn Their Trust</h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Start as an avatar. As your connection deepens, your profile reveals more. You control exactly when and what your match sees.
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
                  The full fwber experience in your pocket. Install directly to your home screen.
                </p>
              </div>
            </div>

             <div className="flex gap-4 md:col-span-2">
              <div className="flex-shrink-0 mt-1">
                <Code className="w-6 h-6 text-gray-700 dark:text-gray-300" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  Trust Through Transparency
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Unlike corporate dating apps, we don&apos;t sell your data. Our code is open-source and auditable by anyone. Your secrets stay on your device, not our servers. <a href="https://github.com/robertpelloni/fwber" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">View on GitHub</a>
                </p>
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
      <footer className="bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-8 md:mb-0">
              <div className="flex items-baseline gap-1">
                <Logo className="text-3xl" />
              </div>
              <p className="text-gray-500 mt-2">© 2026 fwber. All rights reserved.</p>
            </div>
            <div className="flex space-x-8">
              <Link href="/privacy" className="text-gray-600 hover:text-gray-900 dark:text-white">Privacy Policy</Link>
              <Link href="/terms" className="text-gray-600 hover:text-gray-900 dark:text-white">Terms of Service</Link>
              <Link href="/contact" className="text-gray-600 hover:text-gray-900 dark:text-white">Contact Us</Link>
            </div>
          </div>
        </div>
      </footer>
      
      {/* Mobile Bottom Navigation */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 dark:border-gray-800 z-50 pb-safe">
        <div className="flex justify-around items-center h-16">
          <Link href="/" className="flex flex-col items-center justify-center w-full h-full text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500">
            <Home className="w-6 h-6 mb-1" />
            <span className="text-xs font-medium">Home</span>
          </Link>
          <Link href="#features" className="flex flex-col items-center justify-center w-full h-full text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500">
            <Star className="w-6 h-6 mb-1" />
            <span className="text-xs font-medium">Features</span>
          </Link>
          <Link href="#safety" className="flex flex-col items-center justify-center w-full h-full text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-500">
            <Shield className="w-6 h-6 mb-1" />
            <span className="text-xs font-medium">Safety</span>
          </Link>
          <Link href="/register" className="flex flex-col items-center justify-center w-full h-full text-blue-600 dark:text-blue-500 font-semibold">
            <UserPlus className="w-6 h-6 mb-1" />
            <span className="text-xs">Join</span>
          </Link>
        </div>
      </div>

    </div>
  )
}
