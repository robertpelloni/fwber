import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// Helper: serialize BigInt
function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (obj instanceof Date) return obj.toISOString();
  if (Array.isArray(obj)) return obj.map((v: any) => serialize(v));
  if (typeof obj === 'object') {
    const out: any = {};
    for (const key of Object.keys(obj)) out[key] = serialize(obj[key]);
    return out;
  }
  return obj;
}

// GET /api/notifications/count
router.get('/count', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const unread = await prisma.notifications.count({
      where: { user_id: userId, read_at: null },
    });
    const total = await prisma.notifications.count({
      where: { user_id: userId },
    });
    res.json({ count: Number(total), unread: Number(unread) });
  } catch (error: any) {
    res.json({ count: 0, unread: 0 });
  }
});



// GET /api/notifications/unread-count — alias for /count
router.get('/unread-count', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const unread = await prisma.notifications.count({
      where: { user_id: userId, read_at: null },
    });
    res.json({ count: Number(unread), unread_count: Number(unread) });
  } catch {
    res.json({ count: 0, unread_count: 0 });
  }
});

// PUT /api/notifications/mark-read — PUT variant (frontend may use PUT)
router.put('/mark-read', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    await prisma.notifications.updateMany({
      where: { user_id: userId, read_at: null },
      data: { read_at: new Date() },
    });
    res.json({ success: true });
  } catch {
    res.json({ success: true });
  }
});

// GET /api/notifications/preferences — alias for notification-preferences
router.get('/preferences', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const prefs = await prisma.notification_preferences.findMany({
      where: { user_id: userId },
    });
    res.json({ preferences: prefs.map((p: any) => serialize(p)) });
  } catch {
    res.json({ preferences: [] });
  }
});

// GET /api/notifications/:id — get single notification
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const page = Number(req.query.page) || 1;
    const perPage = Number(req.query.per_page) || 20;

    const notifications = await prisma.notifications.findMany({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' },
      skip: (page - 1) * perPage,
      take: perPage,
    });

    res.json(serialize(notifications));
  } catch (error: any) {
    console.error('[Notifications] Error:', error.message);
    res.json([]);
  }
});

// POST /api/notifications/mark-read - mark a single notification read
router.post('/mark-read', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { notification_id } = req.body;

    if (notification_id) {
      await prisma.notifications.updateMany({
        where: { id: BigInt(notification_id), user_id: userId },
        data: { read_at: new Date() },
      });
    }

    res.json({ success: true });
  } catch (error: any) {
    res.json({ success: true });
  }
});

// POST /api/notifications/mark-all-read
router.post('/mark-all-read', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    await prisma.notifications.updateMany({
      where: { user_id: userId, read_at: null },
      data: { read_at: new Date() },
    });
    res.json({ success: true });
  } catch (error: any) {
    res.json({ success: true });
  }
});

/**
 * Internal helper: create a notification for a user.
 * Can be imported and used by other routes.
 */
export async function createNotification(userId: bigint, title: string, body: string) {
  try {
    await prisma.notifications.create({
      data: {
        user_id: userId,
        title,
        body,
      },
    });
  } catch (error: any) {
    console.error('[Notifications] Create error:', error.message);
  }
}

export default router;
