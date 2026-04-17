import { Router } from 'express';
import type { Request, Response } from 'express';

const router = Router();

// POST /api/analytics/events - Accept analytics events
router.post('/events', (req: Request, res: Response) => {
  const { event, properties, timestamp } = req.body;

  // For now, just acknowledge receipt. In production, queue for processing.
  if (process.env.NODE_ENV !== 'production') {
    console.log('[analytics]', event, properties);
  }

  res.status(202).json({ success: true });
});

export default router;
