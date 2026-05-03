import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/gifts - Get all gifts (summary)
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const received = await prisma.user_gifts.findMany({
      where: { receiver_id: userId },
      include: { gifts: true, users_user_gifts_sender_idTousers: { select: { name: true } } }
    });
    const sent = await prisma.user_gifts.findMany({
      where: { sender_id: userId },
      include: { gifts: true, users_user_gifts_receiver_idTousers: { select: { name: true } } }
    });
    const available = await prisma.gifts.findMany({ where: { is_active: true } });

    res.json({
      received: received.map(g => ({
        id: Number(g.id),
        gift: g.gifts,
        from: (g as any).users_user_gifts_sender_idTousers?.name,
        created_at: g.created_at
      })),
      sent: sent.map(g => ({
        id: Number(g.id),
        gift: g.gifts,
        to: (g as any).users_user_gifts_receiver_idTousers?.name,
        created_at: g.created_at
      })),
      available
    });
  } catch (err) {
    res.json({ received: [], sent: [], available: [] });
  }
});

// GET /api/gifts/available - Get available gifts to send
router.get('/available', authenticate, async (req: any, res) => {
  try {
    const gifts = await prisma.gifts.findMany({ where: { is_active: true } });
    res.json({ gifts });
  } catch (err) {
    res.json({ gifts: [] });
  }
});

// POST /api/gifts/send - Send a gift
router.post('/send', authenticate, async (req: any, res) => {
  try {
    const { recipient_id, gift_id, message } = req.body;
    const senderId = BigInt(req.user.id);
    const receiverId = BigInt(recipient_id);
    const giftId = BigInt(gift_id);

    const gift = await prisma.gifts.findUnique({ where: { id: giftId } });
    if (!gift) return res.status(404).json({ error: 'Gift not found' });

    // Check balance
    const sender = await prisma.users.findUnique({ where: { id: senderId }, select: { token_balance: true } });
    if (!sender || Number(sender.token_balance || 0) < gift.cost) {
      return res.status(400).json({ error: 'Insufficient token balance' });
    }

    // Transactional send
    const [userGift] = await prisma.$transaction([
      prisma.user_gifts.create({
        data: {
          sender_id: senderId,
          receiver_id: receiverId,
          gift_id: giftId,
          message: message || '',
          cost_at_time: gift.cost,
        }
      }),
      prisma.users.update({
        where: { id: senderId },
        data: { token_balance: { decrement: gift.cost } }
      }),
      prisma.users.update({
        where: { id: receiverId },
        data: { token_balance: { increment: Math.floor(gift.cost * 0.8) } } // Recipient gets 80% value
      })
    ]);

    res.json({ success: true, gift: userGift });
  } catch (error: any) {
    console.error('[Gifts] Error sending:', error.message);
    res.status(500).json({ error: 'Failed to send gift' });
  }
});

export default router;
