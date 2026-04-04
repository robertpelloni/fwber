import React, { useState } from 'react'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Check, CreditCard, ShieldCheck, Star } from 'lucide-react'
import { Elements } from '@stripe/react-stripe-js'
import { useQueryClient } from '@tanstack/react-query'
import { stripePromise } from '@/lib/stripe'
import { useInitiatePurchase, usePurchasePremium } from '@/lib/hooks/use-premium'
import type { PremiumInitiationResponse } from '@/lib/hooks/use-premium'
import { StripePaymentForm } from './StripePaymentForm'
import { useToast } from '@/components/ToastProvider'

interface PremiumUpgradeModalProps {
  isOpen: boolean
  onClose: () => void
}

export const PremiumUpgradeModal = ({ isOpen, onClose }: PremiumUpgradeModalProps) => {
  const queryClient = useQueryClient()
  const { showError, showSuccess } = useToast()
  const { mutate: initiatePurchase, isPending: isInitiating } = useInitiatePurchase()
  const { mutate: purchasePremium, isPending: isPurchasing } = usePurchasePremium()
  const [paymentIntent, setPaymentIntent] = useState<PremiumInitiationResponse | null>(null)
  const stripeEnabled = !!stripePromise

  const reset = () => {
    setPaymentIntent(null)
  }

  const handleClose = () => {
    reset()
    onClose()
  }

  const handleSuccess = () => {
    queryClient.invalidateQueries({ queryKey: ['premium-status'] })
    queryClient.invalidateQueries({ queryKey: ['premium-history'] })
    queryClient.invalidateQueries({ queryKey: ['user'] })
    showSuccess('Gold unlocked', 'Your premium plan is active now.')
    handleClose()
  }

  const handleUpgrade = () => {
    if (!stripeEnabled) {
      purchasePremium(
        { paymentMethod: 'stripe', planId: 'gold_monthly' },
        {
          onSuccess: () => handleSuccess(),
          onError: (error: unknown) => {
            showError('Upgrade failed', error instanceof Error ? error.message : 'Unable to complete premium purchase.')
          },
        }
      )

      return
    }

    initiatePurchase('gold_monthly', {
      onSuccess: (data) => {
        setPaymentIntent(data)
      },
      onError: (error: unknown) => {
        showError('Checkout unavailable', error instanceof Error ? error.message : 'Unable to start card checkout.')
      },
    })
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => {
      if (!open) {
        handleClose()
      }
    }}>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-2xl text-yellow-600">
            <Star className="h-6 w-6 fill-yellow-600" />
            Upgrade to Gold
          </DialogTitle>
          <DialogDescription>
            Restore the premium dating layer with the highest-signal benefits: see who already liked you, remove swipe caps, and keep your plan status visible in settings.
          </DialogDescription>
        </DialogHeader>

        {!paymentIntent ? (
          <>
            <div className="space-y-4 py-4">
              <div className="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-950/20">
                <div className="text-sm font-semibold uppercase tracking-wide text-yellow-700 dark:text-yellow-300">Gold Monthly</div>
                <div className="mt-2 text-3xl font-black text-gray-900 dark:text-white">$19.99</div>
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                  30 days of premium visibility and unlimited swiping.
                </p>
              </div>

              {[
                {
                  title: 'See who already liked you',
                  description: 'Unlock the dedicated admirers feed instead of waiting to discover them manually.',
                },
                {
                  title: 'Unlimited swipes',
                  description: 'Remove the cap so your discovery flow stays uninterrupted during active sessions.',
                },
                {
                  title: 'Subscription visibility',
                  description: 'Track your tier and billing history directly from the restored subscription settings page.',
                },
              ].map((feature) => (
                <div key={feature.title} className="flex items-start gap-3">
                  <div className="rounded-full bg-green-100 p-2 dark:bg-green-900/30">
                    <Check className="h-4 w-4 text-green-600 dark:text-green-400" />
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-900 dark:text-white">{feature.title}</h4>
                    <p className="text-sm text-gray-600 dark:text-gray-300">{feature.description}</p>
                  </div>
                </div>
              ))}

              <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-200">
                <div className="flex items-start gap-2">
                  <ShieldCheck className="mt-0.5 h-4 w-4" />
                  <div>
                    {stripeEnabled
                      ? 'Secure card checkout is enabled. You will be taken into Stripe Elements to confirm the purchase.'
                      : 'Stripe is not configured in this environment, so the restored backend uses the mock payment gateway to keep the premium flow testable and demonstrable.'}
                  </div>
                </div>
              </div>
            </div>

            <DialogFooter className="flex-col gap-2 sm:flex-col">
              <Button
                className="w-full bg-gradient-to-r from-yellow-500 to-yellow-700 text-white hover:from-yellow-600 hover:to-yellow-800"
                onClick={handleUpgrade}
                disabled={isInitiating || isPurchasing}
              >
                <CreditCard className="mr-2 h-4 w-4" />
                {isInitiating || isPurchasing ? 'Processing...' : stripeEnabled ? 'Continue to secure checkout' : 'Upgrade to Gold'}
              </Button>
              <Button variant="ghost" onClick={handleClose} className="w-full">
                Maybe later
              </Button>
            </DialogFooter>
          </>
        ) : stripeEnabled ? (
          <Elements stripe={stripePromise} options={{ clientSecret: paymentIntent.client_secret }}>
            <StripePaymentForm
              onSuccess={handleSuccess}
              onCancel={reset}
              amount={paymentIntent.amount}
            />
          </Elements>
        ) : null}
      </DialogContent>
    </Dialog>
  )
}
