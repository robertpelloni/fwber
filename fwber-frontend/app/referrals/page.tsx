'use client'

import { useMemo } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { ReferralModal } from '@/components/viral/ReferralModal'
import { useAuth } from '@/lib/auth-context'
import { api } from '@/lib/api/client'
import { useQuery } from '@tanstack/react-query'
import { Copy, DollarSign, Coins, Users, Shield } from 'lucide-react'

interface ReferralLevelSummary {
  level: number
  count: number
  cash_usd: number
  token_amount: number
}

interface ReferralSummary {
  referral_code: string
  referral_link: string
  vouch_link: string
  golden_tickets_remaining: number
  referrals_count: number
  vouches_count: number
  token_balance: number
  pending_cash_usd: number
  earned_token_rewards: number
  levels: ReferralLevelSummary[]
}

export default function ReferralsPage() {
  const { user } = useAuth()
  const origin = typeof window !== 'undefined' ? window.location.origin : (process.env.NEXT_PUBLIC_APP_URL || 'https://fwber.me')

  const { data: summary, isLoading } = useQuery({
    queryKey: ['referrals-page-summary'],
    enabled: !!user,
    queryFn: () => api.get<ReferralSummary>('/referrals/summary'),
  })

  const referralLink = useMemo(() => {
    if (summary?.referral_link) return summary.referral_link
    if (summary?.referral_code) return `${origin}/register?ref=${summary.referral_code}`
    if (user?.referral_code) return `${origin}/register?ref=${user.referral_code}`
    return ''
  }, [summary, user, origin])

  const copy = async (value: string) => {
    if (!value) return
    await navigator.clipboard.writeText(value)
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Referrals" />
        <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">
          <section className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Referrals & Payouts</h1>
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                  Restored a dedicated referral hub so invite links, vouch growth, token rewards, and pending cash payouts are visible outside the modal flow.
                </p>
              </div>
              <ReferralModal />
            </div>
          </section>

          <section className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard icon={<Users className="h-5 w-5" />} label="Referrals" value={summary?.referrals_count ?? user?.referrals_count ?? 0} />
            <MetricCard icon={<Shield className="h-5 w-5" />} label="Vouches" value={summary?.vouches_count ?? user?.vouches_count ?? 0} />
            <MetricCard icon={<Coins className="h-5 w-5" />} label="Token Rewards" value={summary?.earned_token_rewards ?? 0} />
            <MetricCard icon={<DollarSign className="h-5 w-5" />} label="Pending Cash" value={`$${Number(summary?.pending_cash_usd ?? 0).toFixed(2)}`} />
          </section>

          <section className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Invite Link</h2>
            {isLoading ? (
              <p className="mt-3 text-sm text-gray-500">Loading referral summary…</p>
            ) : (
              <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                <input
                  readOnly
                  value={referralLink}
                  className="flex-1 rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                />
                <button
                  onClick={() => copy(referralLink)}
                  className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
                >
                  <Copy className="h-4 w-4" />
                  Copy Link
                </button>
              </div>
            )}
          </section>
        </main>
      </div>
    </ProtectedRoute>
  )
}

function MetricCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: React.ReactNode }) {
  return (
    <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div className="mb-3 inline-flex rounded-xl bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-300">{icon}</div>
      <div className="text-sm text-gray-500 dark:text-gray-400">{label}</div>
      <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{value}</div>
    </div>
  )
}
