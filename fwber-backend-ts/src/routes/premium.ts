import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/premium/who-likes-you - Users who liked current user
router.get('/who-likes-you', authenticate, async (req: any, res) => {
  res.json({ users: [], total: 0 });
});

// GET /api/premium/status - Premium subscription status
router.get('/status', authenticate, async (req: any, res) => {
  res.json({
    is_premium: false,
    plan: null,
    expires_at: null,
    features: [],
  });
});

// POST /api/premium/initiate - Initiate premium subscription
router.post('/initiate', authenticate, async (req: any, res) => {
  // Stripe is not configured — return an error so the frontend can show a message
  res.status(501).json({ success: false, error: 'Card payments are not yet available. Please use tokens to upgrade.' });
});

// POST /api/premium/subscribe - Subscribe to premium
router.post('/subscribe', authenticate, async (req: any, res) => {
  res.json({ message: 'Subscription initiated', session: null });
});

// POST /api/premium/purchase - Purchase premium with tokens
router.post('/purchase', authenticate, async (req: any, res) => {
  res.json({ success: false, error: 'Premium purchase not yet implemented' });
});

// POST /api/premium/cancel - Cancel premium
router.post('/cancel', authenticate, async (req: any, res) => {
  res.json({ message: 'Subscription cancelled' });
});

export default router;
