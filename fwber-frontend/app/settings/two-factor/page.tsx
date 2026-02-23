'use client';

import { useState, useEffect } from 'react';
import ProtectedRoute from '@/components/ProtectedRoute';
import { useAuth } from '@/lib/auth-context';
import { apiClient } from '@/lib/api/client';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ShieldCheck, ArrowLeft, Smartphone } from 'lucide-react';
import Link from 'next/link';
import { Switch } from '@/components/ui/switch';
import { useToast } from '@/components/ui/use-toast';
import TwoFactorRecoveryCodes from '@/components/settings/TwoFactorRecoveryCodes';

export default function TwoFactorPage() {
  const { user } = useAuth();
  const [isEnabled, setIsEnabled] = useState(false);
  const [qrCode, setQrCode] = useState<string | null>(null);
  const [setupKey, setSetupKey] = useState<string | null>(null);
  const [confirmationCode, setConfirmationCode] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    // Check initial status
    // In a real app, this might come from the user object or a status endpoint
    // For now, we assume user.two_factor_enabled is a boolean on the user object
    if (user && (user as any).two_factor_enabled) {
      setIsEnabled(true);
    }
  }, [user]);

  const enableTwoFactor = async () => {
    setIsLoading(true);
    try {
      await apiClient.post('/user/two-factor-authentication');
      // Fetch QR Code
      const res = await apiClient.get('/user/two-factor-qr-code');
      setQrCode(res.data.svg);
      setSetupKey(res.data.url);
    } catch (error) {
      toast({ title: "Error", description: "Failed to enable 2FA", variant: "destructive" });
    } finally {
      setIsLoading(false);
    }
  };

  const confirmTwoFactor = async () => {
    setIsLoading(true);
    try {
      await apiClient.post('/user/confirmed-two-factor-authentication', { code: confirmationCode });
      setIsEnabled(true);
      setQrCode(null);
      toast({ title: "Success", description: "2FA is now enabled." });
    } catch (error) {
      toast({ title: "Error", description: "Invalid code. Please try again.", variant: "destructive" });
    } finally {
      setIsLoading(false);
    }
  };

  const disableTwoFactor = async () => {
    if (!confirm('Are you sure you want to disable 2FA? Your account will be less secure.')) return;
    
    setIsLoading(true);
    try {
      await apiClient.delete('/user/two-factor-authentication');
      setIsEnabled(false);
      setQrCode(null);
      toast({ title: "Disabled", description: "Two-factor authentication disabled." });
    } catch (error) {
      toast({ title: "Error", description: "Failed to disable 2FA", variant: "destructive" });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <header className="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10">
          <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div className="flex items-center gap-4">
              <Link href="/settings" className="p-2 -ml-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
                <ArrowLeft className="w-5 h-5 text-gray-600 dark:text-gray-300" />
              </Link>
              <h1 className="text-xl font-bold text-gray-900 dark:text-white">Two-Factor Authentication</h1>
            </div>
          </div>
        </header>

        <main className="max-w-2xl mx-auto px-4 py-8 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <ShieldCheck className={`w-6 h-6 ${isEnabled ? 'text-green-500' : 'text-gray-400'}`} />
                Status: {isEnabled ? 'Enabled' : 'Disabled'}
              </CardTitle>
              <CardDescription>
                Add an extra layer of security to your account using an authenticator app (like Google Authenticator or Authy).
              </CardDescription>
            </CardHeader>
            <CardContent>
              {!isEnabled && !qrCode && (
                <Button onClick={enableTwoFactor} disabled={isLoading}>
                  Enable 2FA
                </Button>
              )}

              {qrCode && !isEnabled && (
                <div className="space-y-4">
                  <div className="p-4 bg-white rounded-lg border inline-block" dangerouslySetInnerHTML={{ __html: qrCode }} />
                  <p className="text-sm text-gray-600">Scan this QR code with your authenticator app.</p>
                  
                  <div className="flex gap-2">
                    <input
                      type="text"
                      placeholder="Enter code"
                      className="border p-2 rounded"
                      value={confirmationCode}
                      onChange={(e) => setConfirmationCode(e.target.value)}
                    />
                    <Button onClick={confirmTwoFactor} disabled={isLoading || confirmationCode.length < 6}>
                      Confirm
                    </Button>
                  </div>
                </div>
              )}

              {isEnabled && (
                <div className="space-y-4">
                  <p className="text-sm text-green-600 font-medium flex items-center gap-2">
                    <ShieldCheck className="w-4 h-4" /> Your account is secure.
                  </p>
                  <Button variant="destructive" onClick={disableTwoFactor} disabled={isLoading}>
                    Disable 2FA
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>

          {isEnabled && <TwoFactorRecoveryCodes />}
        </main>
      </div>
    </ProtectedRoute>
  );
}
