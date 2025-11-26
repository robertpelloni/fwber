'use client';

import { AuthenticatedRealtimeProvider } from '@/components/AuthenticatedRealtimeProvider';

export default function ChatroomsLayout({
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
