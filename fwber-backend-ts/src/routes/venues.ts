import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

function serialize(obj: any): any {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (Array.isArray(obj)) return obj.map(serialize);
  if (typeof obj === 'object') {
    // Detect Prisma Decimal by {s, e, d} signature
    if (obj.s !== undefined && obj.e !== undefined && Array.isArray(obj.d)) {
      try {
        const parts: string[] = [];
        for (let i = 0; i < obj.d.length; i++) {
          const d = String(obj.d[i]);
          parts.push(i === 0 ? d : d.padStart(7, '0'));
        }
        const digitStr = parts.join('');
        const intDigits = obj.e + 1;
        const sign = obj.s === -1 ? '-' : '';
        if (digitStr.length <= intDigits) {
          return parseFloat(sign + digitStr + '0'.repeat(intDigits - digitStr.length));
        }
        return parseFloat(sign + digitStr.slice(0, intDigits) + '.' + digitStr.slice(intDigits));
      } catch { return 0; }
    }
    if (obj instanceof Date) return obj.toISOString();
    const out: any = {};
    for (const key of Object.keys(obj)) out[key] = serialize(obj[key]);
    return out;
  }
  return obj;
}

// Helper: parse JSON fields
function parseJsonField(val: any): any {
  if (!val) return null;
  if (typeof val === 'object') return val;
  try { return JSON.parse(val); } catch { return val; }
}

// GET /api/venues — search venues by location
router.get('/', authenticate, async (req: any, res) => {
  try {
    const lat = Number(req.query.lat) || 42.3314;
    const lng = Number(req.query.lng) || -83.0458;
    const radius = Number(req.query.radius) || 50; // km
    const type = req.query.type as string | undefined;

    const where: any = { is_active: true };
    if (type) where.business_type = type;

    const venues = await prisma.venues.findMany({
      where,
      orderBy: { name: 'asc' },
      take: 50,
    });

    // Calculate distance and filter by radius
    const R = 6371; // Earth radius in km
    const result = venues.map((v: any) => {
      const vLat = Number(v.latitude || 0);
      const vLng = Number(v.longitude || 0);
      const dLat = ((vLat - lat) * Math.PI) / 180;
      const dLon = ((vLng - lng) * Math.PI) / 180;
      const a = Math.sin(dLat / 2) ** 2 +
        Math.cos((lat * Math.PI) / 180) * Math.cos((vLat * Math.PI) / 180) *
        Math.sin(dLon / 2) ** 2;
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      const distanceKm = R * c;

      const s = serialize(v);
      return {
        ...s,
        operating_hours: parseJsonField(v.operating_hours),
        features: parseJsonField(v.features),
        distance_km: Math.round(distanceKm * 10) / 10,
      };
    }).filter((v: any) => v.distance_km <= radius)
      .sort((a: any, b: any) => a.distance_km - b.distance_km);

    res.json({
      venues: result,
      total: result.length,
      center: { lat, lng },
    });
  } catch (error: any) {
    console.error('[Venues] List error:', error.message);
    res.json({ venues: [], total: 0 });
  }
});

// GET /api/venues/:id — get venue details
router.get('/:id', authenticate, async (req: any, res) => {
  try {
    const venueId = BigInt(req.params.id);
    const venue = await prisma.venues.findUnique({
      where: { id: venueId },
    });
    if (!venue) {
      return res.status(404).json({ message: 'Venue not found' });
    }
    const s = serialize(venue);
    res.json({
      venue: {
        ...s,
        operating_hours: parseJsonField(venue.operating_hours),
        features: parseJsonField(venue.features),
      },
    });
  } catch (error: any) {
    console.error('[Venues] Get error:', error.message);
    res.json({ venue: null });
  }
});

export default router;
