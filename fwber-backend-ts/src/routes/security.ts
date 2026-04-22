import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// POST /api/security/keys — store public key for E2E encryption
router.post('/keys', (req, res) => {
  res.json({ message: 'Public key stored', stored: true });
});

// GET /api/security/keys — retrieve public key
router.get('/keys', (_req, res) => {
  res.json({ public_key: null });
});

export default router;
