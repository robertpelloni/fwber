import React, { useState } from 'react';
import { useAiWingman } from '@/lib/hooks/use-ai-wingman';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Loader2, Flame, Sparkles, Flag, Star, Skull, Share2, Copy, CheckCircle2 } from 'lucide-react';
import { useToast } from '@/lib/hooks/use-toast';

export function ViralContentGenerator() {
  const { roastProfile, checkVibe, predictFortune, findNemesis, error } = useAiWingman();
  const { success } = useToast();
  const [shareUrl, setShareUrl] = useState<string | null>(null);

  const handleGenerate = (type: 'roast' | 'hype' | 'vibe' | 'fortune' | 'nemesis') => {
    setShareUrl(null);
    if (type === 'roast' || type === 'hype') {
      roastProfile.mutate(type, {
        onSuccess: (data) => setShareUrl(`${window.location.origin}/share/${data.share_id}`)
      });
    } else if (type === 'vibe') {
      checkVibe.mutate(undefined, {
        onSuccess: (data) => setShareUrl(`${window.location.origin}/share/${data.share_id}`)
      });
    } else if (type === 'fortune') {
      predictFortune.mutate(undefined, {
        onSuccess: (data) => setShareUrl(`${window.location.origin}/share/${data.share_id}`)
      });
    } else if (type === 'nemesis') {
      findNemesis.mutate(undefined, {
        onSuccess: (data) => setShareUrl(`${window.location.origin}/share/${data.share_id}`)
      });
    }
  };

  const copyToClipboard = () => {
    if (shareUrl) {
      navigator.clipboard.writeText(shareUrl);
      success('Link copied to clipboard!');
    }
  };

  const isLoading = roastProfile.isPending || checkVibe.isPending || predictFortune.isPending || findNemesis.isPending;

  return (
    <Card className="w-full">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Sparkles className="w-5 h-5 text-purple-600" />
          Viral AI Features
        </CardTitle>
        <CardDescription>
          Generate funny, shareable insights about your profile. Share them to earn rewards!
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Tabs defaultValue="roast" className="w-full">
          <TabsList className="grid w-full grid-cols-5 mb-4">
            <TabsTrigger value="roast" title="Roast Me"><Flame className="w-4 h-4" /></TabsTrigger>
            <TabsTrigger value="hype" title="Hype Me"><Sparkles className="w-4 h-4" /></TabsTrigger>
            <TabsTrigger value="vibe" title="Vibe Check"><Flag className="w-4 h-4" /></TabsTrigger>
            <TabsTrigger value="fortune" title="Fortune"><Star className="w-4 h-4" /></TabsTrigger>
            <TabsTrigger value="nemesis" title="Nemesis"><Skull className="w-4 h-4" /></TabsTrigger>
          </TabsList>

          <TabsContent value="roast" className="space-y-4">
            <div className="text-center">
              <h3 className="font-bold text-lg mb-2">Roast My Profile 🔥</h3>
              <p className="text-sm text-gray-500 mb-4">Ready to get humbled? AI will brutally roast your profile.</p>
              {!roastProfile.data ? (
                <Button onClick={() => handleGenerate('roast')} disabled={isLoading} className="bg-orange-600 hover:bg-orange-700 text-white">
                  {isLoading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Flame className="w-4 h-4 mr-2" />}
                  Roast Me
                </Button>
              ) : (
                <div className="bg-orange-50 border border-orange-200 p-4 rounded-lg italic text-gray-700">
                  &quot;{roastProfile.data.roast}&quot;
                </div>
              )}
            </div>
          </TabsContent>

          <TabsContent value="hype" className="space-y-4">
            <div className="text-center">
              <h3 className="font-bold text-lg mb-2">Hype Me Up ✨</h3>
              <p className="text-sm text-gray-500 mb-4">Need an ego boost? AI will tell you why you're amazing.</p>
              {!roastProfile.data ? ( // Reuse roast mutation for hype as logic is same endpoint
                <Button onClick={() => handleGenerate('hype')} disabled={isLoading} className="bg-blue-600 hover:bg-blue-700 text-white">
                  {isLoading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Sparkles className="w-4 h-4 mr-2" />}
                  Hype Me
                </Button>
              ) : (
                <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg italic text-gray-700">
                  &quot;{roastProfile.data.roast}&quot;
                </div>
              )}
            </div>
          </TabsContent>

          <TabsContent value="vibe" className="space-y-4">
             <div className="text-center">
                <h3 className="font-bold text-lg mb-2">Vibe Check 🚩</h3>
                <p className="text-sm text-gray-500 mb-4">What are your green and red flags?</p>
                {!checkVibe.data ? (
                    <Button onClick={() => handleGenerate('vibe')} disabled={isLoading} className="bg-purple-600 hover:bg-purple-700 text-white">
                        {isLoading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Flag className="w-4 h-4 mr-2" />}
                        Check Vibe
                    </Button>
                ) : (
                    <div className="grid grid-cols-2 gap-4 text-left">
                        <div className="bg-green-50 p-3 rounded border border-green-200">
                            <h4 className="font-bold text-green-700 mb-1">Green Flags</h4>
                            <ul className="text-sm text-green-800 list-disc list-inside">
                                {checkVibe.data.green_flags.map((f, i) => <li key={i}>{f}</li>)}
                            </ul>
                        </div>
                        <div className="bg-red-50 p-3 rounded border border-red-200">
                            <h4 className="font-bold text-red-700 mb-1">Red Flags</h4>
                            <ul className="text-sm text-red-800 list-disc list-inside">
                                {checkVibe.data.red_flags.map((f, i) => <li key={i}>{f}</li>)}
                            </ul>
                        </div>
                    </div>
                )}
             </div>
          </TabsContent>

          <TabsContent value="fortune" className="space-y-4">
              <div className="text-center">
                  <h3 className="font-bold text-lg mb-2">Dating Fortune 🔮</h3>
                  <p className="text-sm text-gray-500 mb-4">What does the universe have in store for your love life?</p>
                  {!predictFortune.data ? (
                    <Button onClick={() => handleGenerate('fortune')} disabled={isLoading} className="bg-yellow-600 hover:bg-yellow-700 text-white">
                         {isLoading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Star className="w-4 h-4 mr-2" />}
                         Predict Fortune
                    </Button>
                  ) : (
                    <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg italic text-gray-700">
                      &quot;{predictFortune.data.fortune}&quot;
                    </div>
                  )}
              </div>
          </TabsContent>

          <TabsContent value="nemesis" className="space-y-4">
              <div className="text-center">
                  <h3 className="font-bold text-lg mb-2">Scientific Nemesis 🧬</h3>
                  <p className="text-sm text-gray-500 mb-4">Who should you absolutely avoid?</p>
                  {!findNemesis.data ? (
                    <Button onClick={() => handleGenerate('nemesis')} disabled={isLoading} className="bg-red-600 hover:bg-red-700 text-white">
                         {isLoading ? <Loader2 className="w-4 h-4 animate-spin mr-2" /> : <Skull className="w-4 h-4 mr-2" />}
                         Find Nemesis
                    </Button>
                  ) : (
                    <div className="bg-red-50 border border-red-200 p-4 rounded-lg text-left">
                        <p className="font-bold text-red-800">Type: {findNemesis.data.nemesis_type}</p>
                        <p className="text-sm text-red-700 mt-1">{findNemesis.data.why_it_would_fail}</p>
                    </div>
                  )}
              </div>
          </TabsContent>

          {shareUrl && (
            <div className="mt-6 pt-6 border-t animate-in slide-in-from-bottom-2 fade-in">
              <div className="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-lg border border-purple-100">
                <h4 className="font-bold text-purple-900 mb-2 flex items-center gap-2">
                  <Share2 className="w-4 h-4" /> Share & Earn Rewards
                </h4>
                <p className="text-sm text-purple-700 mb-3">
                  Share this result! If 5 people view it, you get <strong>24 hours of Gold Premium</strong>.
                </p>
                <div className="flex gap-2">
                  <Button variant="outline" className="flex-1 bg-white" onClick={copyToClipboard}>
                    <Copy className="w-4 h-4 mr-2" /> Copy Link
                  </Button>
                  <Button className="flex-1 bg-black text-white hover:bg-gray-800" onClick={() => window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent("Check out my FWBer AI Analysis! 😂")} &url=${encodeURIComponent(shareUrl)}`, '_blank')}>
                    Post to X
                  </Button>
                </div>
              </div>
            </div>
          )}
        </Tabs>
      </CardContent>
    </Card>
  );
}
