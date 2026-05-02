import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/proximity-chatrooms
router.get('/', (_req, res) => {
  res.json([]);
});

// GET /api/proximity-chatrooms/conference-pulse
router.get('/conference-pulse', (_req, res) => {
  res.json({
    professionals: [],
    chatrooms: [],
    meta: {
      professionals_count: 0,
      chatrooms_count: 0
    }
  });
});

// GET /api/proximity-chatrooms/:id
router.get('/:id', (_req, res) => {
  res.json({ id: _req.params.id, name: 'Nearby Room', users: [] });
});

export default router;
