import React from 'react';
import { useAiWingman, VibeCheckResponse } from '@/lib/hooks/use-ai-wingman';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Loader2, Flag, Share2, Copy, RefreshCw, CheckCircle2, AlertTriangle } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';

export function VibeCheck() {
  const { checkVibe } = useAiWingman();
  const { toast } = useToast();
  const [result, setResult] = React.useState<VibeCheckResponse | null>(null);

  const handleGenerate = async () => {
    try {
      const data = await checkVibe.mutateAsync();
      setResult(data);
    } catch (error) {
      // Error is handled by the hook
    }
  };

  const handleCopy = () => {
    if (result) {
      const text = `My Vibe Check:\n\n🟢 Green Flags:\n${result.green_flags.map(f => `- ${f}`).join('\n')}\n\n🚩 Red Flags:\n${result.red_flags.map(f => `- ${f}`).join('\n')}`;
      navigator.clipboard.writeText(text);
      toast({
        title: "Copied to clipboard",
        description: "Share your vibe check with friends!",
      });
    }
  };

  const handleShare = async () => {
    if (result && navigator.share) {
      const text = `My Vibe Check:\n\n🟢 Green Flags:\n${result.green_flags.map(f => `- ${f}`).join('\n')}\n\n🚩 Red Flags:\n${result.red_flags.map(f => `- ${f}`).join('\n')}`;
      try {
        const url = result.share_id 
          ? `${window.location.origin}/share/${result.share_id}`
          : window.location.origin;

        await navigator.share({
          title: 'My FWBer Vibe Check',
          text: text,
          url: url,
        });
      } catch (err) {
        console.error('Error sharing:', err);
      }
    } else {
      handleCopy();
    }
  };

  if (!result && !checkVibe.isPending) {
    return (
      <Card className="bg-gradient-to-br from-purple-900/50 to-pink-900/50 border-purple-500/30">
        <CardContent className="p-6 flex flex-col items-center text-center space-y-4">
          <div className="p-3 rounded-full bg-purple-500/20 animate-pulse">
            <Flag className="w-8 h-8 text-purple-400" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-white">Vibe Check</h3>
            <p className="text-sm text-gray-400 mt-1">
              Are you a walking green flag or a red flag factory? Let AI decide.
            </p>
          </div>
          <Button 
            onClick={handleGenerate} 
            className="bg-purple-600 hover:bg-purple-700 text-white font-bold w-full"
          >
            <Flag className="w-4 h-4 mr-2" />
            Check My Vibe
          </Button>
        </CardContent>
      </Card>
    );
  }

  if (checkVibe.isPending) {
    return (
      <Card className="bg-gray-900 border-gray-800">
        <CardContent className="p-8 flex flex-col items-center justify-center space-y-4">
          <Loader2 className="w-8 h-8 animate-spin text-purple-500" />
          <p className="text-gray-400 animate-pulse">
            Analyzing your vibes...
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-gray-900 overflow-hidden shadow-[0_0_15px_rgba(0,0,0,0.3)] border-purple-500/50 shadow-purple-500/20">
      <CardHeader className="bg-purple-900/20 border-purple-900/30 border-b pb-4">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg font-semibold text-white flex items-center gap-2">
            <Flag className="w-5 h-5 text-purple-500" />
            Vibe Check Result
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
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Green Flags */}
            <div className="space-y-3">
                <h4 className="text-green-400 font-medium flex items-center gap-2">
                    <CheckCircle2 className="w-4 h-4" /> Green Flags
                </h4>
                <ul className="space-y-2">
                    {result?.green_flags.map((flag, i) => (
                        <li key={i} className="bg-green-900/20 border border-green-500/20 rounded px-3 py-2 text-sm text-green-100">
                            {flag}
                        </li>
                    ))}
                </ul>
            </div>

            {/* Red Flags */}
            <div className="space-y-3">
                <h4 className="text-red-400 font-medium flex items-center gap-2">
                    <AlertTriangle className="w-4 h-4" /> Red Flags
                </h4>
                <ul className="space-y-2">
                    {result?.red_flags.map((flag, i) => (
                        <li key={i} className="bg-red-900/20 border border-red-500/20 rounded px-3 py-2 text-sm text-red-100">
                            {flag}
                        </li>
                    ))}
                </ul>
            </div>
        </div>
        
        <div className="flex justify-center pt-2">
            <Button 
                variant="ghost" 
                size="sm" 
                onClick={handleGenerate}
                className="text-purple-500 hover:text-purple-400 hover:bg-purple-900/20"
            >
                <RefreshCw className="w-4 h-4 mr-2" />
                Check Again
            </Button>
        </div>
      </CardContent>
    </Card>
  );
}
