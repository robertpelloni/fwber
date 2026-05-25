"use client";

import { useState } from "react";
import { Lock, Unlock, CheckCircle2, Copy, Twitter, Facebook } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { apiClient } from "@/lib/api/client";
import { useToast } from "@/components/ui/use-toast";

interface ShareToUnlockProps {
  targetId: string; // The ID of the content/profile to unlock
  title?: string;
  description?: string;
  onUnlock: () => void;
  children: React.ReactNode; // The trigger button/element
}

export function ShareToUnlock({
  targetId,
  title = "Unlock Premium Content",
  description = "Share this profile with a friend to unlock full details for free!",
  onUnlock,
  children
}: ShareToUnlockProps) {
  const [isOpen, setIsOpen] = useState(false);
  const [isUnlocked, setIsUnlocked] = useState(false);
  const { toast } = useToast();

  const shareUrl = typeof window !== 'undefined' ? `${window.location.origin}/profile/${targetId}` : '';

  const handleShare = async (platform: string) => {
    // Simulate share action (in a real app, we'd open a share dialog)
    // For "copy link", we copy to clipboard.
    
    if (platform === 'copy') {
      navigator.clipboard.writeText(shareUrl);
      toast({
        title: "Link Copied",
        description: "Now send it to a friend to unlock!",
      });
    }

    // Call backend to record the unlock
    try {
      await apiClient.post('/share-unlock', {
        target_profile_id: targetId,
        platform: platform
      });
      
      setIsUnlocked(true);
      toast({
        title: "Unlocked!",
        description: "Content has been unlocked.",
      });
      
      // Delay closing to show success state
      setTimeout(() => {
        setIsOpen(false);
        onUnlock();
      }, 1500);

    } catch (error) {
      console.error("Failed to unlock:", error);
      toast({
        title: "Error",
        description: "Something went wrong unlocking the content.",
        variant: "destructive"
      });
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        {children}
      </DialogTrigger>
      <DialogContent className="sm:max-w-md bg-zinc-900 border-zinc-800 text-white">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-xl">
            {isUnlocked ? <Unlock className="w-6 h-6 text-green-500" /> : <Lock className="w-6 h-6 text-pink-500" />}
            {title}
          </DialogTitle>
          <DialogDescription className="text-zinc-400">
            {description}
          </DialogDescription>
        </DialogHeader>
        
        <div className="flex flex-col gap-4 py-4">
          {isUnlocked ? (
            <div className="flex flex-col items-center justify-center py-8 text-center space-y-2">
              <div className="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center mb-2">
                <CheckCircle2 className="w-8 h-8 text-green-500" />
              </div>
              <p className="text-lg font-medium text-green-400">Content Unlocked!</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 gap-3">
               <Button 
                variant="outline" 
                className="w-full justify-start gap-3 h-12 border-zinc-700 hover:bg-zinc-800 text-white"
                onClick={() => handleShare('twitter')}
              >
                <Twitter className="w-5 h-5 text-blue-400" />
                Share on Twitter
              </Button>

              <Button 
                variant="outline" 
                className="w-full justify-start gap-3 h-12 border-zinc-700 hover:bg-zinc-800 text-white"
                onClick={() => handleShare('facebook')}
              >
                <Facebook className="w-5 h-5 text-blue-600" />
                Share on Facebook
              </Button>

              <Button 
                variant="outline" 
                className="w-full justify-start gap-3 h-12 border-zinc-700 hover:bg-zinc-800 text-white"
                onClick={() => handleShare('copy')}
              >
                <Copy className="w-5 h-5 text-zinc-400" />
                Copy Link to Share
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
