'use client';

import { useE2EEncryption } from '@/lib/hooks/use-e2e-encryption';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Key } from 'lucide-react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

export function E2ERecoveryAlert() {
  const { isRestorable } = useE2EEncryption();
  const pathname = usePathname();

  // Don't show the alert if they are already on the security settings page
  if (!isRestorable || pathname === '/settings/security') return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-8 md:bottom-8 md:w-96 z-50 animate-in slide-in-from-bottom-5">
      <Alert className="border-blue-500 bg-blue-50 dark:bg-blue-900/90 shadow-2xl backdrop-blur-md">
        <Key className="h-5 w-5 text-blue-600 dark:text-blue-400" />
        <AlertTitle className="text-blue-800 dark:text-blue-200 font-bold text-base">Encrypted Backup Found</AlertTitle>
        <AlertDescription className="text-blue-700 dark:text-blue-300 mt-2 text-sm leading-relaxed">
          <p className="mb-3">We found a secure backup of your encryption keys on our server. Restore them now to read your existing encrypted messages on this device.</p>
          <div className="flex justify-end">
            <Link href="/settings/security">
              <Button size="sm" className="bg-blue-600 hover:bg-blue-700 text-white font-semibold w-full md:w-auto shadow-md">
                Restore Keys
              </Button>
            </Link>
          </div>
        </AlertDescription>
      </Alert>
    </div>
  );
}
