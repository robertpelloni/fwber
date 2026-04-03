'use client';

import { useState, useEffect } from 'react';
import { useE2EEncryption } from '@/lib/hooks/use-e2e-encryption';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Lock, RefreshCw, Key, ShieldCheck, AlertTriangle, UserX, Mic2, Zap, Loader2 } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import AppHeader from '@/components/AppHeader';
import { useAuth } from '@/lib/auth-context';
import { api } from '@/lib/api/client';
import { updateUserProfile } from '@/lib/api/profile';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { runEncryptionBenchmark, BenchmarkResult } from '@/lib/wasm/benchmark';

export default function SecuritySettingsPage() {
  const { isReady, regenerateKeys, backupKeys, restoreKeys } = useE2EEncryption();
  const { token, user, updateUser } = useAuth();
  const [isRegenerating, setIsRegenerating] = useState(false);
  const [backupPassphrase, setBackupPassphrase] = useState('');
  const [restorePassphrase, setRestorePassphrase] = useState('');
  const [isBackingUp, setIsBackingUp] = useState(false);
  const [isRestoring, setIsRestoring] = useState(false);
  const [isConfessional, setIsConfessional] = useState(false);
  const [decoyPassword, setDecoyPassword] = useState('');
  const [isDecoyLoading, setIsDecoyLoading] = useState(false);
  const [benchmark, setBenchmark] = useState<BenchmarkResult | null>(null);
  const [isBenchmarking, setIsBenchmarking] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (user?.profile?.is_confessional_mode !== undefined) {
      setIsConfessional(user.profile.is_confessional_mode);
    }
  }, [user]);

  const handleRunBenchmark = async () => {
    setIsBenchmarking(true);
    try {
        const result = await runEncryptionBenchmark();
        setBenchmark(result);
    } catch (e) {
        console.error(e);
    } finally {
        setIsBenchmarking(false);
    }
  };

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

  const handleConfessionalToggle = async (checked: boolean) => {
    setIsConfessional(checked);
    if (!token) return;
    try {
      await updateUserProfile(token, { is_confessional_mode: checked });
      toast({
        title: checked ? "Confessional Mode Active" : "Confessional Mode Disabled",
        description: checked 
          ? "Profiles and photos are now hidden. Matches are based purely on voice." 
          : "Standard profile and photo visibility restored.",
      });
    } catch (error) {
      setIsConfessional(!checked); // revert on failure
      toast({
        title: "Error",
        description: "Failed to update privacy settings.",
        variant: "destructive",
      });
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <AppHeader />

      <main className="max-w-2xl mx-auto px-4 py-8 pb-20">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
          <ShieldCheck className="w-8 h-8 text-green-500" />
          Security & Privacy
        </h1>

        <div className="space-y-6">
          <Card className="border-purple-200 dark:border-purple-900/50 overflow-hidden relative">
            <div className="absolute top-0 right-0 p-4 opacity-5">
                <Mic2 className="w-24 h-24 rotate-12" />
            </div>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                <Mic2 className="w-5 h-5" />
                Voice-Only Mode
              </CardTitle>
              <CardDescription>
                Disable photos and bios entirely. Force matches to be made based on your voice.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/10 rounded-lg border border-purple-100 dark:border-purple-900/30">
                <div className="flex items-center gap-3">
                  <div className={`w-3 h-3 rounded-full ${isConfessional ? 'bg-purple-500' : 'bg-gray-500'}`} />
                  <div>
                    <Label htmlFor="confessional-toggle" className="font-medium text-sm text-gray-900 dark:text-white cursor-pointer">
                      Enable Confessional Mode
                    </Label>
                  </div>
                </div>
                <Switch id="confessional-toggle" checked={isConfessional} onCheckedChange={handleConfessionalToggle} />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Lock className="w-5 h-5 text-blue-500" />
                End-to-End Encryption
              </CardTitle>
              <CardDescription>
                Your private keys never leave your device unencrypted. Back them up with a passphrase you'll remember.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* --- Key Backup --- */}
              <div className="space-y-3 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-100 dark:border-blue-900/30">
                <h3 className="text-sm font-semibold text-blue-700 dark:text-blue-300 flex items-center gap-2">
                  <ShieldCheck className="w-4 h-4" /> Backup Keys
                </h3>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  Encrypt your private keys with a passphrase and store the backup on our server. If you switch devices, you can recover your chat history.
                </p>
                <div className="flex gap-2">
                  <Input
                    type="password"
                    placeholder="Enter a strong passphrase"
                    value={backupPassphrase}
                    onChange={e => setBackupPassphrase(e.target.value)}
                    className="flex-1"
                  />
                  <Button
                    variant="default"
                    disabled={isBackingUp || backupPassphrase.length < 8}
                    onClick={async () => {
                      setIsBackingUp(true);
                      try {
                        await backupKeys(backupPassphrase);
                        toast({ title: 'Backup Complete', description: 'Your encrypted keys have been securely uploaded.' });
                        setBackupPassphrase('');
                      } catch (e: any) {
                        toast({ title: 'Backup Failed', description: e.message || 'Could not back up keys.', variant: 'destructive' });
                      } finally {
                        setIsBackingUp(false);
                      }
                    }}
                  >
                    {isBackingUp ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <ShieldCheck className="mr-2 h-4 w-4" />}
                    Backup
                  </Button>
                </div>
                {backupPassphrase.length > 0 && backupPassphrase.length < 8 && (
                  <p className="text-xs text-red-500">Passphrase must be at least 8 characters.</p>
                )}
              </div>

              {/* --- Key Restore --- */}
              <div className="space-y-3 p-4 bg-green-50 dark:bg-green-900/10 rounded-lg border border-green-100 dark:border-green-900/30">
                <h3 className="text-sm font-semibold text-green-700 dark:text-green-300 flex items-center gap-2">
                  <Key className="w-4 h-4" /> Restore Keys
                </h3>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  If you're on a new device, enter the passphrase you used during backup to recover your encryption keys and chat history.
                </p>
                <div className="flex gap-2">
                  <Input
                    type="password"
                    placeholder="Enter your backup passphrase"
                    value={restorePassphrase}
                    onChange={e => setRestorePassphrase(e.target.value)}
                    className="flex-1"
                  />
                  <Button
                    variant="default"
                    disabled={isRestoring || restorePassphrase.length < 8}
                    onClick={async () => {
                      setIsRestoring(true);
                      try {
                        await restoreKeys(restorePassphrase);
                        toast({ title: 'Keys Restored', description: 'Your encryption keys have been recovered. Chat history is now accessible.' });
                        setRestorePassphrase('');
                      } catch (e: any) {
                        toast({ title: 'Restore Failed', description: e.message || 'Incorrect passphrase or no backup found.', variant: 'destructive' });
                      } finally {
                        setIsRestoring(false);
                      }
                    }}
                  >
                    {isRestoring ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Key className="mr-2 h-4 w-4" />}
                    Restore
                  </Button>
                </div>
              </div>

              {/* --- Danger Zone: Regenerate --- */}
              <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                <p className="text-xs text-gray-500 dark:text-gray-400 mb-3">
                  <AlertTriangle className="w-3 h-3 inline mr-1 text-red-500" />
                  Regenerating keys will make all previous encrypted messages permanently unreadable on this device.
                </p>
                <Button variant="destructive" onClick={handleRegenerateKeys} disabled={isRegenerating}>
                  {isRegenerating ? <RefreshCw className="mr-2 h-4 w-4 animate-spin" /> : <Key className="mr-2 h-4 w-4" />}
                  Regenerate Keys
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-zinc-900 border-zinc-800 text-white overflow-hidden relative">
            <div className="absolute top-0 right-0 p-4 opacity-10">
                <Zap className="w-24 h-24 text-amber-500" />
            </div>
            <CardHeader>
                <CardTitle className="text-amber-500 flex items-center gap-2 italic uppercase tracking-tighter">
                    <Zap className="w-5 h-5 fill-current" />
                    Cryptographic Performance
                </CardTitle>
                <CardDescription className="text-zinc-400">
                    Benchmark the Rust (WASM) vs. JavaScript (WebCrypto) encryption engines.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {benchmark ? (
                    <div className="grid grid-cols-2 gap-4">
                        <div className="bg-black/40 p-3 rounded-xl border border-white/5">
                            <span className="text-[10px] font-black uppercase text-zinc-500 block mb-1">WASM (Rust)</span>
                            <span className="text-lg font-bold">{benchmark.wasmTime > 0 ? `${benchmark.wasmTime.toFixed(2)}ms` : 'Unavailable'}</span>
                        </div>
                        <div className="bg-black/40 p-3 rounded-xl border border-white/5">
                            <span className="text-[10px] font-black uppercase text-zinc-500 block mb-1">JS (WebCrypto)</span>
                            <span className="text-lg font-bold">{benchmark.jsTime.toFixed(2)}ms</span>
                        </div>
                    </div>
                ) : (
                    <p className="text-zinc-500 text-sm italic">Run benchmark to verify hardware acceleration.</p>
                )}
            </CardContent>
            <CardFooter>
                <Button variant="outline" className="w-full border-zinc-700 text-white" onClick={handleRunBenchmark} disabled={isBenchmarking}>
                    {isBenchmarking ? <Loader2 className="animate-spin mr-2" /> : <RefreshCw className="mr-2 h-4 w-4" />}
                    Test Hardware Acceleration
                </Button>
            </CardFooter>
          </Card>

          <Card className="border-red-200 dark:border-red-900/50">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-600 dark:text-red-400">
                <UserX className="w-5 h-5 text-red-500" />
                Emergency Decoy Profile
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Input type="password" value={decoyPassword} onChange={e => setDecoyPassword(e.target.value)} placeholder="Decoy Password" />
            </CardContent>
            <CardFooter className="flex gap-2">
              <Button variant="default" onClick={handleCreateDecoy} disabled={isDecoyLoading || !decoyPassword}>Set Decoy</Button>
              <Button variant="outline" onClick={handleRemoveDecoy} disabled={isDecoyLoading}>Remove Decoy</Button>
            </CardFooter>
          </Card>
        </div>
      </main>
    </div>
  );
}
