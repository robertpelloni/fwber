import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/marketplace/nearby - Get nearby marketplace items
router.get('/nearby', authenticate, async (req: any, res) => {
  const { lat, lng, radius_m = 5000, category, page = 1, limit = 20 } = req.query;
  res.json({
    items: [],
    total: 0,
    page: Number(page),
    limit: Number(limit),
    radius_m: Number(radius_m),
  });
});

// GET /api/marketplace/items - Get all marketplace items
router.get('/items', authenticate, async (req: any, res) => {
  res.json({ items: [], total: 0 });
});

// POST /api/marketplace/items - Create a marketplace item
router.post('/items', authenticate, async (req: any, res) => {
  res.json({ item: req.body, success: true });
});

// GET /api/marketplace/categories - Get categories
router.get('/categories', authenticate, async (req: any, res) => {
  res.json({ categories: [] });
});

export default router;
