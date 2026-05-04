import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import Stripe from 'stripe';
import * as dotenv from 'dotenv';

dotenv.config();

const router = Router();
const stripe = process.env.STRIPE_SECRET_KEY ? new Stripe(process.env.STRIPE_SECRET_KEY) : null;

router.use(authenticate);

/**
 * POST /api/boosts/purchase
 * Handles boost purchases via Tokens or Stripe
 */
router.post('/purchase', async (req, res) => {
  const userId = BigInt((req as any).user.id);
  const { type, paymentMethod } = req.body;

  // 1. Define pricing
  const prices = {
    standard: { tokens: 50, usd: 499, durationMinutes: 30 },
    super: { tokens: 100, usd: 999, durationMinutes: 120 }
  };

  const selectedPrice = prices[type as keyof typeof prices];
  if (!selectedPrice) {
    return res.status(400).json({ error: 'Invalid boost type' });
  }

  try {
    if (paymentMethod === 'token') {
      // HANDLE TOKEN PURCHASE
      const user = await prisma.users.findUnique({
        where: { id: userId },
        select: { token_balance: true }
      });

      if (!user || Number(user.token_balance) < selectedPrice.tokens) {
        return res.status(400).json({ error: 'Insufficient token balance' });
      }

      // Atomic transaction: Deduct tokens and create boost
      const result = await prisma.$transaction(async (tx) => {
        await tx.users.update({
          where: { id: userId },
          data: { token_balance: { decrement: selectedPrice.tokens } }
        });

        const expiresAt = new Date();
        expiresAt.setMinutes(expiresAt.getMinutes() + selectedPrice.durationMinutes);

        return await tx.boosts.create({
          data: {
            user_id: userId,
            boost_type: type as any,
            status: 'active',
            started_at: new Date(),
            expires_at: expiresAt
          }
        });
      });

      return res.json({ success: true, boost: result });

    } else if (paymentMethod === 'stripe') {
      // HANDLE STRIPE CHECKOUT
      if (!stripe) {
        return res.status(501).json({ error: 'Stripe is not configured on the server' });
      }

      const session = await stripe.checkout.sessions.create({
        payment_method_types: ['card'],
        line_items: [
          {
            price_data: {
              currency: 'usd',
              product_data: {
                name: `${type.toUpperCase()} Boost - fwber`,
                description: `${selectedPrice.durationMinutes} minutes of increased visibility`,
              },
              unit_amount: selectedPrice.usd,
            },
            quantity: 1,
          },
        ],
        mode: 'payment',
        success_url: `${process.env.FRONTEND_URL || 'https://www.fwber.me'}/boosts?success=true`,
        cancel_url: `${process.env.FRONTEND_URL || 'https://www.fwber.me'}/boosts?canceled=true`,
        metadata: {
          userId: userId.toString(),
          boostType: type,
          durationMinutes: selectedPrice.durationMinutes.toString()
        }
      });

      return res.json({ success: true, checkoutUrl: session.url });
    }

    return res.status(400).json({ error: 'Invalid payment method' });
  } catch (error: any) {
    console.error('[BOOST_PURCHASE_ERROR]', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

/**
 * GET /api/boosts/active
 */
router.get('/active', async (req, res) => {
  const userId = BigInt((req as any).user.id);
  try {
    const activeBoost = await prisma.boosts.findFirst({
      where: {
        user_id: userId,
        status: 'active',
        expires_at: { gt: new Date() }
      },
      orderBy: { expires_at: 'desc' }
    });
    res.json(activeBoost || { active: false, boost: null });
  } catch (error) {
    res.json({ active: false, boost: null });
  }
});

/**
 * GET /api/boosts/history
 */
router.get('/history', async (req, res) => {
  const userId = BigInt((req as any).user.id);
  try {
    const history = await prisma.boosts.findMany({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' },
      take: 20
    });
    res.json(history);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch boost history' });
  }
});

export default router;
