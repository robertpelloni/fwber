'use client'

import { useAuth } from '@/lib/auth-context'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'
import Link from 'next/link'
import { Logo } from '@/components/Logo'
import { ThemeToggle } from '@/components/ThemeToggle'
import { trackCTAClick } from '@/lib/ab-testing'
import {
  Shield,
  Heart,
  MapPin,
  MessageCircle,
  Lock,
  Ghost,
  Layers,
  CheckCircle2,
  Sparkles,
  Star,
  Eye,
  Fingerprint,
  UserCheck,
  Home,
  UserPlus,
  Compass,
} from 'lucide-react'
import { Suspense } from 'react'
import ReferralBanner from '@/components/ReferralBanner'

export default function LandingVariantA() {
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
      <div className="flex min-h-screen items-center justify-center bg-white dark:bg-gray-950">
        <div className="h-10 w-10 animate-spin rounded-full border-2 border-purple-200 border-t-purple-600"></div>
      </div>
    )
  }

  if (isAuthenticated) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-white dark:bg-gray-950">
        <div className="text-center">
          <div className="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-2 border-purple-200 border-t-purple-600"></div>
          <p className="text-sm text-gray-500 dark:text-gray-400">Taking you to your dashboard…</p>
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
      <nav className="sticky top-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-950/80">
        <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
          <div className="flex h-16 justify-between">
            <div className="flex items-center">
              <Link href="/" className="flex flex-shrink-0 items-center gap-1.5">
                <Logo className="text-3xl" />
              </Link>
            </div>
            <div className="hidden items-center space-x-6 md:flex">
              <Link
                href="#how-it-works"
                className="text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              >
                How it works
              </Link>
              <Link
                href="#safety"
                className="text-sm text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              >
                Safety
              </Link>
              <ThemeToggle />
              <Link
                href="/login"
                className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
              >
                Log in
              </Link>
              <Link
                href="/register"
                className="rounded-full bg-gray-900 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
              >
                Get started
              </Link>
            </div>
            <div className="flex items-center gap-3 md:hidden">
              <ThemeToggle />
              <Link
                href="/register"
                className="rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900"
              >
                Join
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-b from-purple-50/60 via-white to-white dark:from-purple-950/20 dark:via-gray-950 dark:to-gray-950"></div>
        <div className="absolute left-1/4 top-20 h-96 w-96 rounded-full bg-pink-200/30 blur-3xl dark:bg-pink-500/5"></div>
        <div className="absolute right-1/4 top-40 h-80 w-80 rounded-full bg-purple-200/30 blur-3xl dark:bg-purple-500/5"></div>

        <div className="relative mx-auto max-w-4xl px-4 pb-24 pt-20 text-center sm:pb-32 sm:pt-28">
          <div className="mb-6 flex justify-center">
            <Logo className="text-5xl sm:text-7xl" showDotMe={true} />
          </div>

          <h1 className="mb-6 text-4xl font-bold leading-[1.1] tracking-tight text-gray-900 sm:text-5xl lg:text-6xl dark:text-white">
            Meet people for who{' '}
            <span className="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
              they really are
            </span>
          </h1>

          <p className="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-gray-600 sm:text-xl dark:text-gray-400">
            Photos stay hidden until you choose to reveal them. Values, personality, and real
            connection come first. Your privacy is the foundation, not a feature toggle.
          </p>

          <div className="mb-12 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link
              href="/register"
              onClick={() => trackCTAClick('hero_cta_a')}
              className="w-full rounded-full bg-gray-900 px-8 py-3.5 text-base font-medium text-white shadow-sm transition-all hover:bg-gray-800 sm:w-auto dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
            >
              Create your account — it&apos;s free
            </Link>
            <Link
              href="#how-it-works"
              className="w-full rounded-full border border-gray-200 px-8 py-3.5 text-base font-medium text-gray-700 transition-all hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900"
            >
              See how it works
            </Link>
          </div>

          <div className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-gray-500">
            <span className="flex items-center gap-1.5">
              <Lock className="h-4 w-4" /> End-to-end encrypted
            </span>
            <span className="flex items-center gap-1.5">
              <Ghost className="h-4 w-4" /> Browse anonymously
            </span>
            <span className="flex items-center gap-1.5">
              <Eye className="h-4 w-4" /> Photos are opt-in
            </span>
            <span className="flex items-center gap-1.5">
              <CheckCircle2 className="h-4 w-4" /> Open source
            </span>
          </div>
        </div>
      </section>

      {/* How it works */}
      <section id="how-it-works" className="bg-gray-50 py-24 dark:bg-gray-900">
        <div className="mx-auto max-w-5xl px-4">
          <div className="mb-16 text-center">
            <p className="mb-3 text-sm font-semibold uppercase tracking-widest text-purple-600 dark:text-purple-400">
              How it works
            </p>
            <h2 className="text-3xl font-bold text-gray-900 sm:text-4xl dark:text-white">
              Connection before attraction
            </h2>
          </div>
          <div className="grid gap-12 md:grid-cols-3">
            <div className="text-center">
              <div className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-100 dark:bg-purple-900/30">
                <Sparkles className="h-7 w-7 text-purple-600 dark:text-purple-400" />
              </div>
              <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                1. Answer questions
              </h3>
              <p className="leading-relaxed text-gray-600 dark:text-gray-400">
                Share what matters to you — your values, lifestyle, and what you&apos;re looking
                for. Our matching engine uses 100+ personality questions to find real compatibility.
              </p>
            </div>
            <div className="text-center">
              <div className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-pink-100 dark:bg-pink-900/30">
                <Compass className="h-7 w-7 text-pink-600 dark:text-pink-400" />
              </div>
              <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                2. Discover nearby
              </h3>
              <p className="leading-relaxed text-gray-600 dark:text-gray-400">
                Browse people near you through AI-generated avatars — not photos. See compatibility
                scores, shared values, and local events. Your exact location is never shared.
              </p>
            </div>
            <div className="text-center">
              <div className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 dark:bg-green-900/30">
                <Heart className="h-7 w-7 text-green-600 dark:text-green-400" />
              </div>
              <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                3. Reveal on your terms
              </h3>
              <p className="leading-relaxed text-gray-600 dark:text-gray-400">
                When you&apos;ve built trust through conversation, choose to reveal photos. You
                control the pace. No pressure, no rush — real connection grows naturally.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="py-24">
        <div className="mx-auto max-w-5xl px-4">
          <div className="mb-16 text-center">
            <p className="mb-3 text-sm font-semibold uppercase tracking-widest text-purple-600 dark:text-purple-400">
              Built different
            </p>
            <h2 className="text-3xl font-bold text-gray-900 sm:text-4xl dark:text-white">
              Designed around trust
            </h2>
          </div>
          <div className="grid gap-8 sm:grid-cols-2">
            <FeatureCard
              icon={<Ghost className="h-6 w-6" />}
              title="AI Avatars by default"
              description="Your real photos are hidden behind an AI-generated persona. No snap judgments — you're seen for your personality first."
              color="purple"
            />
            <FeatureCard
              icon={<Fingerprint className="h-6 w-6" />}
              title="Zero-knowledge verification"
              description="Prove you're real without revealing your identity. Anti-catfish technology that protects your privacy."
              color="blue"
            />
            <FeatureCard
              icon={<MapPin className="h-6 w-6" />}
              title="Fuzzy location"
              description="We know your neighborhood, not your address. Meet people nearby without ever sharing exact coordinates."
              color="green"
            />
            <FeatureCard
              icon={<Lock className="h-6 w-6" />}
              title="End-to-end encryption"
              description="Every message is encrypted on your device. We can't read them. No one can. Your conversations are yours."
              color="pink"
            />
            <FeatureCard
              icon={<Layers className="h-6 w-6" />}
              title="Progressive reveal"
              description="You control when your match sees your photos, your name, your details. Trust builds step by step."
              color="orange"
            />
            <FeatureCard
              icon={<UserCheck className="h-6 w-6" />}
              title="Value-based matching"
              description="100+ personality questions across 7 categories. We match on what matters — not just photos and swipes."
              color="indigo"
            />
            <FeatureCard
              icon={<Shield className="h-6 w-6" />}
              title="AI safety guardrails"
              description="Unsolicited explicit content is blocked automatically. Reports are handled quickly. Your safety is non-negotiable."
              color="red"
            />
            <FeatureCard
              icon={<Star className="h-6 w-6" />}
              title="Open source"
              description="Every line of code is auditable. We don't sell your data because you can verify we don't. Trust through transparency."
              color="amber"
            />
          </div>
        </div>
      </section>

      {/* Safety */}
      <section id="safety" className="bg-gray-50 py-24 dark:bg-gray-900">
        <div className="mx-auto max-w-4xl px-4 text-center">
          <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-green-100 dark:bg-green-900/30">
            <Shield className="h-8 w-8 text-green-600 dark:text-green-400" />
          </div>
          <h2 className="mb-4 text-3xl font-bold text-gray-900 sm:text-4xl dark:text-white">
            Your safety comes first
          </h2>
          <p className="mx-auto mb-12 max-w-2xl text-lg leading-relaxed text-gray-600 dark:text-gray-400">
            We built fwber because we believe dating apps should protect you, not exploit you. Every
            design decision starts with the question: &quot;Does this keep people safe?&quot;
          </p>
          <div className="mb-12 grid gap-6 text-left sm:grid-cols-2">
            <SafetyItem
              icon={<Eye className="h-5 w-5" />}
              title="Ghost Mode"
              description="Browse invisibly. No one knows you looked at their profile unless you want them to."
            />
            <SafetyItem
              icon={<MapPin className="h-5 w-5" />}
              title="Location fuzzing"
              description="Your location is approximate. We show your area, never your exact spot."
            />
            <SafetyItem
              icon={<Lock className="h-5 w-5" />}
              title="Private vault"
              description="Encrypted storage for private content that never touches the cloud unencrypted."
            />
            <SafetyItem
              icon={<MessageCircle className="h-5 w-5" />}
              title="Safe Walk"
              description="Share your live route with trusted contacts when walking to meet someone."
            />
          </div>
          <div className="rounded-2xl border border-gray-200 bg-white p-8 sm:p-10 dark:border-gray-700 dark:bg-gray-800">
            <h3 className="mb-3 text-2xl font-bold text-gray-900 dark:text-white">
              Ready to try a different kind of dating app?
            </h3>
            <p className="mb-6 text-gray-600 dark:text-gray-400">
              Free to join. No credit card required. Your data stays yours.
            </p>
            <Link
              href="/register"
              onClick={() => trackCTAClick('bottom_cta_a')}
              className="inline-block rounded-full bg-gray-900 px-8 py-3.5 font-medium text-white transition-colors hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100"
            >
              Create your account
            </Link>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-gray-100 py-12 dark:border-gray-800">
        <div className="mx-auto max-w-6xl px-4">
          <div className="flex flex-col items-center justify-between gap-6 md:flex-row">
            <div className="flex items-center gap-4">
              <Logo className="text-2xl" />
              <span className="text-sm text-gray-500">© 2026 fwber</span>
            </div>
            <div className="flex flex-wrap justify-center gap-6 text-sm text-gray-500">
              <Link
                href="/privacy"
                className="transition-colors hover:text-gray-900 dark:hover:text-white"
              >
                Privacy
              </Link>
              <Link
                href="/terms"
                className="transition-colors hover:text-gray-900 dark:hover:text-white"
              >
                Terms
              </Link>
              <Link
                href="/contact"
                className="transition-colors hover:text-gray-900 dark:hover:text-white"
              >
                Contact
              </Link>
              <a
                href="https://github.com/robertpelloni/fwber"
                target="_blank"
                rel="noopener noreferrer"
                className="transition-colors hover:text-gray-900 dark:hover:text-white"
              >
                GitHub
              </a>
            </div>
          </div>
        </div>
      </footer>

      {/* Mobile bottom nav */}
      <div className="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-100 bg-white md:hidden dark:border-gray-800 dark:bg-gray-950">
        <div className="flex h-14 items-center justify-around">
          <Link
            href="/"
            className="flex h-full w-full flex-col items-center justify-center text-gray-900 dark:text-white"
          >
            <Home className="h-5 w-5" />
            <span className="mt-0.5 text-[10px] font-medium">Home</span>
          </Link>
          <Link
            href="#how-it-works"
            className="flex h-full w-full flex-col items-center justify-center text-gray-400"
          >
            <Star className="h-5 w-5" />
            <span className="mt-0.5 text-[10px]">How it works</span>
          </Link>
          <Link
            href="#safety"
            className="flex h-full w-full flex-col items-center justify-center text-gray-400"
          >
            <Shield className="h-5 w-5" />
            <span className="mt-0.5 text-[10px]">Safety</span>
          </Link>
          <Link
            href="/register"
            className="flex h-full w-full flex-col items-center justify-center font-semibold text-purple-600 dark:text-purple-400"
          >
            <UserPlus className="h-5 w-5" />
            <span className="mt-0.5 text-[10px]">Join</span>
          </Link>
        </div>
      </div>
    </div>
  )
}

function FeatureCard({
  icon,
  title,
  description,
  color,
}: {
  icon: React.ReactNode
  title: string
  description: string
  color: string
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
  }
  return (
    <div className="group rounded-2xl border border-gray-100 bg-white p-6 transition-all hover:border-gray-200 hover:shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700">
      <div
        className={`mb-4 flex h-11 w-11 items-center justify-center rounded-xl ${
          colors[color] || colors.purple
        }`}
      >
        {icon}
      </div>
      <h3 className="mb-2 font-semibold text-gray-900 dark:text-white">{title}</h3>
      <p className="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{description}</p>
    </div>
  )
}

function SafetyItem({
  icon,
  title,
  description,
}: {
  icon: React.ReactNode
  title: string
  description: string
}) {
  return (
    <div className="flex gap-3 rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
        {icon}
      </div>
      <div>
        <h4 className="mb-0.5 text-sm font-medium text-gray-900 dark:text-white">{title}</h4>
        <p className="text-sm leading-relaxed text-gray-600 dark:text-gray-400">{description}</p>
      </div>
    </div>
  )
}
