import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

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

// GET /api/proximity-chatrooms — list nearby proximity chatrooms
router.get('/', async (req: any, res) => {
  try {
    const { latitude, longitude, radius } = req.query;
    const lat = Number(latitude);
    const lng = Number(longitude);
    const radiusM = Number(radius) || 5000;

    const rooms = await prisma.proximity_chatrooms.findMany({
      where: { is_active: true },
      include: {
        users: { select: { id: true, name: true } },
        _count: { select: { proximity_chatroom_members: true } },
      },
      take: 30,
    });

    // Calculate distance and filter
    let results = rooms.map((r: any) => {
      const roomLat = Number(r.lat);
      const roomLng = Number(r.lng);
      let distanceKm = Infinity;

      if (!isNaN(lat) && !isNaN(lng)) {
        distanceKm = Math.sqrt(
          Math.pow((roomLat - lat) * 111, 2) +
          Math.pow((roomLng - lng) * 111 * Math.cos(lat * Math.PI / 180), 2)
        );
      }

      return {
        id: Number(r.id),
        name: r.name,
        description: r.description,
        lat: roomLat,
        lng: roomLng,
        radius_m: Number(r.radius_m),
        created_by: Number(r.created_by),
        creator_name: r.users?.name || 'Unknown',
        member_count: r._count?.proximity_chatroom_members || 0,
        distance_km: Math.round(distanceKm * 10) / 10,
      };
    });

    // Filter by radius if coordinates provided
    if (!isNaN(lat) && !isNaN(lng)) {
      results = results.filter(r => r.distance_km <= radiusM / 1000);
      results.sort((a, b) => a.distance_km - b.distance_km);
    }

    res.json(serialize(results));
  } catch (error: any) {
    console.error('[ProximityChatrooms] List error:', error.message);
    res.json([]);
  }
});

// GET /api/proximity-chatrooms/conference-pulse
router.get('/conference-pulse', async (req: any, res) => {
  try {
    const rooms = await prisma.proximity_chatrooms.findMany({
      where: { is_active: true },
      include: {
        users: { select: { id: true, name: true } },
        _count: { select: { proximity_chatroom_members: true } },
      },
      orderBy: { created_at: 'desc' },
      take: 10,
    });

    res.json(serialize({
      professionals: [],
      chatrooms: rooms.map((r: any) => ({
        id: Number(r.id),
        name: r.name,
        member_count: r._count?.proximity_chatroom_members || 0,
        creator_name: r.users?.name || 'Unknown',
      })),
      meta: {
        professionals_count: 0,
        chatrooms_count: rooms.length,
      },
    }));
  } catch (error: any) {
    console.error('[ProximityChatrooms] Conference error:', error.message);
    res.json({ professionals: [], chatrooms: [], meta: { professionals_count: 0, chatrooms_count: 0 } });
  }
});

// GET /api/proximity-chatrooms/:id
router.get('/:id', async (req: any, res) => {
  try {
    const roomId = BigInt(req.params.id);

    const room = await prisma.proximity_chatrooms.findUnique({
      where: { id: roomId },
      include: {
        users: { select: { id: true, name: true } },
        proximity_chatroom_members: {
          include: {
            users: { select: { id: true, name: true, avatar_url: true } },
          },
          take: 50,
        },
      },
    });

    if (!room) {
      return res.status(404).json({ message: 'Room not found' });
    }

    res.json(serialize({
      id: room.id,
      name: room.name,
      description: room.description,
      lat: Number(room.lat),
      lng: Number(room.lng),
      radius_m: Number(room.radius_m),
      created_by: Number(room.created_by),
      creator_name: room.users?.name,
      users: room.proximity_chatroom_members.map((m: any) => ({
        id: Number(m.users?.id),
        name: m.users?.name,
        avatar_url: m.users?.avatar_url,
      })),
    }));
  } catch (error: any) {
    console.error('[ProximityChatrooms] Get error:', error.message);
    res.status(500).json({ message: 'Failed to get room' });
  }
});

export default router;
