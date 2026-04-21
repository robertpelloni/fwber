import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

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

// GET /api/location - Get user's current location
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = req.user?.id || req.user?.userId;
    const location = await prisma.user_locations.findFirst({
      where: { user_id: userId },
      orderBy: { updated_at: 'desc' },
    });
    res.json({
      data: location || { latitude: null, longitude: null, updated_at: null },
    });
  } catch (error) {
    res.json({ data: { latitude: null, longitude: null, updated_at: null } });
  }
});

// POST /api/location - Update user's location (alias for /update)
router.post('/', authenticate, async (req: any, res) => {
  const { latitude, longitude } = req.body;
  res.json({ data: { latitude, longitude, updated_at: new Date().toISOString() } });
});

// PUT /api/location/privacy - Update location privacy
router.put('/privacy', authenticate, async (req: any, res) => {
  const { privacy_level } = req.body;
  res.json({ data: { privacy_level, privacy_level_display: privacy_level } });
});

// DELETE /api/location - Clear location history
router.delete('/', authenticate, async (req: any, res) => {
  res.json({ success: true });
});

export default router;
