'use client';

import { useState } from 'react';
import { useE2EEncryption } from '@/lib/hooks/use-e2e-encryption';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Lock, RefreshCw, Key, ShieldCheck, AlertTriangle } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import AppHeader from '@/components/AppHeader';

export default function SecuritySettingsPage() {
  const { isReady, regenerateKeys } = useE2EEncryption();
  const [isRegenerating, setIsRegenerating] = useState(false);
  const { toast } = useToast();

  const handleRegenerateKeys = async () => {
    if (!confirm('Are you sure? This will make previous encrypted messages unreadable on this device.')) {
      return;
    }

    setIsRegenerating(true);
    try {
      await regenerateKeys();
      toast({
        title: "Keys Regenerated",
        description: "Your encryption keys have been successfully rotated.",
      });
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to regenerate keys.",
        variant: "destructive",
      });
    } finally {
      setIsRegenerating(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <AppHeader />

      <main className="max-w-2xl mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <ShieldCheck className="w-8 h-8 text-green-500" />
          Security & Encryption
        </h1>

        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Lock className="w-5 h-5 text-blue-500" />
                End-to-End Encryption
              </CardTitle>
              <CardDescription>
                Manage your encryption keys for secure messaging.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div className="flex items-center gap-3">
                  <div className={`w-3 h-3 rounded-full ${isReady ? 'bg-green-500' : 'bg-red-500'} animate-pulse`} />
                  <div>
                    <p className="font-medium text-sm text-gray-900 dark:text-white">
                      Encryption Status
                    </p>
                    <p className="text-xs text-gray-500">
                      {isReady ? 'Active & Secure' : 'Keys missing or invalid'}
                    </p>
                  </div>
                </div>
              </div>

              <div className="bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-200 dark:border-yellow-900 p-4 rounded-lg">
                <h4 className="text-sm font-semibold text-yellow-800 dark:text-yellow-200 flex items-center gap-2 mb-2">
                  <AlertTriangle className="w-4 h-4" />
                  Warning
                </h4>
                <p className="text-xs text-yellow-700 dark:text-yellow-300">
                  Regenerating your keys will make it impossible to decrypt old messages sent to your previous key pair. Only do this if you believe your keys have been compromised.
                </p>
              </div>

              <Button
                variant="destructive"
                onClick={handleRegenerateKeys}
                disabled={isRegenerating}
                className="w-full sm:w-auto"
              >
                {isRegenerating ? (
                  <>
                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                    Regenerating...
                  </>
                ) : (
                  <>
                    <Key className="w-4 h-4 mr-2" />
                    Regenerate Keys
                  </>
                )}
              </Button>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
}
