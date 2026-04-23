'use client';

import ProtectedRoute from '@/components/ProtectedRoute';
import ModerationDashboard from '@/components/ModerationDashboard';

export default function ModerationPage() {
  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <ModerationDashboard />
      </div>
    </ProtectedRoute>
  );
}
