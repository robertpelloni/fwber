'use client';
import { useWallet as useSolanaWallet } from '@solana/wallet-adapter-react';
import dynamic from 'next/dynamic';
import { useWallet as useInternalWallet } from '@/lib/hooks/useWallet';
import { useState } from 'react';
import { apiClient } from '@/lib/api/client';
import { Loader2 } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import PaymentRequests from './PaymentRequests';

const WalletMultiButton = dynamic(
  () => import('@solana/wallet-adapter-react-ui').then((mod) => mod.WalletMultiButton),
  { ssr: false }
);

export default function WalletDashboard() {
  const { publicKey } = useSolanaWallet();
  const { data: internalWallet, refresh } = useInternalWallet();
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<'wallet' | 'requests'>('wallet');

  const handleWithdraw = async () => {
    if (!publicKey || !withdrawAmount) return;
    setLoading(true);
    try {
      await apiClient.post('/wallet/withdraw', {
        amount: parseFloat(withdrawAmount),
        destination_address: publicKey.toBase58()
      });
      refresh();
      setWithdrawAmount('');
      alert('Withdrawal successful! Check your wallet.');
    } catch (e: any) {
      alert('Withdrawal failed: ' + (e.response?.data?.error || e.message));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-2xl font-bold">Wallet & Crypto</h2>
        <div className="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">Devnet</div>
      </div>

      {/* Tabs */}
      <div className="flex space-x-1 p-1 bg-gray-100 dark:bg-gray-700/50 rounded-lg">
        <button
            onClick={() => setActiveTab('wallet')}
            className={`flex-1 py-2 text-sm font-medium rounded-md transition-all ${
                activeTab === 'wallet'
                ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
            }`}
        >
            Dashboard
        </button>
        <button
            onClick={() => setActiveTab('requests')}
            className={`flex-1 py-2 text-sm font-medium rounded-md transition-all ${
                activeTab === 'requests'
                ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
            }`}
        >
            Requests
        </button>
      </div>

      {activeTab === 'wallet' ? (
        <div className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
            {/* Internal Balance */}
            <div className="p-4 border border-purple-200 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                <h3 className="text-sm font-medium text-purple-800 dark:text-purple-300">Internal Balance ($FWB)</h3>
                <p className="text-3xl font-bold text-purple-600 dark:text-purple-400">
                {internalWallet?.balance || '0.00'} FWB
                </p>
                <p className="text-xs text-gray-500 mt-1">Use for instant tips and boosts.</p>
            </div>

            {/* External Wallet */}
            <div className="space-y-4">
                <div className="flex justify-between items-center">
                <h3 className="font-medium">External Wallet (Solana)</h3>
                <WalletMultiButton className="!bg-purple-600 hover:!bg-purple-700" />
                </div>

                {publicKey ? (
                <div className="p-4 border rounded-xl space-y-4 dark:border-gray-700">
                    <div className="flex flex-col items-center justify-center p-4 bg-white rounded-lg shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-600">
                        <QRCodeSVG value={publicKey.toBase58()} size={160} className="p-2 bg-white rounded" />
                        <p className="mt-2 text-xs text-gray-500">Scan to deposit SOL or SPL Tokens</p>
                    </div>

                    <p className="text-sm break-all font-mono bg-gray-100 dark:bg-gray-900 p-2 rounded text-center">
                    {publicKey.toBase58()}
                    </p>

                    <div className="flex gap-2 items-end">
                    <div className="flex-1">
                        <label className="text-xs font-medium mb-1 block">Withdraw Amount</label>
                        <input
                        type="number"
                        value={withdrawAmount}
                        onChange={e => setWithdrawAmount(e.target.value)}
                        className="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600"
                        placeholder="100"
                        />
                    </div>
                    <button
                        onClick={handleWithdraw}
                        disabled={loading || !withdrawAmount}
                        className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50 flex items-center gap-2 h-[42px]"
                    >
                        {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                        Withdraw
                    </button>
                    </div>
                    <p className="text-xs text-gray-500">Withdrawals are processed on the Solana Devnet.</p>
                </div>
                ) : (
                <div className="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl text-center">
                    <p className="text-sm text-gray-500 mb-2">Connect your Phantom or Solflare wallet to withdraw tokens to the blockchain.</p>
                </div>
                )}
            </div>

            {/* Referral */}
            {internalWallet?.referral_code && (
                <div className="p-4 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl">
                    <p className="text-sm opacity-90">Refer a friend & earn 50 FWB</p>
                    <p className="text-2xl font-mono font-bold mt-1 tracking-wider">{internalWallet.referral_code}</p>
                </div>
            )}

            {/* Transaction History */}
            <div className="space-y-4">
                <h3 className="font-medium">Transaction History</h3>
                <div className="space-y-2">
                    {internalWallet?.transactions?.length ? (
                        internalWallet.transactions.map((tx: any) => (
                            <div key={tx.id} className="flex justify-between items-center p-3 border rounded dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-900/50">
                                <div>
                                    <p className="font-medium">{tx.description}</p>
                                    <p className="text-gray-500 text-xs">{new Date(tx.created_at).toLocaleDateString()}</p>
                                </div>
                                <span className={`font-bold ${parseFloat(tx.amount) > 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    {parseFloat(tx.amount) > 0 ? '+' : ''}{tx.amount} FWB
                                </span>
                            </div>
                        ))
                    ) : (
                        <p className="text-gray-500 text-sm">No transactions yet.</p>
                    )}
                </div>
            </div>
        </div>
      ) : (
        <div className="animate-in fade-in slide-in-from-bottom-2 duration-300">
            <PaymentRequests />
        </div>
      )}
    </div>
  );
}
