'use client';

import { useState } from 'react';
import { useE2EEncryption } from '@/lib/hooks/use-e2e-encryption';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Lock, RefreshCw, Key, ShieldCheck, AlertTriangle, Globe, UserX } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import AppHeader from '@/components/AppHeader';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

export default function SecuritySettingsPage() {
  const { isReady, regenerateKeys } = useE2EEncryption();
  const [isRegenerating, setIsRegenerating] = useState(false);
  const [isFederated, setIsFederated] = useState(false); // Default to off, load from API in prod
  const [decoyPassword, setDecoyPassword] = useState('');
  const [isDecoyLoading, setIsDecoyLoading] = useState(false);
  const { toast } = useToast();

  const handleCreateDecoy = async () => {
    if (decoyPassword.length < 8) {
      toast({ title: 'Error', description: 'Password must be at least 8 characters.', variant: 'destructive' });
      return;
    }
    setIsDecoyLoading(true);
    try {
      await api.post('/settings/decoy-profile', { decoy_password: decoyPassword });
      toast({ title: 'Success', description: 'Decoy Profile linked to this password.' });
      setDecoyPassword('');
    } catch (e) {
      toast({ title: 'Error', description: 'Failed to create decoy profile.', variant: 'destructive' });
    } finally {
      setIsDecoyLoading(false);
    }
  };

  const handleRemoveDecoy = async () => {
    setIsDecoyLoading(true);
    try {
      await api.delete('/settings/decoy-profile');
      toast({ title: 'Removed', description: 'Decoy Profile detached.' });
    } catch (e) {
      toast({ title: 'Error', description: 'Failed to remove', variant: 'destructive' });
    } finally {
      setIsDecoyLoading(false);
    }
  };

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

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Globe className="w-5 h-5 text-indigo-500" />
                Fediverse Integration
              </CardTitle>
              <CardDescription>
                Allow your profile to interact with users on Mastodon, Misskey, and other ActivityPub-compatible servers.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div className="flex items-center gap-3">
                  <div className={`w-3 h-3 rounded-full ${isFederated ? 'bg-green-500' : 'bg-gray-500'}`} />
                  <div>
                    <Label htmlFor="federation-toggle" className="font-medium text-sm text-gray-900 dark:text-white cursor-pointer hover:underline">
                      ActivityPub Broadcasting
                    </Label>
                    <p className="text-xs text-gray-500 mt-1">
                      {isFederated ? 'Your profile is discoverable via WebFinger.' : 'Your profile is isolated to this server.'}
                    </p>
                  </div>
                </div>
                <Switch
                  id="federation-toggle"
                  checked={isFederated}
                  onCheckedChange={(checked) => {
                    setIsFederated(checked);
                    toast({
                      title: "Federation Updated",
                      description: checked ? "You mapped your profile to the Fediverse!" : "You have isolated your profile locally.",
                    });
                    // In a real implementation: Update via API hook -> /api/profile
                  }}
                />
              </div>

              <div className="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-900 p-4 rounded-lg">
                <p className="text-xs text-indigo-700 dark:text-indigo-300 font-mono">
                  Your Fediverse Handle: @[your_name]@{typeof window !== 'undefined' ? window.location.hostname : 'domain.com'}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card className="border-red-200 dark:border-red-900/50">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-600 dark:text-red-400">
                <UserX className="w-5 h-5 text-red-500" />
                Emergency Decoy Profile
              </CardTitle>
              <CardDescription>
                If you are ever coerced into opening this app, you can log in with a secondary &quot;Decoy Password&quot;. This will seamlessly log you into a completely different, plausible profile instead of your real one.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="decoy">Set Decoy Password</Label>
                <Input
                  id="decoy"
                  type="password"
                  value={decoyPassword}
                  onChange={e => setDecoyPassword(e.target.value)}
                  placeholder="A password different from your main one"
                />
                <p className="text-xs text-gray-500">
                  Use your normal login email, but enter this alternate password at the login screen.
                </p>
              </div>
            </CardContent>
            <CardFooter className="flex gap-2">
              <Button variant="default" onClick={handleCreateDecoy} disabled={isDecoyLoading || !decoyPassword}>
                {isDecoyLoading ? 'Saving...' : 'Set Decoy Password'}
              </Button>
              <Button variant="outline" onClick={handleRemoveDecoy} disabled={isDecoyLoading}>
                Disable Decoy Mode
              </Button>
            </CardFooter>
          </Card>
        </div>
      </main>
    </div>
  );
}
