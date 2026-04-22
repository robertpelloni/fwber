import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/bounties
router.get('/', (_req, res) => {
  res.json({ data: [], total: 0, page: 1, per_page: 20 });
});

// POST /api/bounties
router.post('/', (_req, res) => {
  res.json({ message: 'Bounty creation not yet implemented' });
});

// GET /api/bounties/:id
router.get('/:id', (_req, res) => {
  res.status(404).json({ message: 'Bounty not found' });
});

export default router;
