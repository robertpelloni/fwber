import WalletDashboard from '@/components/wallet/WalletDashboard';
import AppHeader from '@/components/AppHeader';

export default function WalletPage() {
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-950 pb-20">
      <AppHeader />
      <div className="container mx-auto p-4 max-w-2xl mt-4">
        <WalletDashboard />
      </div>
    </div>
  );
}
