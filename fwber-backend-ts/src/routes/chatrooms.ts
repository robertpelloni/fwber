import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/chatrooms
router.get('/', (_req, res) => {
  res.json([]);
});

// POST /api/chatrooms
router.post('/', (_req, res) => {
  res.json({ success: true, chatroom: { id: Date.now(), ..._req.body } });
});

// GET /api/chatrooms/:id
router.get('/:id', (_req, res) => {
  res.json({ id: _req.params.id, name: 'Chat Room', messages: [] });
});

// POST /api/chatrooms/:id/messages
router.post('/:id/messages', (_req, res) => {
  res.json({ success: true, message: { id: Date.now(), ..._req.body } });
});

export default router;
