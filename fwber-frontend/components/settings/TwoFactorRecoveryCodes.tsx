'use client';

import { useState } from 'react';
import { apiClient } from '@/lib/api/client';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AlertTriangle, Copy, RefreshCw, Eye, EyeOff } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

export default function TwoFactorRecoveryCodes() {
  const [codes, setCodes] = useState<string[]>([]);
  const [isVisible, setIsVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const { toast } = useToast();

  const fetchCodes = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get('/user/two-factor-recovery-codes');
      setCodes(response.data);
      setIsVisible(true);
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to load recovery codes.",
        variant: "destructive",
      });
    } finally {
      setIsLoading(false);
    }
  };

  const regenerateCodes = async () => {
    if (!confirm('Generating new codes will invalidate your existing ones. Continue?')) return;

    setIsLoading(true);
    try {
      const response = await apiClient.post('/user/two-factor-recovery-codes');
      setCodes(response.data);
      setIsVisible(true);
      toast({
        title: "Success",
        description: "New recovery codes generated.",
      });
    } catch (error) {
      toast({
        title: "Error",
        description: "Failed to regenerate codes.",
        variant: "destructive",
      });
    } finally {
      setIsLoading(false);
    }
  };

  const copyToClipboard = () => {
    navigator.clipboard.writeText(codes.join('\n'));
    toast({ description: "Codes copied to clipboard" });
  };

  return (
    <Card className="border-amber-200 dark:border-amber-800">
      <CardHeader>
        <CardTitle className="flex items-center gap-2 text-amber-700 dark:text-amber-400">
          <AlertTriangle className="w-5 h-5" />
          Recovery Codes
        </CardTitle>
        <CardDescription>
          If you lose access to your 2FA device, you can use these codes to log in. Keep them safe.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {!isVisible ? (
          <Button onClick={fetchCodes} disabled={isLoading} variant="outline">
            {isLoading ? 'Loading...' : 'Show Recovery Codes'} <Eye className="ml-2 w-4 h-4" />
          </Button>
        ) : (
          <div className="space-y-4 animate-in fade-in slide-in-from-top-2">
            <div className="bg-gray-100 dark:bg-gray-900 p-4 rounded-lg font-mono text-sm grid grid-cols-2 gap-2 text-center">
              {codes.map((code, i) => (
                <span key={i} className="bg-white dark:bg-gray-800 p-1 rounded border dark:border-gray-700 select-all">
                  {code}
                </span>
              ))}
            </div>

            <div className="flex flex-wrap gap-2">
              <Button onClick={copyToClipboard} variant="secondary" size="sm">
                <Copy className="mr-2 w-4 h-4" /> Copy All
              </Button>
              <Button onClick={regenerateCodes} variant="destructive" size="sm" disabled={isLoading}>
                <RefreshCw className={`mr-2 w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
                Regenerate
              </Button>
              <Button onClick={() => setIsVisible(false)} variant="ghost" size="sm">
                <EyeOff className="mr-2 w-4 h-4" /> Hide
              </Button>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
