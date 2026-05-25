import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/hardware-tokens/status
router.get('/status', (_req, res) => {
  res.json({
    enabled: false,
    tokens: [],
    message: 'No hardware tokens registered',
  });
});

// POST /api/hardware-tokens/register
router.post('/register', (_req, res) => {
  res.json({ message: 'Hardware token registration not yet implemented' });
});

// DELETE /api/hardware-tokens/:id
router.delete('/:id', (_req, res) => {
  res.json({ message: 'Hardware token removed', removed: true });
});

export default router;
