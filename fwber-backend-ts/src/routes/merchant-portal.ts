import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/merchant-portal/profile
router.get('/profile', (_req, res) => {
  res.json({ business_name: '', description: '', is_verified: false, rating: 0, total_deals: 0 });
});

// GET /api/merchant-portal/promotions
router.get('/promotions', (_req, res) => {
  res.json([]);
});

// GET /api/merchant-portal/analytics
router.get('/analytics', (_req, res) => {
  res.json({ views: 0, clicks: 0, conversions: 0, revenue: 0 });
});

// POST /api/merchant-portal/promotions
router.post('/promotions', (_req, res) => {
  res.json({ success: true, promotion: { id: Date.now(), ..._req.body } });
});

export default router;
