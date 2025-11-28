'use client';

import { AuthenticatedRealtimeProvider } from '@/components/AuthenticatedRealtimeProvider';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <AuthenticatedRealtimeProvider>
      {children}
    </AuthenticatedRealtimeProvider>
  );
}
