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
import { useAuth } from '@/lib/auth-context';
import { useToast } from '@/lib/hooks/use-toast';
import { Gift, Copy, Ticket, Users, Twitter, Mail, MessageCircle, Share2 } from 'lucide-react';

interface ReferralModalProps {
  trigger?: React.ReactNode;
}

export function ReferralModal({ trigger }: ReferralModalProps) {
  const { user } = useAuth();
  const { success } = useToast();
  const [isOpen, setIsOpen] = useState(false);

  if (!user) return null;

  const referralLink = typeof window !== 'undefined' 
    ? `${window.location.origin}/register?ref=${user.referral_code}`
    : `https://fwber.me/register?ref=${user.referral_code}`;

  const shareText = "Join me on FWBer - The Definitive Social Network for Adults! Get 3 days of Gold Premium with my link:";
  const encodedText = encodeURIComponent(shareText);
  const encodedLink = encodeURIComponent(referralLink);

  const shareLinks = [
    { 
      name: 'X / Twitter', 
      icon: Twitter, 
      url: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedLink}`,
      color: 'hover:text-blue-400'
    },
    { 
      name: 'WhatsApp', 
      icon: MessageCircle, 
      url: `https://wa.me/?text=${encodedText}%20${encodedLink}`,
      color: 'hover:text-green-500'
    },
    { 
      name: 'Email', 
      icon: Mail, 
      url: `mailto:?subject=Join me on FWBer&body=${shareText}%0A%0A${referralLink}`,
      color: 'hover:text-red-500'
    },
  ];

  const copyToClipboard = () => {
    navigator.clipboard.writeText(referralLink);
    success('Referral link copied to clipboard.');
  };

  const handleNativeShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: 'Join me on FWBer',
          text: shareText,
          url: referralLink,
        });
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
            Invite Friends
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-xl">
            <Gift className="w-6 h-6 text-primary" />
            Invite Friends & Earn Rewards
          </DialogTitle>
          <DialogDescription>
            Share the love and get rewarded! Earn 50 Tokens for every friend who joins.
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-6 py-4">
          {/* Golden Ticket Section */}
          <div className="bg-gradient-to-r from-amber-100 to-yellow-100 dark:from-amber-900/20 dark:to-yellow-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
            <div className="flex items-center gap-3 mb-2">
              <div className="p-2 bg-amber-500 rounded-full text-white">
                <Ticket className="w-5 h-5" />
              </div>
              <div>
                <h4 className="font-bold text-amber-900 dark:text-amber-100">Golden Tickets</h4>
                <p className="text-sm text-amber-700 dark:text-amber-300">
                  You have <span className="font-bold">{user.golden_tickets_remaining || 0}</span> tickets left.
                </p>
              </div>
            </div>
            <p className="text-xs text-amber-800 dark:text-amber-200 mt-2">
              Friends who sign up with your link get <strong>3 Days of Gold Premium</strong> instantly!
            </p>
          </div>

          {/* Share Buttons */}
          <div className="flex justify-center gap-4">
            {shareLinks.map((link) => (
              <a
                key={link.name}
                href={link.url}
                target="_blank"
                rel="noopener noreferrer"
                className={`p-3 rounded-full bg-muted hover:bg-muted/80 transition-colors ${link.color}`}
                title={`Share on ${link.name}`}
              >
                <link.icon className="w-5 h-5" />
              </a>
            ))}
            {typeof navigator !== 'undefined' && 'share' in navigator && (
              <button
                onClick={handleNativeShare}
                className="p-3 rounded-full bg-muted hover:bg-muted/80 transition-colors hover:text-primary"
                title="More Options"
              >
                <Share2 className="w-5 h-5" />
              </button>
            )}
          </div>

          {/* Referral Link Section */}
          <div className="space-y-2">
            <label className="text-sm font-medium">Your Unique Referral Link</label>
            <div className="flex items-center gap-2">
              <Input 
                readOnly 
                value={referralLink} 
                className="font-mono text-sm bg-muted"
              />
              <Button size="icon" onClick={copyToClipboard}>
                <Copy className="w-4 h-4" />
              </Button>
            </div>
          </div>

          {/* Stats Section */}
          <div className="grid grid-cols-2 gap-4">
            <div className="bg-muted p-3 rounded-lg text-center">
              <div className="text-2xl font-bold text-primary">{user.token_balance || 0}</div>
              <div className="text-xs text-muted-foreground">Your Tokens</div>
            </div>
            <div className="bg-muted p-3 rounded-lg text-center">
              <div className="text-2xl font-bold text-primary flex items-center justify-center gap-1">
                {/* We don't have referral count in user object yet, maybe add it later */}
                <Users className="w-4 h-4" />
              </div>
              <div className="text-xs text-muted-foreground">Friends Invited</div>
            </div>
          </div>
        </div>

        <div className="flex justify-end">
          <Button onClick={() => setIsOpen(false)}>Close</Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
