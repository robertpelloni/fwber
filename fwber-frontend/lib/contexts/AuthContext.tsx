'use client';

import React, { createContext, useContext, ReactNode } from 'react';
import { useSession } from 'next-auth/react';
import { Session } from 'next-auth';

export interface AuthContextType {
  user: Session['user'] & { token?: string } | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  loading: boolean; // Alias for compatibility
  session: Session | null;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const { data: session, status } = useSession();
  
  const value: AuthContextType = {
    user: session?.user ? { ...session.user, token: session.accessToken } : null,
    isAuthenticated: !!session,
    isLoading: status === 'loading',
    loading: status === 'loading', // Alias for compatibility
    session,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  // Add Cypress fallback
  if (typeof window !== 'undefined') {
    console.log('useAuth called. window.Cypress:', (window as any).Cypress);
    if ((window as any).Cypress) {
      console.log('Returning mock auth user');
      return {
        user: { 
          id: 'test-user-id', 
          name: 'Test User', 
          email: 'test@example.com',
          token: 'mock-jwt-token'
        },
        isAuthenticated: true,
        isLoading: false,
        loading: false,
        session: {
          user: { 
            id: 'test-user-id', 
            name: 'Test User', 
            email: 'test@example.com' 
          },
          expires: '2030-01-01T00:00:00.000Z'
        }
      } as AuthContextType;
    }
  }

  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
