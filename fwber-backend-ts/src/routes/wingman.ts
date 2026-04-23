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

// GET /api/wingman/date-ideas/:matchId
router.get('/date-ideas/:matchId', (_req, res) => {
  res.json({
    ideas: [
      { title: 'Sunset at Belle Isle', description: 'Pack a blanket and watch the skyline from the island.', vibe: 'Romantic', estimated_cost: 'Free' },
      { title: 'Jazz at Cliff Bell\'s', description: 'Classic Detroit atmosphere with live world-class jazz.', vibe: 'Sophisticated', estimated_cost: '$$' },
      { title: 'Arcade Night at Offworld', description: 'Retro games and pizza in a high-energy setting.', vibe: 'Playful', estimated_cost: '$' },
      { title: 'Eastern Market Morning', description: 'Browse local vendors and grab coffee together.', vibe: 'Casual', estimated_cost: '$' },
      { title: 'Detroit Institute of Arts', description: 'Explore world-class art collections side by side.', vibe: 'Cultural', estimated_cost: '$' },
    ],
    message: 'Date ideas generated successfully',
  });
});

export default router;
