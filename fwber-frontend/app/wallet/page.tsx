'use client'

import { useState } from 'react'
import ProtectedRoute from '@/components/ProtectedRoute'
import { useWallet } from '@/lib/hooks/useWallet'
import { Copy, Wallet, Users, TrendingUp, History } from 'lucide-react'
import { useToast } from '@/components/ToastProvider'

export default function WalletPage() {
  const { data, loading, error, updateAddress } = useWallet()
  const { showSuccess, showError } = useToast()
  const [addressInput, setAddressInput] = useState('')
  const [isEditingAddress, setIsEditingAddress] = useState(false)

  const handleCopyReferral = () => {
    if (data?.referral_code) {
      navigator.clipboard.writeText(data.referral_code)
      showSuccess('Referral code copied!')
    }
  }

  const handleUpdateAddress = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await updateAddress(addressInput)
      setIsEditingAddress(false)
      showSuccess('Wallet address updated')
    } catch (err) {
      showError('Failed to update address')
    }
  }

  if (loading) return <div className="p-8 text-center">Loading wallet...</div>
  if (error) return <div className="p-8 text-center text-red-500">{error}</div>
  if (!data) return null

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-8">My Ownership Wallet</h1>

          {/* Balance Card */}
          <div className="bg-gradient-to-r from-purple-600 to-blue-600 rounded-2xl p-8 text-white shadow-lg mb-8">
            <div className="flex items-center gap-4 mb-4">
              <div className="p-3 bg-white/20 rounded-full">
                <Wallet className="w-8 h-8" />
              </div>
              <div>
                <p className="text-blue-100 font-medium">Total Balance</p>
                <h2 className="text-4xl font-bold">{parseFloat(data.balance).toLocaleString()} FWB</h2>
              </div>
            </div>
            <p className="text-blue-100 text-sm">
              These tokens represent your ownership stake in the platform. 
              Hold them to unlock future benefits and governance rights.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            {/* Referral Card */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div className="flex items-center gap-3 mb-4">
                <Users className="w-6 h-6 text-green-500" />
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Referral Program</h3>
              </div>
              <p className="text-gray-600 dark:text-gray-400 mb-4">
                Earn 50 FWB for every friend you invite. They get a bonus too!
              </p>
              <div className="flex items-center gap-2 bg-gray-100 dark:bg-gray-900 p-3 rounded-lg">
                <code className="flex-1 font-mono text-lg font-bold text-center text-gray-800 dark:text-gray-200">
                  {data.referral_code}
                </code>
                <button
                  onClick={handleCopyReferral}
                  className="p-2 text-gray-500 hover:text-blue-600 transition-colors"
                  title="Copy code"
                >
                  <Copy className="w-5 h-5" />
                </button>
              </div>
              <div className="mt-4 text-sm text-gray-500 text-center">
                {data.referral_count} friends referred so far
              </div>
            </div>

            {/* Wallet Address */}
            <div className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div className="flex items-center gap-3 mb-4">
                <TrendingUp className="w-6 h-6 text-orange-500" />
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">External Wallet</h3>
              </div>
              <p className="text-gray-600 dark:text-gray-400 mb-4">
                Connect your crypto wallet to receive future airdrops.
              </p>
              
              {isEditingAddress ? (
                <form onSubmit={handleUpdateAddress} className="flex gap-2">
                  <input
                    type="text"
                    value={addressInput}
                    onChange={(e) => setAddressInput(e.target.value)}
                    placeholder="0x..."
                    className="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-900 dark:border-gray-600"
                  />
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                  >
                    Save
                  </button>
                </form>
              ) : (
                <div className="flex items-center justify-between bg-gray-50 dark:bg-gray-900 p-3 rounded-lg">
                  <span className="text-gray-600 dark:text-gray-400 truncate max-w-[200px]">
                    {data.wallet_address || 'No address linked'}
                  </span>
                  <button
                    onClick={() => {
                      setAddressInput(data.wallet_address || '')
                      setIsEditingAddress(true)
                    }}
                    className="text-sm text-blue-600 hover:underline"
                  >
                    {data.wallet_address ? 'Edit' : 'Connect'}
                  </button>
                </div>
              )}
            </div>
          </div>

          {/* Transaction History */}
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div className="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
              <History className="w-5 h-5 text-gray-500" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">History</h3>
            </div>
            <div className="divide-y divide-gray-200 dark:divide-gray-700">
              {data.transactions.map((tx) => (
                <div key={tx.id} className="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-750">
                  <div>
                    <p className="font-medium text-gray-900 dark:text-white">
                      {tx.type === 'signup_bonus' ? 'Early Adopter Bonus' : 
                       tx.type === 'referral_bonus' ? 'Referral Reward' : 
                       tx.type === 'referral_accepted_bonus' ? 'Referral Signup Bonus' : tx.type}
                    </p>
                    <p className="text-sm text-gray-500">{tx.description}</p>
                  </div>
                  <div className="text-right">
                    <span className="block font-bold text-green-600 dark:text-green-400">
                      +{parseFloat(tx.amount).toLocaleString()} FWB
                    </span>
                    <span className="text-xs text-gray-400">
                      {new Date(tx.created_at).toLocaleDateString()}
                    </span>
                  </div>
                </div>
              ))}
              {data.transactions.length === 0 && (
                <div className="p-8 text-center text-gray-500">
                  No transactions yet
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </ProtectedRoute>
  )
}
