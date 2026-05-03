import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import Stripe from 'stripe';
import * as dotenv from 'dotenv';

dotenv.config();

const router = Router();
const stripe = process.env.STRIPE_SECRET_KEY ? new Stripe(process.env.STRIPE_SECRET_KEY) : null;

// GET /api/premium/who-likes-you - Users who liked current user
router.get('/who-likes-you', authenticate, async (req: any, res) => {
  const userId = BigInt(req.user.id);
  try {
    // Return people who liked us (match_actions with type 'like' where target is us)
    const likes = await prisma.match_actions.findMany({
      where: {
        target_user_id: userId,
        action: 'like'
      },
      include: {
        users_match_actions_user_idTousers: {
          select: {
            id: true,
            name: true,
            avatar_url: true,
            user_profiles: {
              select: {
                bio: true,
                avatar_url: true
              }
            }
          }
        }
      },
      take: 50
    });

    const users = likes.map(l => {
      const u = l.users_match_actions_user_idTousers;
      const p = Array.isArray(u.user_profiles) ? u.user_profiles[0] : u.user_profiles;
      return {
        id: Number(u.id),
        name: u.name,
        avatar_url: p?.avatar_url || u.avatar_url,
        bio: p?.bio
      };
    });
    res.json({ users, total: users.length });
  } catch (error: any) {
    console.error('[Premium] Who-likes-you error:', error.message);
    res.status(500).json({ error: 'Failed to fetch likes' });
  }
});

// GET /api/premium/status - Premium subscription status
router.get('/status', authenticate, async (req: any, res) => {
  const userId = req.user.id;
  try {
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: {
        tier: true,
        tier_expires_at: true,
        unlimited_swipes: true
      }
    });

    res.json({
      is_premium: user?.tier !== 'free',
      plan: user?.tier,
      expires_at: user?.tier_expires_at,
      features: user?.unlimited_swipes ? ['unlimited_swipes', 'who_likes_you', 'boost_multiplier'] : [],
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch status' });
  }
});

// POST /api/premium/initiate - Initiate premium subscription via Stripe
router.post('/initiate', authenticate, async (req: any, res) => {
  const userId = req.user.id;
  const { planId } = req.body; // monthly, yearly

  if (!stripe) {
    return res.status(501).json({ error: 'Stripe is not configured on the server' });
  }

  const plans = {
    monthly: { usd: 1999, name: 'fwber Premium Monthly' },
    yearly: { usd: 14999, name: 'fwber Premium Yearly' }
  };

  const selectedPlan = plans[planId as keyof typeof plans] || plans.monthly;

  try {
    const session = await stripe.checkout.sessions.create({
      payment_method_types: ['card'],
      line_items: [
        {
          price_data: {
            currency: 'usd',
            product_data: {
              name: selectedPlan.name,
              description: 'Unlock all premium features including "Who Likes You" and unlimited swipes.',
            },
            unit_amount: selectedPlan.usd,
          },
          quantity: 1,
        },
      ],
      mode: 'payment', // Or 'subscription' if using Stripe products/prices
      success_url: `${process.env.FRONTEND_URL || 'https://www.fwber.me'}/premium?success=true`,
      cancel_url: `${process.env.FRONTEND_URL || 'https://www.fwber.me'}/premium?canceled=true`,
      metadata: {
        userId: userId.toString(),
        type: 'premium_upgrade',
        planId: planId
      }
    });

    res.json({ success: true, checkoutUrl: session.url });
  } catch (error) {
    console.error('[PREMIUM_INITIATE_ERROR]', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// POST /api/premium/purchase - Purchase premium with tokens
router.post('/purchase', authenticate, async (req: any, res) => {
  const userId = req.user.id;
  const { planId } = req.body;

  const tokenCosts = {
    monthly: 500,
    yearly: 4000
  };

  const cost = tokenCosts[planId as keyof typeof tokenCosts] || tokenCosts.monthly;

  try {
    const result = await prisma.$transaction(async (tx) => {
      const user = await tx.users.findUnique({ where: { id: userId } });
      if (!user || Number(user.token_balance) < cost) {
        throw new Error('Insufficient tokens');
      }

      await tx.users.update({
        where: { id: userId },
        data: {
          token_balance: { decrement: cost },
          tier: 'gold',
          unlimited_swipes: true,
          tier_expires_at: new Date(Date.now() + (planId === 'yearly' ? 365 : 30) * 24 * 60 * 60 * 1000)
        }
      });

      return { success: true };
    });

    res.json(result);
  } catch (error: any) {
    res.status(400).json({ error: error.message });
  }
});

// POST /api/premium/cancel - Cancel premium
router.post('/cancel', authenticate, async (req: any, res) => {
  const userId = req.user.id;
  try {
    await prisma.users.update({
      where: { id: userId },
      data: {
        tier: 'free',
        unlimited_swipes: false,
        tier_expires_at: null
      }
    });
    res.json({ message: 'Subscription cancelled' });
  } catch (error) {
    res.status(500).json({ error: 'Failed to cancel subscription' });
  }
});

export default router;
