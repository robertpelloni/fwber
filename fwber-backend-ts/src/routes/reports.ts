import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/reports — list user reports
router.get('/', (_req, res) => {
  res.json([]);
});

// POST /api/reports — create report
router.post('/', (_req, res) => {
  res.json({ success: true, report: { id: Date.now(), ..._req.body } });
});

export default router;
