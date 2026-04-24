import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// Stub routes for /api/bulletin-boards
router.get('/', (_req, res) => res.json([]));
router.post('/', (_req, res) => res.json({ success: true }));

export default router;
