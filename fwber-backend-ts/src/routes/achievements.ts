import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.get('/', authenticate, (_req, res) => {
  res.json({
    achievements: [
      { id: 1, name: 'First Steps', description: 'Complete your profile', icon: '👣', earned: true, earned_at: '2025-01-01' },
      { id: 2, name: 'Social Butterfly', description: 'Make 5 connections', icon: '🦋', earned: false, progress: 2, total: 5 },
      { id: 3, name: 'Night Owl', description: 'Active after midnight', icon: '🦉', earned: false },
      { id: 4, name: 'Photogenic', description: 'Upload 5 photos', icon: '📸', earned: false, progress: 1, total: 5 },
    ],
    total: 4,
    earned: 1,
  });
});

export default router;
