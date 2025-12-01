'use client';

import { useState, useEffect } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { CreditCard, Check, Clock, AlertCircle, Star } from 'lucide-react';
import Link from 'next/link';

interface Payment {
  id: number;
  amount: string;
  currency: string;
  status: string;
  description: string;
  created_at: string;
}

interface PremiumStatus {
  is_premium: boolean;
  tier: string;
  expires_at: string | null;
  unlimited_swipes: boolean;
}

export default function SubscriptionPage() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [purchasing, setPurchasing] = useState(false);
  const [status, setStatus] = useState<PremiumStatus | null>(null);
  const [history, setHistory] = useState<Payment[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [statusData, historyData] = await Promise.all([
        api.get<PremiumStatus>('/premium/status'),
        api.get<any>('/subscriptions')
      ]);
      setStatus(statusData);
      setHistory(historyData.data || historyData); // Handle pagination or direct array
    } catch (err) {
      console.error('Failed to fetch subscription data:', err);
      setError('Failed to load subscription details.');
    } finally {
      setLoading(false);
    }
  };

  const handlePurchase = async () => {
    try {
      setPurchasing(true);
      setError(null);
      setSuccessMessage(null);
      
      // Call the purchase endpoint (using mock gateway by default)
      await api.post('/premium/purchase', {
        payment_method_id: 'tok_visa' // Mock token
      });

      setSuccessMessage('Premium subscription activated successfully!');
      await fetchData(); // Refresh data
    } catch (err: any) {
      console.error('Purchase failed:', err);
      setError(err.message || 'Purchase failed. Please try again.');
    } finally {
      setPurchasing(false);
    }
  };

  if (loading) {
    return (
      <ProtectedRoute>
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        </div>
      </ProtectedRoute>
    );
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        <header className="bg-white shadow-sm">
          <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div className="flex items-center gap-3">
              <Link href="/settings" className="text-gray-400 hover:text-gray-600">
                Settings
              </Link>
              <span className="text-gray-300">/</span>
              <h1 className="text-2xl font-bold text-gray-900">Subscription</h1>
            </div>
          </div>
        </header>

        <main className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          {/* Current Plan */}
          <section className="mb-8">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Current Plan</h2>
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
              <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                      {status?.is_premium ? (
                        <>
                          <Star className="w-6 h-6 text-yellow-500 fill-current" />
                          Gold Tier
                        </>
                      ) : (
                        'Free Tier'
                      )}
                    </h3>
                    <p className="text-gray-500 mt-1">
                      {status?.is_premium 
                        ? `Your premium benefits are active until ${new Date(status.expires_at!).toLocaleDateString()}`
                        : 'Upgrade to unlock premium features'}
                    </p>
                  </div>
                  <div className={`px-4 py-1 rounded-full text-sm font-medium ${
                    status?.is_premium ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600'
                  }`}>
                    {status?.is_premium ? 'Active' : 'Free'}
                  </div>
                </div>

                {status?.is_premium ? (
                  <div className="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                    <div className="flex">
                      <Check className="h-5 w-5 text-green-400" />
                      <div className="ml-3">
                        <h3 className="text-sm font-medium text-green-800">Premium Active</h3>
                        <div className="mt-2 text-sm text-green-700">
                          <p>You have access to all Gold features including unlimited swipes and &quot;Who Likes You&quot;.</p>
                        </div>
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-6">
                    <h4 className="font-semibold text-purple-900 mb-2">Upgrade to Gold</h4>
                    <ul className="space-y-2 mb-6">
                      <li className="flex items-center gap-2 text-purple-800">
                        <Check className="w-4 h-4" /> See who likes you
                      </li>
                      <li className="flex items-center gap-2 text-purple-800">
                        <Check className="w-4 h-4" /> Unlimited swipes
                      </li>
                      <li className="flex items-center gap-2 text-purple-800">
                        <Check className="w-4 h-4" /> Priority support
                      </li>
                    </ul>
                    <button
                      onClick={handlePurchase}
                      disabled={purchasing}
                      className="w-full py-3 px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                      {purchasing ? (
                        <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                      ) : (
                        <>
                          <Star className="w-4 h-4 fill-current" />
                          Upgrade for $19.99/mo
                        </>
                      )}
                    </button>
                    {error && (
                      <div className="mt-4 p-3 bg-red-50 text-red-700 text-sm rounded-md flex items-center gap-2">
                        <AlertCircle className="w-4 h-4" />
                        {error}
                      </div>
                    )}
                    {successMessage && (
                      <div className="mt-4 p-3 bg-green-50 text-green-700 text-sm rounded-md flex items-center gap-2">
                        <Check className="w-4 h-4" />
                        {successMessage}
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </section>

          {/* Payment History */}
          <section>
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Payment History</h2>
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
              {history.length === 0 ? (
                <div className="p-8 text-center text-gray-500">
                  <Clock className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                  <p>No payment history found.</p>
                </div>
              ) : (
                <div className="divide-y divide-gray-200">
                  {history.map((payment) => (
                    <div key={payment.id} className="p-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <div className={`p-2 rounded-full ${
                          payment.status === 'succeeded' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
                        }`}>
                          <CreditCard className="w-5 h-5" />
                        </div>
                        <div>
                          <p className="font-medium text-gray-900">{payment.description}</p>
                          <p className="text-sm text-gray-500">
                            {new Date(payment.created_at).toLocaleDateString()}
                          </p>
                        </div>
                      </div>
                      <div className="text-right">
                        <p className="font-medium text-gray-900">
                          {payment.currency.toUpperCase()} {parseFloat(payment.amount).toFixed(2)}
                        </p>
                        <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${
                          payment.status === 'succeeded' 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                        }`}>
                          {payment.status}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </section>
        </main>
      </div>
    </ProtectedRoute>
  );
}
