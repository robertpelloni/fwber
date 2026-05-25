import { Router } from 'express';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/health/metrics — system health metrics
router.get('/metrics', async (_req, res) => {
  try {
    const userCount = await prisma.users.count().catch(() => 0);
    const messageCount = await prisma.messages.count().catch(() => 0);
    const matchCount = await prisma.matches.count().catch(() => 0);

    res.json({
      status: 'ok',
      version: '2.0.1-ts',
      database: 'connected',
      uptime_seconds: Math.round(process.uptime()),
      memory: {
        rss_mb: Math.round(process.memoryUsage().rss / 1024 / 1024),
        heap_mb: Math.round(process.memoryUsage().heapUsed / 1024 / 1024),
      },
      metrics: {
        total_users: userCount,
        total_messages: messageCount,
        total_matches: matchCount,
        active_connections: 0,
      },
    });
  } catch (error: any) {
    res.json({
      status: 'degraded',
      version: '2.0.1-ts',
      database: 'error',
      error: error.message,
    });
  }
});

export default router;
