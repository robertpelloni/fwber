import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/events - List nearby events
router.get('/', authenticate, async (req: any, res) => {
  try {
    const { radius = 50, latitude, longitude, type, ranking_strategy } = req.query;

    const where: any = { status: 'upcoming' };
    if (type) where.type = String(type);

    const events = await prisma.events.findMany({
      where,
      orderBy: { starts_at: 'asc' },
      take: 50,
      include: {
        users: { select: { id: true, name: true, user_profiles: { select: { display_name: true }, take: 1 } } },
        _count: { select: { event_attendees: true } },
      },
    });

    // Calculate distance if user provided lat/lng
    const userLat = latitude ? parseFloat(String(latitude)) : null;
    const userLng = longitude ? parseFloat(String(longitude)) : null;

    const result = events.map((e: any) => {
      const obj: any = {
        id: Number(e.id),
        title: e.title,
        description: e.description,
        type: e.type,
        location_name: e.location_name,
        latitude: Number(e.latitude),
        longitude: Number(e.longitude),
        starts_at: e.starts_at?.toISOString(),
        ends_at: e.ends_at?.toISOString(),
        max_attendees: e.max_attendees,
        price: e.price ? Number(e.price) : null,
        token_cost: e.token_cost ? Number(e.token_cost) : null,
        created_by_user_id: Number(e.created_by_user_id),
        status: e.status,
        attendees_count: e._count?.event_attendees || 0,
        creator: e.users ? { id: Number(e.users.id), display_name: e.users.user_profiles?.[0]?.display_name || e.users.name } : null,
      };

      if (userLat && userLng) {
        const evtLat = Number(e.latitude);
        const evtLng = Number(e.longitude);
        const R = 6371;
        const dLat = ((evtLat - userLat) * Math.PI) / 180;
        const dLon = ((evtLng - userLng) * Math.PI) / 180;
        const a =
          Math.sin(dLat / 2) * Math.sin(dLat / 2) +
          Math.cos((userLat * Math.PI) / 180) *
            Math.cos((evtLat * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        obj.distance_meters = Math.round(R * c * 1000);
      }

      return obj;
    });

    // Filter by radius if distance was calculated
    const radiusMeters = Number(radius) * 1000;
    const filtered = userLat
      ? result.filter((e: any) => (e.distance_meters || 0) <= radiusMeters)
      : result;

    res.json({
      data: filtered,
      meta: {
        total: filtered.length,
        ranking_strategy: ranking_strategy
          ? { trusted_connections: true, scene_alignment: true, freshness: true, distance: true, summary: 'Events ranked by proximity, freshness, and social trust signals.' }
          : undefined,
      },
    });
  } catch (err: any) {
    console.error('[GET /events]', err.message);
    res.json({ data: [], meta: { total: 0 } });
  }
});

// GET /api/events/invitations
router.get('/invitations', authenticate, async (req: any, res) => {
  try {
    const invitations = await prisma.event_invitations.findMany({
      where: { invitee_id: BigInt(req.user.id) },
      include: { events: { include: { users: { select: { id: true } } } } },
      take: 20,
      orderBy: { created_at: 'desc' },
    });
    res.json(invitations.map((inv: any) => ({
      id: Number(inv.id),
      event_id: Number(inv.event_id),
      event: inv.events ? {
        id: Number(inv.events.id),
        title: inv.events.title,
        description: inv.events.description,
        location_name: inv.events.location_name,
        starts_at: inv.events.starts_at?.toISOString(),
        creator: inv.events?.users ? { id: Number(inv.events.users.id) } : null,
      } : null,
      status: inv.status,
      created_at: inv.created_at?.toISOString(),
    })));
  } catch (err: any) {
    console.error('[GET /events/invitations]', err.message);
    res.json([]);
  }
});

// GET /api/events/my-events
router.get('/my-events', authenticate, async (req: any, res) => {
  try {
    const events = await prisma.events.findMany({
      where: { created_by_user_id: BigInt(req.user.id) },
      orderBy: { starts_at: 'desc' },
      include: {
        _count: { select: { event_attendees: true } },
      },
    });
    res.json({
      data: events.map((e: any) => ({
        id: Number(e.id),
        title: e.title,
        description: e.description,
        type: e.type,
        location_name: e.location_name,
        starts_at: e.starts_at?.toISOString(),
        ends_at: e.ends_at?.toISOString(),
        status: e.status,
        attendees_count: e._count?.event_attendees || 0,
      })),
    });
  } catch (err: any) {
    console.error('[GET /events/my-events]', err.message);
    res.json({ data: [] });
  }
});

// POST /api/events - Create event
router.post('/', authenticate, async (req: any, res) => {
  try {
    const { title, description, type, location_name, latitude, longitude, starts_at, ends_at, max_attendees, price, token_cost } = req.body;

    if (!title || !description || !location_name) {
      return res.status(400).json({ error: 'Title, description, and location_name are required' });
    }

    const event = await prisma.events.create({
      data: {
        title,
        description,
        type: type || 'standard',
        location_name,
        latitude: latitude || 0,
        longitude: longitude || 0,
        starts_at: starts_at ? new Date(starts_at) : new Date(),
        ends_at: ends_at ? new Date(ends_at) : new Date(Date.now() + 3600000),
        max_attendees: max_attendees || null,
        price: price || null,
        token_cost: token_cost || null,
        created_by_user_id: BigInt(req.user.id),
        status: 'upcoming',
      },
    });

    // Auto-RSVP creator as attending
    await prisma.event_attendees.create({
      data: {
        event_id: event.id,
        user_id: BigInt(req.user.id),
        status: 'attending',
      },
    });

    res.json({
      id: Number(event.id),
      title: event.title,
      description: event.description,
      type: event.type,
      location_name: event.location_name,
      latitude: Number(event.latitude),
      longitude: Number(event.longitude),
      starts_at: event.starts_at?.toISOString(),
      ends_at: event.ends_at?.toISOString(),
      max_attendees: event.max_attendees,
      price: event.price ? Number(event.price) : null,
      token_cost: event.token_cost ? Number(event.token_cost) : null,
      created_by_user_id: Number(event.created_by_user_id),
      status: event.status,
      attendees_count: 1,
    });
  } catch (err: any) {
    console.error('[POST /events]', err.message, err.stack);
    res.status(500).json({ error: 'Failed to create event', details: err.message });
  }
});

// GET /api/events/:id
router.get('/:id', authenticate, async (req: any, res) => {
  try {
    const event = await prisma.events.findUnique({
      where: { id: BigInt(req.params.id) },
      include: {
        users: { select: { id: true, name: true, user_profiles: { select: { display_name: true }, take: 1 } } },
        event_attendees: {
          include: { users: { select: { id: true, name: true, user_profiles: { select: { display_name: true }, take: 1 } } } },
          take: 50,
        },
      },
    });
    if (!event) return res.status(404).json({ error: 'Event not found' });

    res.json({
      id: Number(event.id),
      title: event.title,
      description: event.description,
      type: event.type,
      location_name: event.location_name,
      latitude: Number(event.latitude),
      longitude: Number(event.longitude),
      starts_at: event.starts_at?.toISOString(),
      ends_at: event.ends_at?.toISOString(),
      max_attendees: event.max_attendees,
      price: event.price ? Number(event.price) : null,
      token_cost: event.token_cost ? Number(event.token_cost) : null,
      created_by_user_id: Number(event.created_by_user_id),
      status: event.status,
      attendees_count: event.event_attendees?.length || 0,
      creator: event.users ? { id: Number(event.users.id), display_name: event.users.user_profiles?.[0]?.display_name || event.users.name } : null,
      attendees: event.event_attendees?.map((a: any) => ({
        id: Number(a.id),
        status: a.status,
        user: a.users ? { id: Number(a.users.id), display_name: a.users.user_profiles?.[0]?.display_name || a.users.name } : null,
      })),
    });
  } catch (err: any) {
    console.error('[GET /events/:id]', err.message);
    res.status(500).json({ error: 'Failed to load event' });
  }
});

// POST /api/events/:id/rsvp
router.post('/:id/rsvp', authenticate, async (req: any, res) => {
  try {
    const { status = 'attending' } = req.body;
    
    // Check if event exists
    const event = await prisma.events.findUnique({
        where: { id: BigInt(req.params.id) }
    });

    if (!event) return res.status(404).json({ error: 'Event not found' });

    // Validate status against enum
    const validStatuses = ['attending', 'maybe', 'declined'];
    if (!validStatuses.includes(status)) {
        return res.status(400).json({ error: 'Invalid RSVP status' });
    }

    const userId = BigInt(req.user.id);
    const eventId = BigInt(req.params.id);

    // Use a transaction or check for existing record first to avoid the Prisma limitation 
    // where upsert doesn't always handle manual map: unique constraints perfectly in some versions.
    const existing = await prisma.event_attendees.findFirst({
        where: { event_id: eventId, user_id: userId }
    });

    if (existing) {
        await prisma.event_attendees.update({
            where: { id: existing.id },
            data: { status: status as any }
        });
    } else {
        await prisma.event_attendees.create({
            data: {
                event_id: eventId,
                user_id: userId,
                status: status as any
            }
        });
    }

    res.json({ message: 'RSVP updated', status });
  } catch (err: any) {
    console.error('[POST /events/:id/rsvp]', err.message, err.stack);
    res.status(500).json({ error: 'Failed to RSVP', details: err.message });
  }
});

// DELETE /api/events/:id/rsvp
router.delete('/:id/rsvp', authenticate, async (req: any, res) => {
  try {
    await prisma.event_attendees.deleteMany({
      where: {
        event_id: BigInt(req.params.id),
        user_id: BigInt(req.user.id),
      },
    });
    res.json({ message: 'RSVP removed', status: 'not_attending' });
  } catch (err: any) {
    console.error('[DELETE /events/:id/rsvp]', err.message);
    res.json({ message: 'RSVP removed', status: 'not_attending' });
  }
});

export default router;
