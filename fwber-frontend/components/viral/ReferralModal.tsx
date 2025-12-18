import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAuth } from '@/lib/auth-context';
import { useToast } from '@/lib/hooks/use-toast';
import { Gift, Copy, Ticket, Users, Twitter, Mail, MessageCircle, Share2, Shield, Flame, PartyPopper } from 'lucide-react';

interface ReferralModalProps {
  trigger?: React.ReactNode;
}

export function ReferralModal({ trigger }: ReferralModalProps) {
  const { user } = useAuth();
  const { success } = useToast();
  const [isOpen, setIsOpen] = useState(false);

  if (!user) return null;

  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://fwber.me';
  const referralLink = `${origin}/register?ref=${user.referral_code}`;
  const vouchLink = `${origin}/vouch/${user.referral_code}`;

  const shareText = "🔥 Join me on FWBer! Use my link to get 50 Tokens and 3 Days of Gold Premium instantly.";
  const vouchText = "Can you vouch for me? I'm building my reputation on FWBer. Click to verify I'm legit!";

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    success('Link copied to clipboard.');
  };

  const handleNativeShare = async (title: string, text: string, url: string) => {
    if (typeof navigator !== 'undefined' && navigator.share) {
      try {
        await navigator.share({ title, text, url });
      } catch (err) {
        console.error('Error sharing:', err);
      }
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        {trigger || (
          <Button variant="outline" className="gap-2">
            <Gift className="w-4 h-4" />
            Invite & Earn
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-xl">
            <Gift className="w-6 h-6 text-primary" />
            Viral Rewards
          </DialogTitle>
          <DialogDescription>
            Grow your network and earn tokens by inviting friends.
          </DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="invite" className="w-full">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="invite">Invite Friends</TabsTrigger>
            <TabsTrigger value="vouch">Get Vouched</TabsTrigger>
          </TabsList>

          <TabsContent value="invite" className="space-y-4 py-4">
             {/* Golden Ticket Section */}
            <div className="bg-gradient-to-r from-amber-100 to-yellow-100 dark:from-amber-900/20 dark:to-yellow-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                <div className="flex items-center gap-3 mb-2">
                <div className="p-2 bg-amber-500 rounded-full text-white">
                    <Ticket className="w-5 h-5" />
                </div>
                <div>
                    <h4 className="font-bold text-amber-900 dark:text-amber-100">Golden Tickets</h4>
                    <p className="text-sm text-amber-700 dark:text-amber-300">
                    <span className="font-bold">{user.golden_tickets_remaining || 0}</span> tickets remaining.
                    </p>
                </div>
                </div>
                <p className="text-xs text-amber-800 dark:text-amber-200 mt-2">
                Friends get <strong>3 Days of Gold Premium</strong> instantly!
                </p>
            </div>

            <div className="space-y-2">
                <label className="text-sm font-medium">Your Invite Link</label>
                <div className="flex items-center gap-2">
                <Input readOnly value={referralLink} className="font-mono text-sm bg-muted" />
                <Button size="icon" onClick={() => copyToClipboard(referralLink)}>
                    <Copy className="w-4 h-4" />
                </Button>
                </div>
            </div>

            <div className="flex gap-2 justify-center">
                 <Button variant="outline" size="sm" className="gap-2" onClick={() => window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(referralLink)}`, '_blank')}>
                    <Twitter className="w-4 h-4" /> Post
                 </Button>
                 <Button variant="outline" size="sm" className="gap-2" onClick={() => window.open(`https://wa.me/?text=${encodeURIComponent(shareText + ' ' + referralLink)}`, '_blank')}>
                    <MessageCircle className="w-4 h-4" /> Chat
                 </Button>
                 <Button variant="outline" size="sm" className="gap-2" onClick={() => handleNativeShare('Join FWBer', shareText, referralLink)}>
                    <Share2 className="w-4 h-4" /> Share
                 </Button>
            </div>
          </TabsContent>

          <TabsContent value="vouch" className="space-y-4 py-4">
             <div className="bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <div className="flex items-center gap-3 mb-2">
                    <Shield className="w-8 h-8 text-blue-600" />
                    <div>
                        <h4 className="font-bold text-blue-900 dark:text-blue-100">Build Your Reputation</h4>
                        <p className="text-sm text-blue-800 dark:text-blue-200">
                            Ask friends to vouch for you as <strong>Safe</strong>, <strong>Fun</strong>, or <strong>Hot</strong>.
                        </p>
                    </div>
                </div>
                <div className="flex gap-2 mt-3 justify-center">
                    <span className="flex items-center text-xs bg-white/50 px-2 py-1 rounded-full text-green-700 font-bold"><Shield className="w-3 h-3 mr-1"/> Safe</span>
                    <span className="flex items-center text-xs bg-white/50 px-2 py-1 rounded-full text-purple-700 font-bold"><PartyPopper className="w-3 h-3 mr-1"/> Fun</span>
                    <span className="flex items-center text-xs bg-white/50 px-2 py-1 rounded-full text-orange-700 font-bold"><Flame className="w-3 h-3 mr-1"/> Hot</span>
                </div>
            </div>

            <div className="space-y-2">
                <label className="text-sm font-medium">Your Vouch Link</label>
                <div className="flex items-center gap-2">
                <Input readOnly value={vouchLink} className="font-mono text-sm bg-muted" />
                <Button size="icon" onClick={() => copyToClipboard(vouchLink)}>
                    <Copy className="w-4 h-4" />
                </Button>
                </div>
            </div>

            <div className="flex gap-2 justify-center">
                 <Button variant="outline" size="sm" className="gap-2" onClick={() => window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(vouchText)}&url=${encodeURIComponent(vouchLink)}`, '_blank')}>
                    <Twitter className="w-4 h-4" /> Ask
                 </Button>
                 <Button variant="outline" size="sm" className="gap-2" onClick={() => window.open(`mailto:?subject=Vouch for me?&body=${encodeURIComponent(vouchText + '\n\n' + vouchLink)}`, '_blank')}>
                    <Mail className="w-4 h-4" /> Email
                 </Button>
                 <Button variant="outline" size="sm" className="gap-2" onClick={() => handleNativeShare('Vouch for Me', vouchText, vouchLink)}>
                    <Share2 className="w-4 h-4" /> Share
                 </Button>
            </div>
          </TabsContent>
        </Tabs>

        {/* Stats Footer */}
        <div className="border-t pt-4 grid grid-cols-2 gap-4">
            <div className="bg-muted p-2 rounded text-center">
              <div className="text-xl font-bold text-primary">{user.token_balance || 0}</div>
              <div className="text-[10px] uppercase tracking-wider text-muted-foreground">Tokens</div>
            </div>
            <div className="bg-muted p-2 rounded text-center">
              <div className="text-xl font-bold text-primary flex items-center justify-center gap-1">
                {user.referrals_count || 0}
                <Users className="w-4 h-4" />
              </div>
              <div className="text-[10px] uppercase tracking-wider text-muted-foreground">Invites</div>
            </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
