import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/messages — list conversations
router.get('/', (_req, res) => {
  res.json([]);
});

// GET /api/messages/:id — get messages in conversation
router.get('/:id', (_req, res) => {
  res.json({ messages: [], conversation: { id: _req.params.id } });
});

// POST /api/messages — send message
router.post('/', (_req, res) => {
  res.json({ success: true, message: { id: Date.now(), ..._req.body } });
});

export default router;
