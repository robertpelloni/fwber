'use client';

import { AuthenticatedRealtimeProvider } from '@/components/AuthenticatedRealtimeProvider';

export default function FriendsLayout({
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
