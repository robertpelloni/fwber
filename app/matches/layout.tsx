'use client';

import { AuthenticatedRealtimeProvider } from '@/components/AuthenticatedRealtimeProvider';

export default function MatchesLayout({
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
