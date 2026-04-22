import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/blocks — list blocked users
router.get('/', (_req, res) => {
  res.json({ data: [], total: 0 });
});

// POST /api/blocks — block a user
router.post('/', (_req, res) => {
  res.json({ message: 'User blocked', blocked: true });
});

// DELETE /api/blocks/:userId — unblock a user
router.delete('/:userId', (_req, res) => {
  res.json({ message: 'User unblocked', unblocked: true });
});

export default router;
