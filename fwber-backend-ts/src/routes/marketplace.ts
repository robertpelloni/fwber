import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/marketplace/nearby — nearby marketplace items from approved merchants
router.get('/nearby', authenticate, async (req: any, res) => {
  try {
    const lat = Number(req.query.lat) || 42.33;
    const lng = Number(req.query.lng) || -83.05;
    const radiusM = Number(req.query.radius_m) || 50000;
    const page = Number(req.query.page) || 1;
    const limit = Math.min(Number(req.query.limit) || 20, 50);
    const category = req.query.category as string;

    const where: any = { is_available: true };
    if (category) where.merchant_profiles = { category };

    const items = await prisma.merchant_inventories.findMany({
      where,
      orderBy: { created_at: 'desc' as const },
      skip: (page - 1) * limit,
      take: limit,
      include: {
        merchant_profiles: {
          select: { id: true, business_name: true, category: true, verification_status: true, description: true }
        }
      }
    });

    const total = await prisma.merchant_inventories.count({ where });

    const data = items.map((item: any) => ({
      id: Number(item.id),
      name: item.name,
      description: item.description,
      price_usd: Number(item.price_usd),
      stock_count: Number(item.stock_count),
      image_url: item.image_url,
      merchant_name: item.merchant_profiles?.business_name || 'Unknown',
      merchant_category: item.merchant_profiles?.category || 'general',
      merchant_verified: item.merchant_profiles?.verification_status === 'approved',
    }));

    res.json({ items: data, total, page, limit, radius_m: radiusM });
  } catch (err: any) {
    console.error('[marketplace] nearby error:', err.message);
    res.json({ items: [], total: 0, page: 1, limit: 20, radius_m: 5000 });
  }
});

// GET /api/marketplace/items
router.get('/items', authenticate, async (req: any, res) => {
  try {
    const items = await prisma.merchant_inventories.findMany({
      where: { is_available: true },
      orderBy: { created_at: 'desc' as const },
      take: 20,
      include: {
        merchant_profiles: { select: { id: true, business_name: true, category: true } }
      }
    });
    res.json({ items: items.map((i: any) => ({
      id: Number(i.id), name: i.name, description: i.description,
      price_usd: Number(i.price_usd), stock_count: Number(i.stock_count),
      merchant_name: i.merchant_profiles?.business_name || 'Unknown'
    }))});
  } catch (err: any) {
    res.json({ items: [] });
  }
});

// POST /api/marketplace/items
router.post('/items', authenticate, async (req: any, res) => {
  res.json({ message: 'Item creation requires merchant approval' });
});

// GET /api/marketplace/categories
router.get('/categories', authenticate, async (_req: any, res) => {
  try {
    const cats = await prisma.merchant_profiles.findMany({
      where: { verification_status: 'approved' },
      select: { category: true },
      distinct: ['category']
    });
    res.json({ categories: cats.map((c: any) => c.category).filter(Boolean) });
  } catch (_e) {
    res.json({ categories: ['food', 'retail', 'entertainment', 'services'] });
  }
});

export default router;
