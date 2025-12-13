'use client';

import { AuthenticatedRealtimeProvider } from '@/components/AuthenticatedRealtimeProvider';

export default function NearbyLayout({
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
