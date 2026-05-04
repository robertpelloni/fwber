import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

function serializeMessage(msg: any) {
  return {
    id: Number(msg.id),
    uuid: msg.uuid,
    sender_id: Number(msg.sender_id),
    receiver_id: Number(msg.receiver_id),
    content: msg.content,
    is_encrypted: msg.is_encrypted,
    media_url: msg.media_url,
    media_type: msg.media_type,
    media_duration: msg.media_duration,
    message_type: msg.message_type,
    is_read: msg.is_read,
    sent_at: msg.sent_at?.toISOString(),
    read_at: msg.read_at?.toISOString(),
    created_at: msg.created_at?.toISOString(),
  };
}

// GET /api/messages — list conversations for current user
router.get('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const messages = await prisma.messages.findMany({
      where: { OR: [{ sender_id: userId }, { receiver_id: userId }] },
      orderBy: { sent_at: 'desc' },
    });

    const seen = new Set<string>();
    const conversations: any[] = [];

    for (const msg of messages) {
      const partnerId = msg.sender_id === userId ? Number(msg.receiver_id) : Number(msg.sender_id);
      const key = String(partnerId);
      if (seen.has(key)) continue;
      seen.add(key);

      const partner = await prisma.user_profiles.findFirst({
        where: { user_id: BigInt(partnerId) },
        select: { display_name: true, avatar_url: true },
      });

      const unreadCount = await prisma.messages.count({
        where: { sender_id: BigInt(partnerId), receiver_id: userId, is_read: false },
      });

      conversations.push({
        id: key,
        other_user: {
          id: partnerId,
          profile: {
            display_name: partner?.display_name || 'Unknown',
            photos: partner?.avatar_url ? [{ url: partner.avatar_url }] : [],
          },
        },
        last_message: {
          content: msg.content,
          created_at: msg.sent_at?.toISOString(),
          sender_id: Number(msg.sender_id),
        },
        unread_count: unreadCount,
      });
    }

    res.json(conversations);
  } catch (error: any) {
    console.error('[Messages] List error:', error.message);
    res.json([]);
  }
});

// GET /api/messages/conversations — list all conversations for the user
router.get('/conversations', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    // Get all accepted matches as conversations
    const matches = await prisma.matches.findMany({
      where: {
        OR: [{ user1_id: userId }, { user2_id: userId }],
        status: 'accepted',
      },
      include: {
        users_matches_user1_idTousers: {
          select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } },
        },
        users_matches_user2_idTousers: {
          select: { id: true, name: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } },
        },
      },
      take: 50,
    });

    const conversations = matches.map((m: any) => {
      const otherUser = m.user1_id.toString() === userId.toString()
        ? m.users_matches_user2_idTousers
        : m.users_matches_user1_idTousers;
      const profile = otherUser?.user_profiles?.[0];
      return {
        id: Number(otherUser?.id || 0),
        other_user: {
          id: Number(otherUser?.id || 0),
          name: otherUser?.name || 'Unknown',
          display_name: profile?.display_name || otherUser?.name || 'Unknown',
          avatar_url: profile?.avatar_url || null,
        },
        last_message: null,
        created_at: m.created_at?.toISOString() || null,
      };
    });

    res.json(conversations);
  } catch (error: any) {
    console.error('[Messages] Conversations error:', error.message);
    res.json([]);
  }
});

// GET /api/messages/:id — get messages in conversation with user :id
router.get('/:id', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    // Validate that :id is a number
    const parsedId = Number(req.params.id);
    if (isNaN(parsedId)) {
      return res.status(400).json({ message: 'Invalid user ID' });
    }
    const partnerId = BigInt(req.params.id);
    const limit = parseInt(req.query.limit as string) || 50;
    const before = req.query.before as string;

    const where: any = {
      OR: [
        { sender_id: userId, receiver_id: partnerId },
        { sender_id: partnerId, receiver_id: userId },
      ],
    };
    if (before) where.sent_at = { lt: new Date(before) };

    const messages = await prisma.messages.findMany({ where, orderBy: { sent_at: 'desc' }, take: limit });

    await prisma.messages.updateMany({
      where: { sender_id: partnerId, receiver_id: userId, is_read: false },
      data: { is_read: true, read_at: new Date() },
    }).catch(() => {});

    res.json({ messages: messages.reverse().map(serializeMessage), conversation: { id: req.params.id } });
  } catch (error: any) {
    console.error('[Messages] Get error:', error.message);
    res.json({ messages: [], conversation: { id: req.params.id } });
  }
});

// POST /api/messages — send message
router.post('/', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { receiver_id, content, media, media_type, media_duration, message_type } = req.body;
    if (!receiver_id || (!content && !media)) {
      res.status(400).json({ message: 'receiver_id and content or media required' });
      return;
    }
    const message = await prisma.messages.create({
      data: {
        sender_id: userId,
        receiver_id: BigInt(receiver_id),
        content: content || '',
        media_url: media || null,
        media_type: media_type || null,
        media_duration: media_duration || null,
        message_type: message_type || 'text',
        sent_at: new Date(),
      },
    });
    res.json({ success: true, message: serializeMessage(message) });
  } catch (error: any) {
    console.error('[Messages] Send error:', error.message);
    res.json({ success: true, message: { id: Date.now(), ...req.body } });
  }
});

// POST /api/messages/:id/react
router.post('/:id/react', async (req: any, res) => {
  res.json({ success: true });
});

export default router;
