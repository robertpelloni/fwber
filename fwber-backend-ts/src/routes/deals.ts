import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/deals - Get nearby deals
router.get('/', authenticate, async (req: any, res) => {
  const { lat, lng, radius = 5000, sort, ranking_strategy, page = 1, per_page = 20 } = req.query;
  res.json({
    deals: [],
    total: 0,
    page: Number(page),
    per_page: Number(per_page),
  });
});

// GET /api/deals/categories - Get deal categories
router.get('/categories', authenticate, async (req: any, res) => {
  res.json({
    categories: [
      { id: 1, name: 'Dining', icon: 'restaurant' },
      { id: 2, name: 'Entertainment', icon: 'movie' },
      { id: 3, name: 'Shopping', icon: 'shopping_bag' },
      { id: 4, name: 'Wellness', icon: 'spa' },
      { id: 5, name: 'Travel', icon: 'flight' },
    ],
  });
});

// GET /api/deals/:id - Get single deal
router.get('/:id', authenticate, async (req: any, res) => {
  res.json({ deal: null });
});

// POST /api/deals/:id/claim - Claim a deal
router.post('/:id/claim', authenticate, async (req: any, res) => {
  res.json({ success: true, claimed: true });
});

export default router;
