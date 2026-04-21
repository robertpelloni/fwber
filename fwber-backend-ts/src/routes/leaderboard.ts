import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/leaderboard - Get leaderboard rankings
router.get('/', authenticate, async (req: any, res) => {
  const { type = 'global', page = 1, per_page = 20 } = req.query;
  res.json({
    leaderboard: [],
    total: 0,
    page: Number(page),
    per_page: Number(per_page),
    type,
  });
});

// GET /api/leaderboard/nearby - Get nearby leaderboard
router.get('/nearby', authenticate, async (req: any, res) => {
  res.json({ leaderboard: [], total: 0 });
});

// GET /api/leaderboard/friends - Get friends leaderboard
router.get('/friends', authenticate, async (req: any, res) => {
  res.json({ leaderboard: [], total: 0 });
});

export default router;
