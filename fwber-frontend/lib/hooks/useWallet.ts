import { useEffect, useState } from 'react'
import { apiClient } from '@/lib/api/client'

export interface Transaction {
  id: number
  amount: string
  type: string
  description: string
  created_at: string
}

export interface ReferralLevelSummary {
  level: number
  count: number
  cash_usd: number
  token_amount: number
}

export interface ReferralCommissionSummary {
  id: number
  level: number
  cash_amount: string
  cash_status: string
  token_amount: string
  source: string
  created_at: string | null
}

export interface WalletData {
  balance: string
  referral_code: string
  wallet_address: string | null
  transactions: Transaction[]
  referral_count: number
  golden_tickets_remaining: number
  treasury_address?: string
  mint_address?: string
  referral_link?: string
  vouch_link?: string
  vouches_count?: number
  pending_cash_usd?: number
  earned_token_rewards?: number
  levels?: ReferralLevelSummary[]
  recent_commissions?: ReferralCommissionSummary[]
  reward_rules?: {
    signup?: {
      referrer_token_amount: number
      referred_token_amount: number
    }
    level_1?: {
      cash_usd: number
      token_amount: number
    }
    level_2?: {
      cash_usd: number
      token_amount: number
    }
  }
}

export function useWallet() {
  const [data, setData] = useState<WalletData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const fetchWallet = async () => {
    try {
      setLoading(true)
      setError(null)
      const response = await apiClient.get<WalletData>('/wallet')
      setData(response.data)
    } catch (err) {
      setError('Failed to load wallet data')
    } finally {
      setLoading(false)
    }
  }

  const updateAddress = async (address: string) => {
    await apiClient.post('/wallet/address', { wallet_address: address })
    fetchWallet()
  }

  useEffect(() => {
    fetchWallet()
  }, [])

  return { data, loading, error, updateAddress, refresh: fetchWallet }
}
