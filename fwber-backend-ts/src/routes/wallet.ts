import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import Stripe from 'stripe';
import * as dotenv from 'dotenv';

dotenv.config();

const router = Router();
const stripe = process.env.STRIPE_SECRET_KEY ? new Stripe(process.env.STRIPE_SECRET_KEY) : null;

// GET /api/wallet - Get user wallet balance
// GET /api/wallet/balance - Alias for balance
async function getWalletBalance(req: any, res: any) {
  const userId = BigInt(req.user.id);
  try {
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { token_balance: true }
    });
    res.json({
      balance: user?.token_balance || 0,
      tokens: user?.token_balance || 0,
      currency: 'FWB',
      transactions: [],
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch wallet' });
  }
}

router.get('/', authenticate, getWalletBalance);
router.get('/balance', authenticate, getWalletBalance);

// GET /api/wallet/transactions - Transaction history
router.get('/transactions', authenticate, async (req: any, res) => {
  const userId = BigInt(req.user.id);
  try {
    const transactions = await prisma.merchant_payments.findMany({
      where: { payer_id: userId },
      orderBy: { created_at: 'desc' },
      take: 20
    });
    res.json({ transactions, total: transactions.length });
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch transactions' });
  }
});

/**
 * POST /api/wallet/top-up/initiate
 * Creates a Stripe Payment Intent for a specific USD amount
 */
router.post('/top-up/initiate', authenticate, async (req: any, res) => {
  const userId = BigInt(req.user.id);
  const { amount_usd } = req.body;

  if (!stripe) {
    return res.status(501).json({ error: 'Stripe is not configured on the server' });
  }

  try {
    // Create a PaymentIntent with the order amount and currency
    const paymentIntent = await stripe.paymentIntents.create({
      amount: amount_usd * 100, // Stripe expects amount in cents
      currency: 'usd',
      automatic_payment_methods: {
        enabled: true,
      },
      metadata: {
        userId: userId.toString(),
        type: 'wallet_topup',
        amount_usd: amount_usd.toString()
      }
    });

    res.json({
      client_secret: paymentIntent.client_secret,
    });
  } catch (error) {
    console.error('[WALLET_TOPUP_INITIATE_ERROR]', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

/**
 * POST /api/wallet/top-up/confirm
 * Synchronous confirmation (optional, webhooks are better but this allows immediate UI update)
 */
router.post('/top-up/confirm', authenticate, async (req: any, res) => {
  const userId = BigInt(req.user.id);
  const { payment_intent_id } = req.body;

  if (!stripe) {
    return res.status(501).json({ error: 'Stripe is not configured' });
  }

  try {
    const paymentIntent = await stripe.paymentIntents.retrieve(payment_intent_id);

    if (paymentIntent.status === 'succeeded' && paymentIntent.metadata.userId === userId.toString()) {
      const amountUsdStr = paymentIntent.metadata.amount_usd || '0';
      const amountUsd = parseInt(amountUsdStr);
      
      // Calculate token amount (matches AMOUNTS in frontend)
      let fwbAmount = amountUsd * 10;
      if (amountUsd >= 100) fwbAmount = 1200;
      else if (amountUsd >= 50) fwbAmount = 550;

      // Update user balance
      await prisma.users.update({
        where: { id: userId },
        data: {
          token_balance: { increment: fwbAmount }
        }
      });

      return res.json({ success: true, new_balance: fwbAmount });
    }

    res.status(400).json({ error: 'Payment not successful' });
  } catch (error) {
    res.status(500).json({ error: 'Internal server error' });
  }
});

export default router;
