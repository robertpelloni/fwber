import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/wingman/cosmic-match
router.get('/cosmic-match', (_req, res) => {
  res.json({
    match: null,
    compatibility_score: 0,
    message: 'Cosmic match analysis not yet available',
  });
});

// GET /api/wingman/nemesis
router.get('/nemesis', (_req, res) => {
  res.json({
    nemesis: null,
    rivalry_score: 0,
    message: 'Nemesis analysis not yet available',
  });
});

// GET /api/wingman/fortune
router.get('/fortune', (_req, res) => {
  res.json({
    fortune: 'The stars are aligned in your favor today.',
    luck_score: 7,
    message: 'Fortune reading not yet available',
  });
});

// GET /api/wingman/vibe-check
router.get('/vibe-check', (_req, res) => {
  res.json({
    vibe: 'neutral',
    score: 0.5,
    message: 'Vibe check not yet available',
  });
});

export default router;
