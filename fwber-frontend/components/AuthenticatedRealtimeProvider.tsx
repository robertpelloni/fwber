'use client';

import { useAuth } from '@/lib/auth-context';
import { MercureRealtimeProvider } from '@/components/realtime/MercureBridge';

interface AuthenticatedRealtimeProviderProps {
  children: React.ReactNode;
  autoConnect?: boolean;
}

/**
 * A wrapper that provides realtime features only when user is authenticated.
 * 
 * Use this in layouts for sections that need presence, typing indicators, etc.
 * It will gracefully degrade to no realtime features if user is not logged in.
 */
export function AuthenticatedRealtimeProvider({
  children,
  autoConnect = true,
}: AuthenticatedRealtimeProviderProps) {
  const { isAuthenticated } = useAuth();

  // Only wrap with RealtimeProvider if authenticated
  if (!isAuthenticated) {
    return <>{children}</>;
  }

  return (
    <MercureRealtimeProvider autoConnect={autoConnect}>
      {children}
    </MercureRealtimeProvider>
  );
}
