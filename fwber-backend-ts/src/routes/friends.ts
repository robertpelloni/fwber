import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';
import { createNotification } from './notifications.js';
import { checkAndUnlockAchievements } from '../lib/achievements.js';

const router = Router();

// Helper: serialize BigInt values
function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (obj instanceof Date) return obj;
  if (Array.isArray(obj)) return obj.map((v: any) => serialize(v));
  if (typeof obj === 'object') {
    const out: any = {};
    for (const key of Object.keys(obj)) {
      out[key] = serialize(obj[key]);
    }
    return out;
  }
  return obj;
}

// GET /api/friends — list accepted friends
router.get('/', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    const friendships = await prisma.friends.findMany({
      where: {
        OR: [{ user_id: userId }, { friend_id: userId }],
        status: 'accepted',
      },
      include: {
        users_friends_user_idTousers: {
          select: {
            id: true,
            name: true,
            user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 },
          },
        },
        users_friends_friend_idTousers: {
          select: {
            id: true,
            name: true,
            user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 },
          },
        },
      },
      orderBy: { created_at: 'desc' },
    });

    const friends = friendships.map((f: any) => {
      const isRequester = f.user_id.toString() === userId.toString();
      const otherUser = isRequester
        ? f.users_friends_friend_idTousers
        : f.users_friends_user_idTousers;
      const profile = Array.isArray(otherUser?.user_profiles)
        ? otherUser.user_profiles[0]
        : otherUser?.user_profiles;

      return {
        id: Number(otherUser?.id || 0),
        name: otherUser?.name || 'Unknown',
        display_name: profile?.display_name || otherUser?.name || 'Unknown',
        avatar_url: profile?.avatar_url || null,
        friends_since: f.created_at,
      };
    });

    res.json({ data: serialize(friends) });
  } catch (error: any) {
    console.error('[Friends] List error:', error.message);
    res.json({ data: [] });
  }
});

// GET /api/friends/requests — list pending friend requests
router.get('/requests', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);

    // Incoming requests (people who want to be friends with me)
    const incoming = await prisma.friend_requests.findMany({
      where: { friend_id: userId, status: 'pending' },
      include: {
        users_friend_requests_user_idTousers: {
          select: {
            id: true,
            name: true,
            user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 },
          },
        },
      },
      orderBy: { created_at: 'desc' },
    });

    // Outgoing requests (people I want to be friends with)
    const outgoing = await prisma.friend_requests.findMany({
      where: { user_id: userId, status: 'pending' },
      include: {
        users_friend_requests_friend_idTousers: {
          select: {
            id: true,
            name: true,
            user_profiles: { select: { display_name: true, avatar_url: true }, take: 1 },
          },
        },
      },
      orderBy: { created_at: 'desc' },
    });

    const serializeRequest = (req: any, type: 'incoming' | 'outgoing') => {
      const user = type === 'incoming'
        ? req.users_friend_requests_user_idTousers
        : req.users_friend_requests_friend_idTousers;
      const profile = Array.isArray(user?.user_profiles)
        ? user.user_profiles[0]
        : user?.user_profiles;

      return {
        id: Number(req.id),
        type,
        user: {
          id: Number(user?.id || 0),
          name: user?.name || 'Unknown',
          display_name: profile?.display_name || user?.name || 'Unknown',
          avatar_url: profile?.avatar_url || null,
        },
        created_at: req.created_at,
      };
    };

    res.json({
      data: serialize([
        ...incoming.map((r: any) => serializeRequest(r, 'incoming')),
        ...outgoing.map((r: any) => serializeRequest(r, 'outgoing')),
      ]),
    });
  } catch (error: any) {
    console.error('[Friends] Requests error:', error.message);
    res.json({ data: [] });
  }
});

// POST /api/friends/request — send a friend request
router.post('/request', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { friend_id } = req.body || {};

    if (!friend_id) {
      return res.status(400).json({ message: 'friend_id is required' });
    }

    const friendId = BigInt(friend_id);

    if (userId === friendId) {
      return res.status(400).json({ message: 'Cannot send friend request to yourself' });
    }

    // Check target user exists
    const targetUser = await prisma.users.findUnique({ where: { id: friendId } });
    if (!targetUser) {
      return res.status(404).json({ message: 'User not found' });
    }

    // Check if already friends or request exists
    const existingFriend = await prisma.friends.findFirst({
      where: {
        OR: [
          { user_id: userId, friend_id: friendId },
          { user_id: friendId, friend_id: userId },
        ],
      },
    });
    if (existingFriend) {
      return res.status(400).json({ message: 'Already friends or friendship pending' });
    }

    const existingRequest = await prisma.friend_requests.findFirst({
      where: {
        OR: [
          { user_id: userId, friend_id: friendId },
          { user_id: friendId, friend_id: userId },
        ],
        status: 'pending',
      },
    });
    if (existingRequest) {
      return res.status(400).json({ message: 'Friend request already pending' });
    }

    await prisma.friend_requests.create({
      data: {
        user_id: userId,
        friend_id: friendId,
        status: 'pending',
      },
    });

    // Notify the recipient
    await createNotification(friendId, 'New Friend Request',
      `${(await prisma.users.findUnique({ where: { id: userId }, select: { name: true } }))?.name || 'Someone'} wants to be your friend!`);

    res.json({ success: true, message: 'Friend request sent' });
  } catch (error: any) {
    console.error('[Friends] Request error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to send friend request' });
  }
});

// POST /api/friends/accept/:id — accept a friend request
router.post('/accept/:id', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const requestId = BigInt(req.params.id);

    const friendRequest = await prisma.friend_requests.findUnique({
      where: { id: requestId },
    });

    if (!friendRequest) {
      return res.status(404).json({ message: 'Friend request not found' });
    }

    if (friendRequest.friend_id !== userId) {
      return res.status(403).json({ message: 'Not authorized to accept this request' });
    }

    if (friendRequest.status !== 'pending') {
      return res.status(400).json({ message: 'Request is no longer pending' });
    }

    // Update request status
    await prisma.friend_requests.update({
      where: { id: requestId },
      data: { status: 'accepted' },
    });

    // Create bidirectional friendship
    await prisma.friends.create({
      data: {
        user_id: friendRequest.user_id,
        friend_id: friendRequest.friend_id,
        status: 'accepted',
      },
    });

    // Notify the requester
    await createNotification(friendRequest.user_id, 'Friend Request Accepted',
      `${(await prisma.users.findUnique({ where: { id: userId }, select: { name: true } }))?.name || 'Someone'} accepted your friend request!`);

    
    // Check achievements (first friend)
    checkAndUnlockAchievements(userId).catch(() => {});
    checkAndUnlockAchievements(BigInt(req.params.id)).catch(() => {});
res.json({ success: true, message: 'Friend request accepted' });
  } catch (error: any) {
    console.error('[Friends] Accept error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to accept friend request' });
  }
});

// POST /api/friends/reject/:id — reject a friend request
router.post('/reject/:id', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const requestId = BigInt(req.params.id);

    const friendRequest = await prisma.friend_requests.findUnique({
      where: { id: requestId },
    });

    if (!friendRequest) {
      return res.status(404).json({ message: 'Friend request not found' });
    }

    if (friendRequest.friend_id !== userId) {
      return res.status(403).json({ message: 'Not authorized to reject this request' });
    }

    await prisma.friend_requests.update({
      where: { id: requestId },
      data: { status: 'rejected' },
    });

    res.json({ success: true, message: 'Friend request rejected' });
  } catch (error: any) {
    console.error('[Friends] Reject error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to reject friend request' });
  }
});

// DELETE /api/friends/:id — remove a friend
router.delete('/:id', authenticate, async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const friendId = BigInt(req.params.id);

    // Remove bidirectional friendship
    const deleted = await prisma.friends.deleteMany({
      where: {
        OR: [
          { user_id: userId, friend_id: friendId },
          { user_id: friendId, friend_id: userId },
        ],
      },
    });

    if (deleted.count === 0) {
      return res.status(404).json({ message: 'Friendship not found' });
    }

    res.json({ success: true, message: 'Friend removed' });
  } catch (error: any) {
    console.error('[Friends] Delete error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to remove friend' });
  }
});

export default router;
