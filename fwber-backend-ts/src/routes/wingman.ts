import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/wingman/cosmic-match
router.get('/cosmic-match', (_req, res) => {
  res.json({
    best_match: 'A creative soul who shares your curiosity',
    best_reason: 'Your combined energy creates a magnetic connection.',
    worst_match: 'Someone who fears spontaneity',
    worst_reason: 'Your free spirit would clash with rigidity.',
  });
});

// GET /api/wingman/nemesis
router.get('/nemesis', (_req, res) => {
  res.json({
    nemesis_type: 'The Chronically Basic',
    clashing_traits: ['Orders pumpkin spice lattes unironically', 'Thinks hiking is a personality trait'],
    why_it_would_fail: 'You need depth; they need a filter.',
    scientific_explanation: 'Opposites attract, but only in magnetism—not in conversation.',
  });
});

// GET /api/wingman/fortune
router.get('/fortune', (_req, res) => {
  res.json({
    fortune: 'The stars are aligned in your favor today. A surprise encounter awaits.',
  });
});

// GET /api/wingman/vibe-check
router.get('/vibe-check', (_req, res) => {
  res.json({
    green_flags: ['Emotionally available', 'Good taste in music', 'Kind to animals'],
    red_flags: ['Replies with one word', 'Has "fluent in sarcasm" in bio'],
  });
});

// POST /api/wingman/quirk-check
router.post('/quirk-check', (_req, res) => {
  res.json({
    flag_type: 'Beige Flag',
    reason: 'It\'s not a red flag, it\'s not a green flag... it just *is*.',
    emoji: '🤷',
  });
});

// POST /api/wingman/roast
router.post('/roast', (_req, res) => {
  res.json({
    roast: 'Your profile has the energy of a participation trophy—technically present, but nobody asked for it.',
  });
});

// GET /api/wingman/profile-analysis
router.get('/profile-analysis', (_req, res) => {
  res.json({
    score: 72,
    strengths: ['Clear photos', 'Interesting bio'],
    weaknesses: ['Could add more conversation starters'],
    tips: ['Try adding a fun fact about yourself'],
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
  });
});

// GET /api/wingman/ice-breakers/:matchId
router.get('/ice-breakers/:matchId', (_req, res) => {
  res.json({ suggestion: 'Ask about their favorite local spot — it always sparks a great conversation.' });
});

// GET /api/wingman/replies/:matchId
router.get('/replies/:matchId', (_req, res) => {
  res.json({ suggestion: 'Try: "That\'s awesome! What made you choose that?"' });
});

// POST /api/wingman/compatibility-audit/:targetId
router.post('/compatibility-audit/:targetId', (_req, res) => {
  res.json({
    overall_score: 73,
    strengths: ['Shared sense of humor', 'Similar values'],
    weaknesses: ['Different sleep schedules'],
    surviving_the_apocalypse_together: true,
  });
});

export default router;
