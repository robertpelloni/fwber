import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/subscriptions
router.get('/', async (req: any, res) => {
  try {
    const subs = await prisma.subscriptions.findMany({
      where: { user_id: BigInt(req.user.id) }, orderBy: { created_at: 'desc' }
    });
    res.json(subs.map((s: any) => ({
      id: Number(s.id), name: s.name, stripe_status: s.stripe_status,
      stripe_price: s.stripe_price, ends_at: s.ends_at, created_at: s.created_at
    })));
  } catch (err: any) { res.json([]); }
});

// GET /api/subscriptions/history
router.get('/history', async (req: any, res) => {
  try {
    const payments = await prisma.payments.findMany({
      where: { user_id: BigInt(req.user.id), description: { contains: 'Subscription' } },
      orderBy: { created_at: 'desc' }, take: 20
    });
    const data = payments.map((p: any) => ({
      id: Number(p.id), amount: Number(p.amount), currency: p.currency || 'usd',
      status: p.status, description: p.description || 'Premium subscription', created_at: p.created_at
    }));
    res.json({ data, total: data.length });
  } catch (err: any) { res.json({ data: [], total: 0 }); }
});

// GET /api/subscriptions/current
router.get('/current', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const sub = await prisma.subscriptions.findFirst({
      where: { user_id: userId, stripe_status: 'active', ends_at: { gt: new Date() } },
      orderBy: { created_at: 'desc' }
    });
    if (sub) return res.json({ plan: sub.name, status: sub.stripe_status, expires_at: sub.ends_at, stripe_id: sub.stripe_id, source: 'stripe' });

    const profiles = await prisma.user_profiles.findMany({ where: { user_id: userId }, take: 1 });
    const profile: any = profiles[0];
    if (profile?.is_premium && profile?.premium_expires_at && new Date() < new Date(profile.premium_expires_at)) {
      return res.json({ plan: 'gold', status: 'active', expires_at: profile.premium_expires_at, source: 'token_purchase' });
    }
    res.json({ plan: 'free', status: 'active', expires_at: null });
  } catch (err: any) { res.json({ plan: 'free', status: 'active', expires_at: null }); }
});

// POST /api/subscriptions/checkout
router.post('/checkout', async (req: any, res) => {
  const { plan } = req.body;
  if (!plan || !['gold_monthly', 'gold_yearly'].includes(plan)) return res.status(422).json({ error: 'Valid plan required' });
  res.json({ message: 'Checkout session', plan, amount: plan === 'gold_yearly' ? 9999 : 999, currency: 'usd', status: 'requires_payment_method' });
});

// POST /api/subscriptions/cancel
router.post('/cancel', async (req: any, res) => {
  try {
    const sub = await prisma.subscriptions.findFirst({ where: { user_id: BigInt(req.user.id), stripe_status: 'active' } });
    if (!sub) return res.status(404).json({ error: 'No active subscription' });
    await prisma.subscriptions.update({ where: { id: sub.id }, data: { stripe_status: 'canceling' } });
    res.json({ message: 'Subscription canceled at period end', expires_at: sub.ends_at, status: 'canceling' });
  } catch (err: any) { res.status(500).json({ error: 'Failed to cancel' }); }
});

export default router;
