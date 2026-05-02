import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/proximity/local-pulse
router.get('/local-pulse', authenticate, async (req: any, res) => {
  res.json({
    artifacts: [],
    candidates: [], // Matches frontend expectation for nearby matches
    profiles: [],
    venues: [],
    total: 0,
    radius: Number(req.query.radius) || 1000,
    meta: {
      artifacts_count: 0,
      candidates_count: 0,
      venues_count: 0,
      radius: Number(req.query.radius) || 1000,
      ranking_strategy: 'freshness'
    }
  });
});

// GET /api/proximity/feed
router.get('/feed', authenticate, async (req: any, res) => {
  res.json({ artifacts: [] });
});

// POST /api/proximity/artifacts
router.post('/artifacts', authenticate, async (req: any, res) => {
  res.json({ artifact: null, success: true });
});

// GET /api/proximity/artifacts/:id
router.get('/artifacts/:id', authenticate, async (req: any, res) => {
  res.json({ artifact: null });
});

// POST /api/proximity/artifacts/:id/flag
router.post('/artifacts/:id/flag', authenticate, async (req: any, res) => {
  res.json({ message: 'Flagged' });
});

// DELETE /api/proximity/artifacts/:id
router.delete('/artifacts/:id', authenticate, async (req: any, res) => {
  res.json({ message: 'Deleted' });
});

// POST /api/proximity/artifacts/:id/comments
router.post('/artifacts/:id/comments', authenticate, async (req: any, res) => {
  res.json({ message: 'Comment added', comment: null });
});

// GET /api/proximity/artifacts/:id/comments
router.get('/artifacts/:id/comments', authenticate, async (req: any, res) => {
  res.json({ data: [] });
});

// POST /api/proximity/artifacts/:id/vote
router.post('/artifacts/:id/vote', authenticate, async (req: any, res) => {
  res.json({ message: 'Voted', vote: null });
});

export default router;
