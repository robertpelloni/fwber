import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button, ButtonProps } from '@/components/ui/button';
import { useToast } from '@/lib/hooks/use-toast';
import { useAuth } from '@/lib/auth-context';
import { wingmanApi } from '@/lib/api/wingman';
import { Flame, Share2, Sparkles, Loader2, Twitter, MessageCircle } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

interface RoastGeneratorProps {
  trigger?: React.ReactNode;
}

export function RoastGenerator({ trigger }: RoastGeneratorProps) {
  const { user } = useAuth();
  const { success, error } = useToast();
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<string | null>(null);
  const [mode, setMode] = useState<'roast' | 'hype'>('roast');
  const [shareId, setShareId] = useState<string | null>(null);

  const generateContent = async () => {
    setLoading(true);
    setResult(null);
    setShareId(null);
    try {
      const response = await wingmanApi.roastProfile({ mode });
      setResult(response.roast);
      setShareId(response.share_id);
    } catch (err: any) {
      error(err.message || "Something went wrong. Try again.");
    } finally {
      setLoading(false);
    }
  };

  const shareUrl = shareId 
    ? `${typeof window !== 'undefined' ? window.location.origin : ''}/viral/${shareId}`
    : '';

  const copyToClipboard = () => {
    if (shareUrl) {
      navigator.clipboard.writeText(shareUrl);
      success("Link copied to clipboard.");
    }
  };

  const handleShare = async () => {
    if (typeof navigator !== 'undefined' && navigator.share && shareUrl) {
      try {
        await navigator.share({
          title: mode === 'roast' ? 'My fwber Roast' : 'My fwber Hype',
          text: `Check out this AI ${mode} of my profile!`,
          url: shareUrl,
        });
      } catch (err) {
        console.error('Error sharing:', err);
      }
    } else {
      copyToClipboard();
    }
  };

  const reset = () => {
    setResult(null);
    setShareId(null);
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open: boolean) => { setIsOpen(open); if (!open) reset(); }}>
      <DialogTrigger asChild>
        {trigger || (
          <Button variant="outline" className="gap-2">
            <Flame className="w-4 h-4 text-orange-500" />
            Roast My Profile
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-xl">
            {mode === 'roast' ? <Flame className="w-6 h-6 text-orange-500" /> : <Sparkles className="w-6 h-6 text-yellow-500" />}
            {mode === 'roast' ? 'AI Roast' : 'AI Hype'}
          </DialogTitle>
        </DialogHeader>

        {!result ? (
          <Tabs defaultValue="roast" onValueChange={(val: string) => setMode(val as 'roast' | 'hype')} className="w-full">
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="roast">🔥 Roast Me</TabsTrigger>
              <TabsTrigger value="hype">✨ Hype Me</TabsTrigger>
            </TabsList>
            <div className="p-6 text-center space-y-4">
              <div className="text-6xl animate-bounce">
                {mode === 'roast' ? '😈' : '😎'}
              </div>
              <p className="text-muted-foreground">
                {mode === 'roast' 
                  ? "Ready to get humbled? Our AI will analyze your profile and roast you explicitly." 
                  : "Need an ego boost? Let our AI tell you why you&apos;re a catch."}
              </p>
              <Button 
                size="lg" 
                onClick={generateContent} 
                disabled={loading}
                className={mode === 'roast' ? 'bg-orange-600 hover:bg-orange-700' : 'bg-yellow-600 hover:bg-yellow-700'}
              >
                {loading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : null}
                {loading ? 'Analyzing...' : (mode === 'roast' ? 'Roast Me!' : 'Hype Me Up!')}
              </Button>
            </div>
          </Tabs>
        ) : (
          <div className="space-y-6 animate-in fade-in zoom-in duration-300">
            <div className={`p-6 rounded-xl border-2 ${mode === 'roast' ? 'bg-orange-50 border-orange-200 dark:bg-orange-950/20 dark:border-orange-900' : 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950/20 dark:border-yellow-900'}`}>
              <div className="text-4xl mb-4 text-center">{mode === 'roast' ? '💀' : '🤩'}</div>
              <p className="text-lg font-medium text-center italic leading-relaxed">
                &quot;{result}&quot;
              </p>
            </div>

            <div className="space-y-3">
              <p className="text-sm text-center text-muted-foreground font-medium uppercase tracking-wide">
                Share this {mode}
              </p>
              
              <div className="flex gap-2 justify-center">
                <Button variant="outline" size="sm" onClick={() => window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(`AI just ${mode}ed me on fwber: "${result}"\n\nSee yours here:`)}&url=${encodeURIComponent(shareUrl)}`, '_blank')}>
                  <Twitter className="w-4 h-4 mr-2" />
                  Tweet
                </Button>
                <Button variant="outline" size="sm" onClick={() => window.open(`https://wa.me/?text=${encodeURIComponent(`Check out this AI ${mode}: "${result}" ${shareUrl}`)}`, '_blank')}>
                  <MessageCircle className="w-4 h-4 mr-2" />
                  WhatsApp
                </Button>
                <Button variant="default" size="sm" onClick={handleShare}>
                  <Share2 className="w-4 h-4 mr-2" />
                  Share Link
                </Button>
              </div>

              <div className="pt-4 text-center">
                <Button variant="ghost" size="sm" onClick={reset} className="text-muted-foreground hover:text-foreground">
                  Try Again
                </Button>
              </div>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
