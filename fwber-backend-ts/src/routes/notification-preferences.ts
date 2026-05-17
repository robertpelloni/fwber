import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/notification-preferences
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const prefs = await prisma.notification_preferences.findMany({
      where: { user_id: userId },
      orderBy: { type: 'asc' as const }
    });
    res.json(prefs.map(p => ({
      id: Number(p.id),
      type: p.type,
      mail: Boolean(p.mail),
      push: Boolean(p.push),
      database: Boolean(p.database),
    })));
  } catch (err: any) {
    console.error('[notification-prefs] GET error:', err.message);
    res.json([]);
  }
});

// PUT /api/notification-preferences — update a preference
router.put('/:type', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { type } = req.params;
    const { mail, push, database } = req.body;

    const updated = await prisma.notification_preferences.upsert({
      where: { user_id_type: { user_id: userId, type } },
      update: {
        ...(mail !== undefined ? { mail: Boolean(mail) } : {}),
        ...(push !== undefined ? { push: Boolean(push) } : {}),
        ...(database !== undefined ? { database: Boolean(database) } : {}),
      },
      create: {
        user_id: userId,
        type,
        mail: mail !== undefined ? Boolean(mail) : true,
        push: push !== undefined ? Boolean(push) : true,
        database: database !== undefined ? Boolean(database) : true,
      }
    });
    res.json({ success: true, preference: {
      id: Number(updated.id), type: updated.type,
      mail: Boolean(updated.mail), push: Boolean(updated.push), database: Boolean(updated.database)
    }});
  } catch (err: any) {
    console.error('[notification-prefs] PUT error:', err.message);
    res.status(500).json({ error: 'Failed to update preference' });
  }
});

export default router;
