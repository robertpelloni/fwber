import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// GET /api/video/history
router.get('/history', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const calls = await prisma.video_calls.findMany({
      where: { OR: [{ caller_id: userId }, { receiver_id: userId }] },
      orderBy: { created_at: 'desc' }, take: 30,
      include: {
        users_video_calls_caller_idTousers: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } },
        users_video_calls_receiver_idTousers: { select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true } } } }
      }
    });
    const data = calls.map((call: any) => {
      const isCaller = call.caller_id === userId;
      const otherUser = isCaller ? call.users_video_calls_receiver_idTousers : call.users_video_calls_caller_idTousers;
      const otherProfile = Array.isArray(otherUser?.user_profiles) ? otherUser.user_profiles[0] : otherUser?.user_profiles;
      return {
        id: Number(call.id), direction: isCaller ? 'outgoing' : 'incoming',
        status: call.status, duration: call.duration || 0,
        started_at: call.started_at, ended_at: call.ended_at, created_at: call.created_at,
        other_user: { id: Number(otherUser?.id), name: otherProfile?.display_name || otherUser?.name || 'Unknown', avatar_url: otherProfile?.avatar_url || null }
      };
    });
    res.json({ data, total: data.length });
  } catch (err: any) {
    console.error('[video] history error:', err.message);
    res.json({ data: [], total: 0 });
  }
});

// POST /api/video/initiate
router.post('/initiate', async (req: any, res) => {
  try {
    const callerId = BigInt(req.user.id);
    const { receiver_id } = req.body;
    if (!receiver_id) return res.status(400).json({ error: 'receiver_id is required' });
    const call = await prisma.video_calls.create({
      data: { caller_id: callerId, receiver_id: BigInt(receiver_id), status: 'initiated', started_at: new Date() }
    });
    res.status(201).json({ id: Number(call.id), status: call.status, message: 'Call initiated' });
  } catch (err: any) {
    res.status(500).json({ error: 'Failed to initiate call' });
  }
});

// PUT /api/video/:callId/status
router.put('/:callId/status', async (req: any, res) => {
  try {
    const { status, duration } = req.body;
    if (!['connected', 'missed', 'rejected', 'ended'].includes(status)) return res.status(422).json({ error: 'Invalid status' });
    const call = await prisma.video_calls.findUnique({ where: { id: BigInt(req.params.callId) } });
    if (!call) return res.status(404).json({ error: 'Call not found' });
    const updateData: any = { status };
    if (status === 'ended') { updateData.ended_at = new Date(); updateData.duration = duration || 0; }
    await prisma.video_calls.update({ where: { id: call.id }, data: updateData });
    res.json({ success: true, callId: Number(call.id), status });
  } catch (err: any) {
    res.status(500).json({ error: 'Failed to update status' });
  }
});

export default router;
