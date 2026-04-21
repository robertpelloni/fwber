import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// All video routes require auth
router.use(authenticate);

// GET /api/video/history
router.get('/history', (_req, res) => {
  res.json({ data: [], total: 0 });
});

// POST /api/video/initiate
router.post('/initiate', (_req, res) => {
  res.json({ message: 'Video calls not yet implemented' });
});

// PUT /api/video/:callId/status
router.put('/:callId/status', (_req, res) => {
  res.json({ message: 'Video calls not yet implemented' });
});

export default router;
