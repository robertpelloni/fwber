'use client'

import { useEffect, useMemo, useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { useWallet } from '@/lib/hooks/useWallet'
import { useReceivedGifts } from '@/lib/hooks/use-gifts'
import { Coins, Wallet as WalletIcon, RefreshCw, Copy, Ticket, Gift, BadgeDollarSign, ShieldCheck, Link as LinkIcon } from 'lucide-react'

async function copyText(value?: string | null) {
  if (!value) return
  await navigator.clipboard.writeText(value)
}

export default function WalletPage() {
  const [activeTab, setActiveTab] = useState<'overview' | 'gifts'>('overview')
  const { data, loading, error, updateAddress, refresh } = useWallet()
  const { data: receivedGifts = [], isLoading: giftsLoading } = useReceivedGifts()
  const [walletAddress, setWalletAddress] = useState('')
  const [isSaving, setIsSaving] = useState(false)

  const selectedTabClasses = useMemo(() => ({
    active: 'bg-gray-900 text-white dark:bg-white dark:text-gray-900',
    inactive: 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
  }), [])

  useEffect(() => {
    if (typeof window === 'undefined') {
      return
    }

    const nextTab = new URLSearchParams(window.location.search).get('tab') === 'gifts' ? 'gifts' : 'overview'
    setActiveTab(nextTab)
  }, [])

  const handleSave = async () => {
    if (!walletAddress.trim()) return
    setIsSaving(true)
    try {
      await updateAddress(walletAddress.trim())
      setWalletAddress('')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Wallet" />
        <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Wallet, Referrals & Payouts</h1>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
              Restored the compact wallet surface plus the referral and payout ledger that several premium, gift, and social-proof flows still depend on.
            </p>
          </div>

          {loading ? (
            <div className="rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900">Loading wallet…</div>
          ) : error ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-8 text-sm text-red-600 shadow-sm dark:border-red-900/40 dark:bg-red-950/20">{error}</div>
          ) : (
            <div className="space-y-6">
              <div className="flex flex-wrap gap-3">
                <a href="/wallet" className={`rounded-xl px-4 py-2 text-sm font-semibold ${activeTab === 'overview' ? selectedTabClasses.active : selectedTabClasses.inactive}`}>Overview</a>
                <a href="/wallet?tab=gifts" className={`rounded-xl px-4 py-2 text-sm font-semibold ${activeTab === 'gifts' ? selectedTabClasses.active : selectedTabClasses.inactive}`}>Received Gifts</a>
              </div>
              <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Token Balance</p>
                  <div className="mt-2 flex items-center gap-3">
                    <Coins className="h-8 w-8 text-yellow-500" />
                    <span className="text-4xl font-black text-gray-900 dark:text-white">{data?.balance ?? '0.00'}</span>
                  </div>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <BadgeDollarSign className="h-5 w-5 text-green-500" />
                    <p className="text-sm font-medium">Pending Payouts</p>
                  </div>
                  <p className="mt-2 text-4xl font-black text-gray-900 dark:text-white">${(data?.pending_cash_usd ?? 0).toFixed(2)}</p>
                  <p className="mt-2 text-sm text-gray-500">Cash commissions earned from premium referrals and awaiting payout.</p>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <Gift className="h-5 w-5 text-pink-500" />
                    <p className="text-sm font-medium">Referral Rewards</p>
                  </div>
                  <p className="mt-2 text-4xl font-black text-gray-900 dark:text-white">{(data?.earned_token_rewards ?? 0).toFixed(2)}</p>
                  <p className="mt-2 text-sm text-gray-500">Token rewards credited from signup and premium referral activity.</p>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <div className="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <Ticket className="h-5 w-5 text-orange-500" />
                    <p className="text-sm font-medium">Golden Tickets</p>
                  </div>
                  <p className="mt-2 text-4xl font-black text-gray-900 dark:text-white">{data?.golden_tickets_remaining ?? 0}</p>
                  <p className="mt-2 text-sm text-gray-500">Still surfaced because multiple premium and event-era paths reference them.</p>
                </div>
              </div>

              {activeTab === 'gifts' ? (
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <div className="mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                    <Gift className="h-5 w-5 text-pink-500" />
                    <h2 className="text-lg font-semibold">Received Gifts</h2>
                  </div>
                  {giftsLoading ? (
                    <p className="text-sm text-gray-500">Loading gifts…</p>
                  ) : receivedGifts.length ? (
                    <div className="space-y-3">
                      {receivedGifts.map((giftEntry) => (
                        <div key={giftEntry.id} className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                          <div className="flex items-start justify-between gap-4">
                            <div>
                              <div className="font-medium text-gray-900 dark:text-white">{giftEntry.gift.name} from {giftEntry.sender?.name || 'Someone'}</div>
                              <div className="mt-1 text-sm text-gray-500">{giftEntry.message || 'No note attached.'}</div>
                              <div className="mt-1 text-xs text-gray-400">{new Date(giftEntry.created_at).toLocaleString()}</div>
                            </div>
                            <div className="text-right text-sm font-semibold text-gray-900 dark:text-white">+{giftEntry.cost_at_time} tokens</div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-500">No gifts received yet.</p>
                  )}
                </div>
              ) : (
              <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <section className="space-y-6">
                  <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Referral Hub</h2>
                        <p className="mt-1 text-sm text-gray-500">Your invite code, share links, and social-proof routes are live again.</p>
                      </div>
                      <button onClick={refresh} className="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                        <RefreshCw className="h-4 w-4" />
                        Refresh
                      </button>
                    </div>

                    <div className="mt-5 space-y-4">
                      <div className="rounded-xl border border-dashed border-gray-300 px-4 py-4 dark:border-gray-700">
                        <div className="mb-2 flex items-center justify-between gap-3">
                          <span className="text-sm font-medium text-gray-500">Referral Code</span>
                          <button onClick={() => copyText(data?.referral_code)} className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            <Copy className="h-4 w-4" />
                            Copy Code
                          </button>
                        </div>
                        <span className="font-mono text-xl font-bold text-gray-900 dark:text-white">{data?.referral_code ?? 'UNAVAILABLE'}</span>
                      </div>

                      <div className="grid gap-3 md:grid-cols-2">
                        <LinkCard title="Invite Link" value={data?.referral_link} onCopy={() => copyText(data?.referral_link)} />
                        <LinkCard title="Vouch Link" value={data?.vouch_link} onCopy={() => copyText(data?.vouch_link)} />
                      </div>

                      <div className="grid gap-3 sm:grid-cols-3">
                        <MetricCard label="Successful Referrals" value={String(data?.referral_count ?? 0)} />
                        <MetricCard label="Vouches" value={String(data?.vouches_count ?? 0)} />
                        <MetricCard label="Signup Bonus" value={`${data?.reward_rules?.signup?.referrer_token_amount ?? 0}/${data?.reward_rules?.signup?.referred_token_amount ?? 0}`} helper="referrer/new user" />
                      </div>
                    </div>
                  </div>

                  <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h2>
                    <div className="mt-4 space-y-3">
                      {data?.transactions?.length ? data.transactions.map((transaction) => (
                        <div key={transaction.id} className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                          <div className="flex items-center justify-between gap-4">
                            <div>
                              <div className="font-medium text-gray-900 dark:text-white">{transaction.description}</div>
                              <div className="text-sm text-gray-500 dark:text-gray-400">{transaction.type} · {new Date(transaction.created_at).toLocaleString()}</div>
                            </div>
                            <div className="text-right font-semibold text-gray-900 dark:text-white">{transaction.amount}</div>
                          </div>
                        </div>
                      )) : <p className="text-sm text-gray-500">No wallet transactions yet.</p>}
                    </div>
                  </div>
                </section>

                <section className="space-y-6">
                  <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="mb-4 flex items-center gap-2">
                      <WalletIcon className="h-5 w-5 text-purple-500" />
                      <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Wallet Address</h2>
                    </div>
                    <p className="mb-3 break-all text-sm text-gray-500">Current: {data?.wallet_address || 'No wallet address saved yet.'}</p>
                    <input
                      value={walletAddress}
                      onChange={(event) => setWalletAddress(event.target.value)}
                      placeholder="Paste your wallet address"
                      className="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    />
                    <button onClick={handleSave} disabled={isSaving} className="mt-3 inline-flex items-center gap-2 rounded-xl bg-purple-600 px-4 py-3 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-60">
                      {isSaving ? 'Saving…' : 'Save Address'}
                    </button>
                  </div>

                  <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div className="mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                      <ShieldCheck className="h-5 w-5 text-green-500" />
                      <h2 className="text-lg font-semibold">Referral Payout Rules</h2>
                    </div>
                    <div className="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                      <RuleRow label="Level 1 premium commission" cash={data?.reward_rules?.level_1?.cash_usd ?? 0} tokens={data?.reward_rules?.level_1?.token_amount ?? 0} />
                      <RuleRow label="Level 2 premium commission" cash={data?.reward_rules?.level_2?.cash_usd ?? 0} tokens={data?.reward_rules?.level_2?.token_amount ?? 0} />
                    </div>
                  </div>

                  <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Commission Ledger</h2>
                    <div className="mt-4 space-y-3">
                      {data?.recent_commissions?.length ? data.recent_commissions.map((commission) => (
                        <div key={commission.id} className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                          <div className="flex items-start justify-between gap-4">
                            <div>
                              <div className="font-medium text-gray-900 dark:text-white">Level {commission.level} premium referral</div>
                              <div className="text-sm text-gray-500">{commission.cash_status} payout · {new Date(commission.created_at || Date.now()).toLocaleString()}</div>
                            </div>
                            <div className="text-right text-sm font-semibold text-gray-900 dark:text-white">
                              <div>${commission.cash_amount}</div>
                              <div>{commission.token_amount} tokens</div>
                            </div>
                          </div>
                        </div>
                      )) : <p className="text-sm text-gray-500">No referral commissions yet.</p>}
                    </div>
                  </div>

                  <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Tier Performance</h2>
                    <div className="mt-4 space-y-3">
                      {data?.levels?.map((level) => (
                        <div key={level.level} className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                          <div className="flex items-center justify-between">
                            <div>
                              <div className="font-medium text-gray-900 dark:text-white">Level {level.level}</div>
                              <div className="text-sm text-gray-500">{level.count} commission{level.count === 1 ? '' : 's'}</div>
                            </div>
                            <div className="text-right text-sm font-semibold text-gray-900 dark:text-white">
                              <div>${level.cash_usd.toFixed(2)}</div>
                              <div>{level.token_amount.toFixed(2)} tokens</div>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </section>
              </div>
              )}
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}

function LinkCard({ title, value, onCopy }: { title: string; value?: string | null; onCopy: () => void }) {
  return (
    <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
      <div className="mb-2 flex items-center gap-2 text-sm font-medium text-gray-500">
        <LinkIcon className="h-4 w-4" />
        {title}
      </div>
      <div className="mb-3 break-all text-sm text-gray-900 dark:text-white">{value || 'Unavailable'}</div>
      <button onClick={onCopy} className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
        <Copy className="h-4 w-4" />
        Copy Link
      </button>
    </div>
  )
}

function MetricCard({ label, value, helper }: { label: string; value: string; helper?: string }) {
  return (
    <div className="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
      <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{label}</p>
      <p className="mt-2 text-2xl font-black text-gray-900 dark:text-white">{value}</p>
      {helper ? <p className="mt-1 text-xs text-gray-500">{helper}</p> : null}
    </div>
  )
}

function RuleRow({ label, cash, tokens }: { label: string; cash: number; tokens: number }) {
  return (
    <div className="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-800">
      <span>{label}</span>
      <span className="font-semibold text-gray-900 dark:text-white">${cash.toFixed(2)} + {tokens.toFixed(2)} tokens</span>
    </div>
  )
}
