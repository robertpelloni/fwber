'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'
import { Logo } from '@/components/Logo'
import { ThemeToggle } from '@/components/ThemeToggle'
import {
  Shield, Heart, MapPin, MessageCircle,
  Lock, Ghost, Layers, CheckCircle2, Sparkles, Star,
  Eye, Fingerprint, UserCheck, Home, UserPlus, Compass
} from 'lucide-react'
import { Suspense } from 'react'
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
      <div className="min-h-screen flex items-center justify-center bg-white dark:bg-gray-950">
        <div className="animate-spin rounded-full h-10 w-10 border-2 border-purple-200 border-t-purple-600"></div>
      </div>
    )
  }

  if (isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-white dark:bg-gray-950">
        <div className="text-center">
          <div className="animate-spin rounded-full h-10 w-10 border-2 border-purple-200 border-t-purple-600 mx-auto mb-4"></div>
          <p className="text-gray-500 dark:text-gray-400 text-sm">Taking you to your dashboard…</p>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-white dark:bg-gray-950">
      <Suspense fallback={null}>
        <ReferralBanner />
      </Suspense>

      {/* Navigation */}
      <nav className="sticky top-0 z-50 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <Link href="/" className="flex-shrink-0 flex items-center gap-1.5">
                <Logo className="text-3xl" />
              </Link>
            </div>
            <div className="hidden md:flex items-center space-x-6">
              <Link href="#how-it-works" className="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">How it works</Link>
              <Link href="#safety" className="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Safety</Link>
              <ThemeToggle />
              <Link href="/login" className="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors font-medium">
                Log in
              </Link>
              <Link href="/register" className="bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-5 py-2 rounded-full text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                Get started
              </Link>
            </div>
            <div className="md:hidden flex items-center gap-3">
              <ThemeToggle />
              <Link href="/register" className="bg-gray-900 dark:bg-white text-white dark:text-gray-900 px-4 py-2 rounded-full text-sm font-medium">
                Join
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-b from-purple-50/60 via-white to-white dark:from-purple-950/20 dark:via-gray-950 dark:to-gray-950"></div>
        <div className="absolute top-20 left-1/4 w-96 h-96 bg-pink-200/30 dark:bg-pink-500/5 rounded-full blur-3xl"></div>
        <div className="absolute top-40 right-1/4 w-80 h-80 bg-purple-200/30 dark:bg-purple-500/5 rounded-full blur-3xl"></div>

        <div className="relative max-w-4xl mx-auto px-4 pt-20 pb-24 sm:pt-28 sm:pb-32 text-center">
          <div className="mb-6 flex justify-center">
            <Logo className="text-5xl sm:text-7xl" showDotMe={true} />
          </div>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white tracking-tight leading-[1.1] mb-6">
            Meet people for who{' '}
            <span className="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
              they really are
            </span>
          </h1>

          <p className="text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-10 leading-relaxed">
            Photos stay hidden until you choose to reveal them. Values, personality, and real connection come first. Your privacy is the foundation, not a feature toggle.
          </p>

          <div className="flex flex-col sm:flex-row items-center justify-center gap-3 mb-12">
            <Link
              href="/register"
              className="w-full sm:w-auto bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium py-3.5 px-8 rounded-full text-base shadow-sm hover:bg-gray-800 dark:hover:bg-gray-100 transition-all"
            >
              Create your account — it&apos;s free
            </Link>
            <Link
              href="#how-it-works"
              className="w-full sm:w-auto border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-medium py-3.5 px-8 rounded-full text-base hover:bg-gray-50 dark:hover:bg-gray-900 transition-all"
            >
              See how it works
            </Link>
          </div>

          {/* Trust signals */}
          <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-gray-500 dark:text-gray-500">
            <span className="flex items-center gap-1.5">
              <Lock className="w-4 h-4" /> End-to-end encrypted
            </span>
            <span className="flex items-center gap-1.5">
              <Ghost className="w-4 h-4" /> Browse anonymously
            </span>
            <span className="flex items-center gap-1.5">
              <Eye className="w-4 h-4" /> Photos are opt-in
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="w-4 h-4" /> Open source
            </span>
          </div>
        </div>
      </section>

      {/* How it works */}
      <section id="how-it-works" className="py-24 bg-gray-50 dark:bg-gray-900">
        <div className="max-w-5xl mx-auto px-4">
          <div className="text-center mb-16">
            <p className="text-sm font-semibold uppercase tracking-widest text-purple-600 dark:text-purple-400 mb-3">How it works</p>
            <h2 className="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Connection before attraction</h2>
          </div>

          <div className="grid md:grid-cols-3 gap-12">
            <div className="text-center">
              <div className="w-14 h-14 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto mb-5">
                <Sparkles className="w-7 h-7 text-purple-600 dark:text-purple-400" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">1. Answer questions</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                Share what matters to you — your values, lifestyle, and what you&apos;re looking for. Our matching engine uses 100+ personality questions to find real compatibility.
              </p>
            </div>
            <div className="text-center">
              <div className="w-14 h-14 rounded-2xl bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center mx-auto mb-5">
                <Compass className="w-7 h-7 text-pink-600 dark:text-pink-400" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">2. Discover nearby</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                Browse people near you through AI-generated avatars — not photos. See compatibility scores, shared values, and local events. Your exact location is never shared.
              </p>
            </div>
            <div className="text-center">
              <div className="w-14 h-14 rounded-2xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-5">
                <Heart className="w-7 h-7 text-green-600 dark:text-green-400" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">3. Reveal on your terms</h3>
              <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                When you&apos;ve built trust through conversation, choose to reveal photos. You control the pace. No pressure, no rush — real connection grows naturally.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="py-24">
        <div className="max-w-5xl mx-auto px-4">
          <div className="text-center mb-16">
            <p className="text-sm font-semibold uppercase tracking-widest text-purple-600 dark:text-purple-400 mb-3">Built different</p>
            <h2 className="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Designed around trust</h2>
          </div>

          <div className="grid sm:grid-cols-2 gap-8">
            <FeatureCard
              icon={<Ghost className="w-6 h-6" />}
              title="AI Avatars by default"
              description="Your real photos are hidden behind an AI-generated persona. No snap judgments — you're seen for your personality first."
              color="purple"
            />
            <FeatureCard
              icon={<Fingerprint className="w-6 h-6" />}
              title="Zero-knowledge verification"
              description="Prove you're real without revealing your identity. Anti-catfish technology that protects your privacy."
              color="blue"
            />
            <FeatureCard
              icon={<MapPin className="w-6 h-6" />}
              title="Fuzzy location"
              description="We know your neighborhood, not your address. Meet people nearby without ever sharing exact coordinates."
              color="green"
            />
            <FeatureCard
              icon={<Lock className="w-6 h-6" />}
              title="End-to-end encryption"
              description="Every message is encrypted on your device. We can't read them. No one can. Your conversations are yours."
              color="pink"
            />
            <FeatureCard
              icon={<Layers className="w-6 h-6" />}
              title="Progressive reveal"
              description="You control when your match sees your photos, your name, your details. Trust builds step by step."
              color="orange"
            />
            <FeatureCard
              icon={<UserCheck className="w-6 h-6" />}
              title="Value-based matching"
              description="100+ personality questions across 7 categories. We match on what matters — not just photos and swipes."
              color="indigo"
            />
            <FeatureCard
              icon={<Shield className="w-6 h-6" />}
              title="AI safety guardrails"
              description="Unsolicited explicit content is blocked automatically. Reports are handled quickly. Your safety is non-negotiable."
              color="red"
            />
            <FeatureCard
              icon={<Star className="w-6 h-6" />}
              title="Open source"
              description="Every line of code is auditable. We don't sell your data because you can verify we don't. Trust through transparency."
              color="amber"
            />
          </div>
        </div>
      </section>

      {/* Safety */}
      <section id="safety" className="py-24 bg-gray-50 dark:bg-gray-900">
        <div className="max-w-4xl mx-auto px-4 text-center">
          <div className="w-16 h-16 rounded-2xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-6">
            <Shield className="w-8 h-8 text-green-600 dark:text-green-400" />
          </div>
          <h2 className="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">Your safety comes first</h2>
          <p className="text-lg text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto leading-relaxed">
            We built fwber because we believe dating apps should protect you, not exploit you. Every design decision starts with the question: &quot;Does this keep people safe?&quot;
          </p>

          <div className="grid sm:grid-cols-2 gap-6 text-left mb-12">
            <SafetyItem icon={<Eye className="w-5 h-5" />} title="Ghost Mode" description="Browse invisibly. No one knows you looked at their profile unless you want them to." />
            <SafetyItem icon={<MapPin className="w-5 h-5" />} title="Location fuzzing" description="Your location is approximate. We show your area, never your exact spot." />
            <SafetyItem icon={<Lock className="w-5 h-5" />} title="Private vault" description="Encrypted storage for private content that never touches the cloud unencrypted." />
            <SafetyItem icon={<MessageCircle className="w-5 h-5" />} title="Safe Walk" description="Share your live route with trusted contacts when walking to meet someone." />
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-8 sm:p-10">
            <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">Ready to try a different kind of dating app?</h3>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Free to join. No credit card required. Your data stays yours.
            </p>
            <Link
              href="/register"
              className="inline-block bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium py-3.5 px-8 rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors"
            >
              Create your account
            </Link>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-gray-100 dark:border-gray-800 py-12">
        <div className="max-w-6xl mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center gap-6">
            <div className="flex items-center gap-4">
              <Logo className="text-2xl" />
              <span className="text-sm text-gray-500">© 2026 fwber</span>
            </div>
            <div className="flex flex-wrap justify-center gap-6 text-sm text-gray-500">
              <Link href="/privacy" className="hover:text-gray-900 dark:hover:text-white transition-colors">Privacy</Link>
              <Link href="/terms" className="hover:text-gray-900 dark:hover:text-white transition-colors">Terms</Link>
              <Link href="/contact" className="hover:text-gray-900 dark:hover:text-white transition-colors">Contact</Link>
              <a href="https://github.com/robertpelloni/fwber" target="_blank" rel="noopener noreferrer" className="hover:text-gray-900 dark:hover:text-white transition-colors">GitHub</a>
            </div>
          </div>
        </div>
      </footer>

      {/* Mobile bottom nav */}
      <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-950 border-t border-gray-100 dark:border-gray-800 z-50">
        <div className="flex justify-around items-center h-14">
          <Link href="/" className="flex flex-col items-center justify-center w-full h-full text-gray-900 dark:text-white">
            <Home className="w-5 h-5" />
            <span className="text-[10px] font-medium mt-0.5">Home</span>
          </Link>
          <Link href="#how-it-works" className="flex flex-col items-center justify-center w-full h-full text-gray-400">
            <Star className="w-5 h-5" />
            <span className="text-[10px] mt-0.5">How it works</span>
          </Link>
          <Link href="#safety" className="flex flex-col items-center justify-center w-full h-full text-gray-400">
            <Shield className="w-5 h-5" />
            <span className="text-[10px] mt-0.5">Safety</span>
          </Link>
          <Link href="/register" className="flex flex-col items-center justify-center w-full h-full text-purple-600 dark:text-purple-400 font-semibold">
            <UserPlus className="w-5 h-5" />
            <span className="text-[10px] mt-0.5">Join</span>
          </Link>
        </div>
      </div>
    </div>
  )
}

/* ── Sub-components ── */

function FeatureCard({
  icon, title, description, color,
}: {
  icon: React.ReactNode; title: string; description: string; color: string;
}) {
  const colors: Record<string, string> = {
    purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    green: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400',
    pink: 'bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400',
    orange: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
    indigo: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    red: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
    amber: 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
  };

  return (
    <div className="group p-6 rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-gray-200 dark:hover:border-gray-700 hover:shadow-sm transition-all">
      <div className={`w-11 h-11 rounded-xl ${colors[color] || colors.purple} flex items-center justify-center mb-4`}>
        {icon}
      </div>
      <h3 className="font-semibold text-gray-900 dark:text-white mb-2">{title}</h3>
      <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{description}</p>
    </div>
  );
}

function SafetyItem({
  icon, title, description,
}: {
  icon: React.ReactNode; title: string; description: string;
}) {
  return (
    <div className="flex gap-3 p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
      <div className="flex-shrink-0 w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
        {icon}
      </div>
      <div>
        <h4 className="font-medium text-gray-900 dark:text-white text-sm mb-0.5">{title}</h4>
        <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{description}</p>
      </div>
    </div>
  );
}
