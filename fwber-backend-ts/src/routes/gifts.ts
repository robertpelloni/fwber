import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

// GET /api/gifts - Get all gifts (summary)
router.get('/', authenticate, async (req: any, res) => {
  res.json({ received: [], sent: [], available: [] });
});

// GET /api/gifts/received - Get received gifts
router.get('/received', authenticate, async (req: any, res) => {
  res.json([]);
});

// GET /api/gifts/sent - Get sent gifts
router.get('/sent', authenticate, async (req: any, res) => {
  res.json([]);
});

// GET /api/gifts/available - Get available gifts to send
router.get('/available', authenticate, async (req: any, res) => {
  res.json({ gifts: [] });
});

// POST /api/gifts/send - Send a gift
router.post('/send', authenticate, async (req: any, res) => {
  res.json({ success: true, gift: req.body });
});

export default router;
