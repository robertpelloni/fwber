import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/moderation — moderation dashboard (moderators only)
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    // Check if user is moderator
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { is_moderator: true },
    });

    if (!user?.is_moderator) {
      return res.status(403).json({ message: 'Moderator access required' });
    }

    // Get open reports
    const reports = await prisma.reports.findMany({
      where: { status: 'open' },
      include: {
        users_reports_reporter_idTousers: {
          select: { id: true, name: true, email: true },
        },
        users_reports_accused_idTousers: {
          select: { id: true, name: true, email: true },
        },
      },
      orderBy: { created_at: 'desc' },
      take: 50,
    });

    // Get stats
    const openCount = await prisma.reports.count({ where: { status: 'open' } });
    const resolvedCount = await prisma.reports.count({ where: { status: 'resolved' } });
    const totalReports = await prisma.reports.count();

    res.json({
      reports: reports.map((r: any) => ({
        id: Number(r.id),
        reason: r.reason,
        details: r.details,
        status: r.status,
        created_at: r.created_at?.toISOString(),
        reporter: {
          id: Number(r.users_reports_reporter_idTousers?.id || 0),
          name: r.users_reports_reporter_idTousers?.name || 'Unknown',
        },
        accused: {
          id: Number(r.users_reports_accused_idTousers?.id || 0),
          name: r.users_reports_accused_idTousers?.name || 'Unknown',
        },
      })),
      stats: {
        open: Number(openCount),
        resolved: Number(resolvedCount),
        total: Number(totalReports),
      },
      total: Number(openCount),
    });
  } catch (error: any) {
    console.error('[Moderation] Dashboard error:', error.message);
    res.json({ reports: [], stats: { open: 0, resolved: 0, total: 0 }, total: 0 });
  }
});

// GET /api/moderation/dashboard — alias
router.get('/dashboard', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { is_moderator: true },
    });

    if (!user?.is_moderator) {
      return res.status(403).json({ message: 'Moderator access required' });
    }

    const openCount = await prisma.reports.count({ where: { status: 'open' } });
    const resolvedCount = await prisma.reports.count({ where: { status: 'resolved' } });
    const totalBlocks = await prisma.blocks.count();

    res.json({
      open_reports: Number(openCount),
      resolved_reports: Number(resolvedCount),
      total_blocks: Number(totalBlocks),
      status: 'healthy',
    });
  } catch (error: any) {
    res.json({ open_reports: 0, resolved_reports: 0, total_blocks: 0, status: 'healthy' });
  }
});

// POST /api/moderation/:id/action — take action on a report
router.post('/:id/action', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const reportId = BigInt(req.params.id);
    const { action, notes } = req.body;

    const user = await prisma.users.findUnique({
      where: { id: userId },
      select: { is_moderator: true },
    });

    if (!user?.is_moderator) {
      return res.status(403).json({ message: 'Moderator access required' });
    }

    await prisma.reports.update({
      where: { id: reportId },
      data: {
        status: action === 'dismiss' ? 'dismissed' : 'resolved',
        resolution_notes: notes || null,
        moderator_id: userId,
      },
    });

    res.json({ success: true });
  } catch (error: any) {
    console.error('[Moderation] Action error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to process action' });
  }
});

export default router;
