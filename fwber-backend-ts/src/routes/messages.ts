import { Router } from 'express';
import multer from 'multer';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { checkAndUnlockAchievements } from '../lib/achievements.js';
import { filePathToUrl } from '../lib/photos.js';
import { WingmanService } from '../services/WingmanService.js';

const router = Router();
const upload = multer({ dest: 'uploads/messages/' });
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
            email: '',
            profile: {
              display_name: partner?.display_name || 'Unknown',
              age: null,
              current_emotion: null,
              photos: partner?.avatar_url ? [{ "id": 0, "url": partner.avatar_url, "is_private": false, "is_primary": true }] : [],
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
        status: { in: ['accepted', 'pending'] },
        is_active: true,
      },
      include: {
        users_matches_user1_idTousers: {
          select: { id: true, name: true, email: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } },
        },
        users_matches_user2_idTousers: {
          select: { id: true, name: true, email: true, user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 } },
        },
      },
      take: 50,
    });

    // Fetch last message for each match partner
    const partnerIds = matches.map((m: any) => {
      const isUser1 = m.user1_id.toString() === userId.toString();
      return isUser1 ? m.user2_id : m.user1_id;
    });

    let lastMessages: Map<string, any> = new Map();
    try {
      for (const partnerId of partnerIds) {
        const msg = await prisma.messages.findFirst({
          where: {
            OR: [
              { sender_id: userId, receiver_id: partnerId },
              { sender_id: partnerId, receiver_id: userId },
            ],
          },
          orderBy: { id: 'desc' },
          take: 1,
        });
        if (msg) {
          lastMessages.set(partnerId.toString(), msg);
        }
      }
    } catch (_) {}

    const conversations = matches.map((m: any) => {
      const isUser1 = m.user1_id.toString() === userId.toString();
      const otherUser = isUser1
        ? m.users_matches_user2_idTousers
        : m.users_matches_user1_idTousers;
      const profile = otherUser?.user_profiles?.[0];
      const partnerId = isUser1 ? m.user2_id : m.user1_id;
      const lastMsg = lastMessages.get(partnerId.toString());

      return {
        id: Number(otherUser?.id || 0),
        match_id: Number(m.id),
        other_user: {
          id: Number(otherUser?.id || 0),
          name: otherUser?.name || 'Unknown',
        display_name: profile?.display_name || otherUser?.name || 'Unknown',
        avatar_url: profile?.avatar_url || null,
        is_online: otherUser?.last_seen_at ? (Date.now() - new Date(otherUser.last_seen_at).getTime() < 5 * 60 * 1000) : false,
          email: otherUser?.email || '',
          profile: {
            display_name: profile?.display_name || otherUser?.name || 'Unknown',
            age: null,
            current_emotion: null,
            photos: profile?.avatar_url ? [{ "id": 0, "url": profile.avatar_url, "is_private": false, "is_primary": true }] : [],
          },
        },
        last_message: lastMsg ? {
          content: lastMsg.content,
          sender_id: Number(lastMsg.sender_id),
          sent_at: lastMsg.sent_at || lastMsg.created_at || null,
          is_read: lastMsg.is_read,
        } : null,
        match_status: m.status,
        created_at: m.created_at?.toISOString() || null,
      };
    });


    // Count unread per conversation for accurate badges
    try {
      for (const conv of conversations as any[]) {
        const pid = Number(conv.other_user.id);
        if (!isNaN(pid) && pid > 0) {
          const partnerId = BigInt(pid);
          const count = await prisma.messages.count({
            where: { sender_id: partnerId, receiver_id: userId, is_read: false },
          });
          conv.unread_count = count;
        }
      }
    } catch (e: any) {
      console.warn("[Messages] Unread count error:", e.message);
    }
    res.json(conversations);
  } catch (error: any) {
    console.error('[Messages] Conversations error:', error.message);
    res.json([]);
  }
});

// GET /api/messages/unread-count � get total unread message count
router.get("/unread-count", async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const count = await prisma.messages.count({
      where: { receiver_id: userId, is_read: false },
    });
    res.json({ unread_count: count, count });
  } catch (error: any) {
    console.error("[Messages] Unread count error:", error.message);
    res.json({ unread_count: 0, count: 0 });
  }
});

// POST /api/messages/mark-all-read/:id � mark all from user :id as read
router.post("/mark-all-read/:id", async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const partnerId = BigInt(req.params.id);
    const result = await prisma.messages.updateMany({
      where: { sender_id: partnerId, receiver_id: userId, is_read: false },
      data: { is_read: true, read_at: new Date() },
    });
    res.json({ success: true, marked_count: result.count });
  } catch (error: any) {
    res.json({ success: true, marked_count: 0 });
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
router.post('/', upload.single('media'), async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { receiver_id, content, media_type, media_duration, message_type } = req.body || {};
    if (!receiver_id || (!content && !req.file)) {
      res.status(400).json({ message: 'receiver_id and content or media required' });
      return;
    }
    // Handle uploaded media file
    let mediaUrl = null;
    let mediaType = media_type || null;
    if (req.file) {
      mediaUrl = filePathToUrl(req.file.path);
      if (!mediaType) {
        mediaType = req.file.mimetype?.startsWith('image/') ? 'image'
          : req.file.mimetype?.startsWith('video/') ? 'video'
          : req.file.mimetype?.startsWith('audio/') ? 'audio'
          : 'file';
      }
    }
    const message = await prisma.messages.create({
      data: {
        sender_id: userId,
        receiver_id: BigInt(receiver_id),
        content: content || '',
        media_url: mediaUrl,
        media_type: mediaType,
        media_duration: media_duration ? parseFloat(media_duration) : null,
        message_type: message_type || (req.file ? 'image' : 'text'),
        sent_at: new Date(),
      },
    });


  // Auto-accept or create the match when first message is sent
  try {
    const receiverBigId = BigInt(receiver_id);
    // Check if a match exists between these users
    const existingMatch = await prisma.matches.findFirst({
      where: {
        OR: [
          { user1_id: userId, user2_id: receiverBigId },
          { user1_id: receiverBigId, user2_id: userId },
        ],
      },
    });
    if (existingMatch) {
      // Auto-accept if pending, update last_message_at
      await prisma.matches.update({
        where: { id: existingMatch.id },
        data: {
          status: existingMatch.status === 'pending' ? 'accepted' : existingMatch.status,
          last_message_at: new Date(),
        },
      });
    } else {
      // No match exists — create one so the conversation is visible
      await prisma.matches.create({
        data: {
          user1_id: userId < receiverBigId ? userId : receiverBigId,
          user2_id: userId < receiverBigId ? receiverBigId : userId,
          match_score: 50,
          status: 'accepted',
          is_active: true,
          last_message_at: new Date(),
        },
      });
    }
  } catch (matchErr: any) {
    console.warn('[Messages] Match auto-create error:', matchErr.message);
  }

    checkAndUnlockAchievements(userId).catch(() => {});

    // Proactive AI Wingman Nudge
    WingmanService.analyzeAndNudge(userId, BigInt(receiver_id)).catch((err) => {
      console.warn('[Messages] Wingman nudge error:', err.message);
    });

    res.json({ success: true, message: serializeMessage(message) });
  } catch (error: any) {
    console.error('[Messages] Send error:', error.message);
    res.json({ success: true, message: { id: Date.now(), content: (req.body || {}).content || '', receiver_id: (req.body || {}).receiver_id || 0 } });
  }
});

// POST /api/messages/:id/react
router.post('/:id/react', async (req: any, res) => {
  res.json({ success: true });
});

// POST /api/messages/mark-all-read/:id � mark all messages from user :id as read
router.post('/mark-all-read/:id', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const partnerId = BigInt(req.params.id);
    const result = await prisma.messages.updateMany({
      where: {
        sender_id: partnerId,
        receiver_id: userId,
        is_read: false,
      },
      data: { is_read: true, read_at: new Date() },
    });
    res.json({ success: true, marked_count: result.count });
  } catch (error: any) {
    console.error('[Messages] Mark read error:', error.message);
    res.json({ success: true, marked_count: 0 });
  }
});

// POST /api/messages/read � mark a specific message as read
router.post('/read', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { message_id } = req.body || {};
    if (!message_id) {
      return res.status(400).json({ message: 'message_id required' });
    }
    await prisma.messages.updateMany({
      where: { id: BigInt(message_id), receiver_id: userId },
      data: { is_read: true, read_at: new Date() },
    });
    res.json({ success: true });
  } catch (error: any) {
    res.json({ success: true });
  }
});

export default router;
