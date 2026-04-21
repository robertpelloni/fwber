import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/location/nearby - Find nearby users
router.get('/nearby', authenticate, async (req: any, res) => {
  const { latitude, longitude, radius = 1000, limit = 50, ranking_strategy } = req.query;
  res.json({ users: [], total: 0, radius: Number(radius) });
});

// POST /api/location/update - Update user's location
router.post('/update', authenticate, async (req: any, res) => {
  res.json({ success: true });
});

// GET /api/location/status
router.get('/status', authenticate, async (req: any, res) => {
  res.json({ location_shared: false, latitude: null, longitude: null });
});

export default router;
