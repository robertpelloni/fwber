'use client'

import { useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import AppHeader from '@/components/AppHeader'
import { useWallet } from '@/lib/hooks/useWallet'
import { Coins, Wallet as WalletIcon, RefreshCw, Copy, Ticket } from 'lucide-react'

export default function WalletPage() {
  const { data, loading, error, updateAddress, refresh } = useWallet()
  const [walletAddress, setWalletAddress] = useState('')
  const [isSaving, setIsSaving] = useState(false)

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

  const handleCopy = async () => {
    if (!data?.referral_code) return
    await navigator.clipboard.writeText(data.referral_code)
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
        <AppHeader title="Wallet" />
        <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
          <div className="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Wallet</h1>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
              Restored a compact wallet surface so token-linked upsells and gift/event payment fallbacks land somewhere real again.
            </p>
          </div>

          {loading ? (
            <div className="rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900">Loading wallet…</div>
          ) : error ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-8 text-sm text-red-600 shadow-sm dark:border-red-900/40 dark:bg-red-950/20">{error}</div>
          ) : (
            <div className="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
              <section className="space-y-6">
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Token Balance</p>
                      <div className="mt-2 flex items-center gap-3">
                        <Coins className="h-8 w-8 text-yellow-500" />
                        <span className="text-4xl font-black text-gray-900 dark:text-white">{data?.balance ?? '0.00'}</span>
                      </div>
                    </div>
                    <button onClick={refresh} className="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                      <RefreshCw className="h-4 w-4" />
                      Refresh
                    </button>
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
                  <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Referral Code</h2>
                  <div className="mt-4 flex items-center gap-3 rounded-xl border border-dashed border-gray-300 px-4 py-4 dark:border-gray-700">
                    <span className="flex-1 font-mono text-lg font-bold text-gray-900 dark:text-white">{data?.referral_code ?? 'UNAVAILABLE'}</span>
                    <button onClick={handleCopy} className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                      <Copy className="h-4 w-4" />
                      Copy
                    </button>
                  </div>
                  <p className="mt-3 text-sm text-gray-500">Referral count: {data?.referral_count ?? 0}</p>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                  <div className="mb-4 flex items-center gap-2">
                    <WalletIcon className="h-5 w-5 text-purple-500" />
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Wallet Address</h2>
                  </div>
                  <p className="mb-3 text-sm text-gray-500 break-all">Current: {data?.wallet_address || 'No wallet address saved yet.'}</p>
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
                  <div className="mb-2 flex items-center gap-2 text-gray-900 dark:text-white">
                    <Ticket className="h-5 w-5 text-orange-500" />
                    <h2 className="text-lg font-semibold">Golden Tickets</h2>
                  </div>
                  <p className="text-3xl font-black text-gray-900 dark:text-white">{data?.golden_tickets_remaining ?? 0}</p>
                  <p className="mt-2 text-sm text-gray-500">These are still surfaced because multiple legacy premium/event paths reference them.</p>
                </div>
              </section>
            </div>
          )}
        </main>
      </div>
    </ProtectedRoute>
  )
}
