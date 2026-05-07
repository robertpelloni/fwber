'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/auth-context';

export default function MerchantPage() {
  const router = useRouter();
  const { user } = useAuth();

  useEffect(() => {
    // Redirect to merchant dashboard (or register if not a merchant)
    if (user) {
      router.replace('/merchant/dashboard');
    } else {
      router.replace('/merchant/register');
    }
  }, [user, router]);

  return (
    <div className="min-h-screen flex items-center justify-center">
      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500" />
    </div>
  );
}
