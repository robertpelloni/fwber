import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';

const router = Router();

router.use(authenticate);

// GET /api/notification-preferences
router.get('/', (_req, res) => {
  res.json({
    push_enabled: true,
    email_enabled: true,
    match_notifications: true,
    message_notifications: true,
    proximity_alerts: true,
    weekly_digest: true,
  });
});

// PUT /api/notification-preferences
router.put('/', (_req, res) => {
  res.json({ message: 'Preferences updated', updated: true });
});

export default router;
