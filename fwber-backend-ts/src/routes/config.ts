import { Router } from 'express';

const router = Router();

// GET /api/config/features — feature flags
router.get('/features', (_req, res) => {
  res.json({
    features: {
      wingman_ai: true,
      video_calls: true,
      proximity_discovery: true,
      burner_links: true,
      federation: false,
      audio_rooms: true,
      marketplace: true,
      groups: true,
      events: true,
      chatrooms: true,
      journals: true,
      scrapbook: true,
      achievements: true,
      leaderboards: true,
      daily_checkin: true,
      gift_sending: true,
      profile_boosts: true,
      premium_subscriptions: true,
      token_economy: true,
      photo_verification: false,
      two_factor_auth: true,
      e2e_encryption: false,
    },
    version: '2.0.1-ts',
    environment: process.env.NODE_ENV || 'production',
  });
});

// GET /api/config/health — health check
router.get('/health', (_req, res) => {
  res.json({
    status: 'ok',
    version: '2.0.1-ts',
    uptime: process.uptime(),
    memory: {
      rss: Math.round(process.memoryUsage().rss / 1024 / 1024),
      heapUsed: Math.round(process.memoryUsage().heapUsed / 1024 / 1024),
      heapTotal: Math.round(process.memoryUsage().heapTotal / 1024 / 1024),
    },
  });
});

export default router;
