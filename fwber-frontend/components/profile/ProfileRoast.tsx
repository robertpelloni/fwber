import React from 'react';
import { useAiWingman } from '@/lib/hooks/use-ai-wingman';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Loader2, Flame, Share2, Copy, RefreshCw } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

export function ProfileRoast() {
  const { roastProfile } = useAiWingman();
  const { toast } = useToast();
  const [roast, setRoast] = React.useState<string | null>(null);

  const handleRoast = async () => {
    try {
      const result = await roastProfile.mutateAsync();
      setRoast(result.roast);
    } catch (error) {
      // Error is handled by the hook
    }
  };

  const handleCopy = () => {
    if (roast) {
      navigator.clipboard.writeText(roast);
      toast({
        title: "Copied to clipboard",
        description: "Share this roast with your friends!",
      });
    }
  };

  const handleShare = async () => {
    if (roast && navigator.share) {
      try {
        await navigator.share({
          title: 'My FWBer Profile Roast',
          text: roast,
          url: window.location.origin,
        });
      } catch (err) {
        console.error('Error sharing:', err);
      }
    } else {
      handleCopy();
    }
  };

  if (!roast && !roastProfile.isPending) {
    return (
      <Card className="bg-gradient-to-br from-orange-900/50 to-red-900/50 border-orange-500/30">
        <CardContent className="p-6 flex flex-col items-center text-center space-y-4">
          <div className="p-3 bg-orange-500/20 rounded-full animate-pulse">
            <Flame className="w-8 h-8 text-orange-400" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-white">Roast My Profile</h3>
            <p className="text-sm text-gray-400 mt-1">
              Brave enough? Let our AI Roast Master humble you. Perfect for sharing with friends!
            </p>
          </div>
          <Button 
            onClick={handleRoast} 
            className="bg-orange-600 hover:bg-orange-700 text-white font-bold"
          >
            <Flame className="w-4 h-4 mr-2" />
            Roast Me!
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (roastProfile.isPending) {
    return (
      <Card className="bg-gray-900 border-gray-800">
        <CardContent className="p-8 flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-8 h-8 text-orange-500 animate-spin" />
          <p className="text-gray-400 animate-pulse">Preparing the burn...</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-gray-900 border-orange-500/50 overflow-hidden shadow-[0_0_15px_rgba(249,115,22,0.3)]">
      <CardHeader className="bg-orange-900/20 border-b border-orange-900/30 pb-4">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg font-semibold text-white flex items-center gap-2">
            <Flame className="w-5 h-5 text-orange-500" />
            Your Roast
          </CardTitle>
          <div className="flex gap-2">
            <Button variant="ghost" size="icon" onClick={handleCopy} title="Copy">
              <Copy className="w-4 h-4 text-gray-400 hover:text-white" />
            </Button>
            <Button variant="ghost" size="icon" onClick={handleShare} title="Share">
              <Share2 className="w-4 h-4 text-gray-400 hover:text-white" />
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent className="p-6 space-y-6">
        <div className="bg-black/40 rounded-lg p-4 border border-orange-500/20">
          <p className="text-gray-200 italic leading-relaxed font-medium">
            &quot;{roast}&quot;
          </p>
        </div>
        
        <div className="flex justify-center pt-2">
            <Button 
                variant="ghost" 
                size="sm" 
                onClick={handleRoast}
                className="text-orange-500 hover:text-orange-400 hover:bg-orange-900/20"
            >
                <RefreshCw className="w-4 h-4 mr-2" />
                Roast Me Again
            </Button>
        </div>
      </CardContent>
    </Card>
  );
}
