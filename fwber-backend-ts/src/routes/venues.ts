import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/venues - Search venues by location
router.get('/', authenticate, async (req: any, res) => {
  const lat = Number(req.query.lat) || 0;
  const lng = Number(req.query.lng) || 0;
  const strategy = req.query.ranking_strategy || 'default';
  res.json({
    venues: [],
    total: 0,
    center: { lat, lng },
    ranking_strategy: strategy,
  });
});

// GET /api/venues/:id - Get venue details
router.get('/:id', authenticate, async (req: any, res) => {
  res.json({ venue: null });
});

// POST /api/venues - Create venue
router.post('/', authenticate, async (req: any, res) => {
  res.json({ venue: null, message: 'Venue created' });
});

export default router;
