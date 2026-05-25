import React, { useState } from 'react';
import { PaymentElement, useStripe, useElements } from '@stripe/react-stripe-js';
import { Button } from '@/components/ui/button';

interface StripePaymentFormProps {
  onSuccess: () => void;
  onCancel: () => void;
  amount: number;
}

export const StripePaymentForm = ({ onSuccess, onCancel, amount }: StripePaymentFormProps) => {
  const stripe = useStripe();
  const elements = useElements();
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    setIsProcessing(true);

    const { error } = await stripe.confirmPayment({
      elements,
      confirmParams: {
        // Make sure to change this to your payment completion page
        return_url: `${window.location.origin}/premium/success`,
      },
      redirect: 'if_required',
    });

    if (error) {
      setErrorMessage(error.message ?? 'An unexpected error occurred.');
      setIsProcessing(false);
    } else {
      // Payment succeeded
      onSuccess();
      setIsProcessing(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <PaymentElement />
      {errorMessage && <div className="text-red-500 text-sm">{errorMessage}</div>}
      <div className="flex flex-col gap-2 mt-4">
        <Button 
          type="submit" 
          disabled={!stripe || isProcessing}
          className="w-full bg-gradient-to-r from-yellow-500 to-yellow-700 hover:from-yellow-600 hover:to-yellow-800 text-white font-bold py-6 text-lg"
        >
          {isProcessing ? 'Processing...' : `Pay $${amount}`}
        </Button>
        <Button type="button" variant="ghost" onClick={onCancel} className="w-full">
          Cancel
        </Button>
      </div>
    </form>
  );
};
