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
router.post('/initiate', async (req: any, res) => {
  const { receiver_id } = req.body;
  if (!receiver_id) return res.status(400).json({ error: 'receiver_id is required' });

  res.json({
    id: Math.floor(Math.random() * 1000000),
    status: 'calling',
    success: true
  });
});

// PUT /api/video/:callId/status
router.put('/:callId/status', async (req: any, res) => {
  const { status } = req.body;
  res.json({ success: true, callId: req.params.callId, status });
});

export default router;
