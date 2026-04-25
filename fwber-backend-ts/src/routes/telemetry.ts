import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// POST /api/telemetry/client-events
// Accepts client-side telemetry events (face blur, preview stats, etc.)
router.post('/client-events', authenticate, async (req: any, res) => {
  // Stub: accept and discard for now
  res.json({ ok: true });
});

export default router;
