import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Check, Star } from 'lucide-react';
import { useInitiatePurchase } from '@/lib/hooks/use-premium';
import { Elements } from '@stripe/react-stripe-js';
import { stripePromise } from '@/lib/stripe';
import { StripePaymentForm } from './StripePaymentForm';
import { useQueryClient } from '@tanstack/react-query';

interface PremiumUpgradeModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const PremiumUpgradeModal = ({ isOpen, onClose }: PremiumUpgradeModalProps) => {
  const { mutate: initiate, isPending: isInitiating } = useInitiatePurchase();
  const [clientSecret, setClientSecret] = useState<string | null>(null);
  const queryClient = useQueryClient();

  const handleInitiate = () => {
    initiate('gold_monthly', {
      onSuccess: (data) => {
        setClientSecret(data.client_secret);
      }
    });
  };

  const handleSuccess = () => {
    // Invalidate queries to refresh premium status
    // Note: Webhook might take a moment, so optimistic update or polling might be better
    // For now, we just invalidate and close
    queryClient.invalidateQueries({ queryKey: ['premium-status'] });
    queryClient.invalidateQueries({ queryKey: ['user'] });
    onClose();
    setClientSecret(null); // Reset for next time
  };

  const handleCancel = () => {
    setClientSecret(null);
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => {
      if (!open) {
        onClose();
        setClientSecret(null);
      }
    }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-2xl text-yellow-600">
            <Star className="fill-yellow-600" /> Upgrade to Gold
          </DialogTitle>
          <DialogDescription>
            Unlock exclusive features and supercharge your dating experience.
          </DialogDescription>
        </DialogHeader>
        
        {!clientSecret ? (
          <>
            <div className="space-y-4 py-4">
              <div className="flex items-center gap-3">
                <div className="bg-green-100 p-2 rounded-full">
                  <Check className="w-5 h-5 text-green-600" />
                </div>
                <div>
                  <h4 className="font-semibold">See Who Likes You</h4>
                  <p className="text-sm text-gray-500">Reveal your secret admirers instantly.</p>
                </div>
              </div>
              
              <div className="flex items-center gap-3">
                <div className="bg-green-100 p-2 rounded-full">
                  <Check className="w-5 h-5 text-green-600" />
                </div>
                <div>
                  <h4 className="font-semibold">Unlimited Swipes</h4>
                  <p className="text-sm text-gray-500">No more daily limits. Swipe as much as you want.</p>
                </div>
              </div>
            </div>

            <DialogFooter className="flex-col sm:flex-col gap-2">
              <Button 
                className="w-full bg-gradient-to-r from-yellow-500 to-yellow-700 hover:from-yellow-600 hover:to-yellow-800 text-white font-bold py-6 text-lg"
                onClick={handleInitiate}
                disabled={isInitiating}
              >
                {isInitiating ? 'Loading Payment...' : 'Upgrade for $9.99/mo'}
              </Button>
              <Button variant="ghost" onClick={onClose} className="w-full">
                Maybe Later
              </Button>
            </DialogFooter>
          </>
        ) : (
          <Elements stripe={stripePromise} options={{ clientSecret }}>
            <StripePaymentForm 
              onSuccess={handleSuccess} 
              onCancel={handleCancel}
              amount={9.99}
            />
          </Elements>
        )}
      </DialogContent>
    </Dialog>
  );
};
