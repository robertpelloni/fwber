import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { checkAndUnlockAchievements } from '../lib/achievements.js';

const router = Router();

// GET /api/gifts - Get all gifts (summary)
const GIFT_EMOJIS: Record<string, string> = {
  coffee: '☕', rose: '🌹', cocktail: '🍹', teddy: '🧸',
  rocket: '🚀', diamond: '💎', default: '🎁',
};

function giftEmoji(gift: any): string {
  const name = (gift.name || '').toLowerCase();
  for (const [key, emoji] of Object.entries(GIFT_EMOJIS)) {
    if (name.includes(key)) return emoji as string;
  }
  return GIFT_EMOJIS.default!;
}

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
      data: available,
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

// GET /api/gifts/received - Get gifts received by current user
router.get('/received', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const received = await prisma.user_gifts.findMany({
      where: { receiver_id: userId },
      include: {
        gifts: true,
        users_user_gifts_sender_idTousers: {
          select: { name: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } },
        },
      },
      orderBy: { created_at: 'desc' },
      take: 50,
    });
    res.json(received.map((g: any) => {
      const sender = g.users_user_gifts_sender_idTousers;
      const senderProfile = Array.isArray(sender?.user_profiles) ? sender.user_profiles[0] : sender?.user_profiles;
      return {
        id: Number(g.id),
        gift_id: Number(g.gift_id),
        gift_name: g.gifts?.name || 'Gift',
        gift_emoji: giftEmoji(g.gifts),
        cost_at_time: Number(g.cost_at_time) || 0,
        message: g.message || '',
        from_user_id: Number(g.sender_id),
        from_name: senderProfile?.display_name || sender?.name || 'Anonymous',
        from_avatar_url: senderProfile?.avatar_url || null,
        created_at: g.created_at ? new Date(g.created_at).toISOString() : null,
      };
    }));
  } catch (error: any) {
    console.error('[Gifts] Received error:', error.message);
    res.json([]);
  }
});

// GET /api/gifts/sent - Get gifts sent by current user
router.get('/sent', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const sent = await prisma.user_gifts.findMany({
      where: { sender_id: userId },
      include: {
        gifts: true,
        users_user_gifts_receiver_idTousers: {
          select: { name: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } },
        },
      },
      orderBy: { created_at: 'desc' },
      take: 50,
    });
    res.json(sent.map((g: any) => {
      const receiver = g.users_user_gifts_receiver_idTousers;
      const receiverProfile = Array.isArray(receiver?.user_profiles) ? receiver.user_profiles[0] : receiver?.user_profiles;
      return {
        id: Number(g.id),
        gift_id: Number(g.gift_id),
        gift_name: g.gifts?.name || 'Gift',
        gift_emoji: giftEmoji(g.gifts),
        cost_at_time: Number(g.cost_at_time) || 0,
        message: g.message || '',
        to_user_id: Number(g.receiver_id),
        to_name: receiverProfile?.display_name || receiver?.name || 'Anonymous',
        to_avatar_url: receiverProfile?.avatar_url || null,
        created_at: g.created_at ? new Date(g.created_at).toISOString() : null,
      };
    }));
  } catch (error: any) {
    console.error('[Gifts] Sent error:', error.message);
    res.json([]);
  }
});

// GET /api/gifts/available - Get available gifts to send
router.get('/available', authenticate, async (req: any, res) => {
  try {
    const gifts = await prisma.gifts.findMany({ where: { is_active: true } });
    res.json({
      gifts: gifts.map((g: any) => ({
        ...g,
        id: Number(g.id),
        cost: Number(g.cost),
        emoji: giftEmoji(g),
      })),
    });
  } catch (err) {
    res.json({ gifts: [] });
  }
});

// POST /api/gifts/send - Send a gift
router.post('/send', authenticate, async (req: any, res) => {
  try {
    const { recipient_id, receiver_id, gift_id, gift_type, message } = req.body;
    const senderId = BigInt(req.user.id);
    const receiverId = BigInt(recipient_id || receiver_id);

    // Find gift by ID or by type/name
    let gift: any;
    if (gift_id) {
      gift = await prisma.gifts.findUnique({ where: { id: BigInt(gift_id) } });
    } else if (gift_type) {
      const nameMap: Record<string, string> = {
        coffee: "Coffee", rose: "Rose", cocktail: "Cocktail",
        teddy: "Teddy Bear", teddy_bear: "Teddy Bear",
        rocket: "Rocket", diamond: "Diamond",
      };
      const giftName = nameMap[gift_type.toLowerCase()] || gift_type;
      gift = await prisma.gifts.findFirst({ where: { name: giftName } });
    }
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
          gift_id: gift.id,
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

    // Check achievements (first gift, gift milestones)
    checkAndUnlockAchievements(senderId).catch(() => {});

    res.json({ success: true, gift: userGift });
  } catch (error: any) {
    console.error('[Gifts] Error sending:', error.message);
    res.status(500).json({ error: 'Failed to send gift' });
  }
});

export default router;
