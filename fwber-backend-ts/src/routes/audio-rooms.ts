import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();
router.use(authenticate);

// GET /api/audio-rooms - List ranked audio rooms
router.get('/', async (req, res) => {
  res.json({
    data: [],
    rooms: [],
    meta: {
      ranking_strategy: 'trust-aware',
      total_active: 0
    }
  });
});

// POST /api/audio-rooms - Create a new audio room
router.post('/', async (req, res) => {
  res.json({
    id: Math.floor(Math.random() * 10000),
    name: req.body.name || 'New Audio Room',
    success: true
  });
});

// POST /api/audio-rooms/:id/join - Join a room
router.post('/:id/join', async (req, res) => {
  res.json({
    room: {
      id: req.params.id,
      name: 'Audio Room',
      host_id: 1,
    },
    participants: []
  });
});

// POST /api/audio-rooms/:id/leave - Leave a room
router.post('/:id/leave', async (req, res) => {
  res.json({ success: true });
});

// POST /api/audio-rooms/:id/signal - WebRTC signaling
router.post('/:id/signal', async (req, res) => {
  res.json({ success: true });
});

export default router;
