import { useState, useEffect } from 'react'
import { apiClient } from '@/lib/api/client'

export interface Transaction {
  id: number
  amount: string
  type: string
  description: string
  created_at: string
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
}

export function useWallet() {
  const [data, setData] = useState<WalletData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const fetchWallet = async () => {
    try {
      setLoading(true)
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
