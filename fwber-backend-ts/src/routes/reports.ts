import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/reports — list reports submitted by user
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const reports = await prisma.reports.findMany({
      where: { reporter_id: userId },
      include: {
        users_reports_accused_idTousers: {
          select: { id: true, name: true },
        },
      },
      orderBy: { created_at: 'desc' },
      take: 20,
    });

    res.json(reports.map((r: any) => ({
      id: Number(r.id),
      accused_user_id: Number(r.accused_id),
      accused_name: r.users_reports_accused_idTousers?.name || 'Unknown',
      reason: r.reason,
      details: r.details,
      status: r.status,
      resolution_notes: r.resolution_notes,
      created_at: r.created_at?.toISOString(),
    })));
  } catch (error: any) {
    console.error('[Reports] List error:', error.message);
    res.json([]);
  }
});

// POST /api/reports — create report
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { accused_id, reported_user_id, reason, details, description, message_id } = req.body;

    const targetId = accused_id || reported_user_id;
    if (!targetId || !reason) {
      return res.status(400).json({ message: 'accused_id and reason are required' });
    }

    const report = await prisma.reports.create({
      data: {
        reporter_id: userId,
        accused_id: BigInt(targetId),
        reason,
        details: details || description || null,
        message_id: message_id ? BigInt(message_id) : null,
        status: 'open',
      },
    });

    res.json({ success: true, report: { id: Number(report.id) } });
  } catch (error: any) {
    console.error('[Reports] Create error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to create report' });
  }
});

export default router;
