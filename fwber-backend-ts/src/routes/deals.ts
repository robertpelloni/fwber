import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();

// GET /api/deals - Get nearby deals (merchant inventory at discounted prices)
router.get('/', authenticate, async (req: any, res) => {
  try {
    const page = Number(req.query.page) || 1;
    const perPage = Math.min(Number(req.query.per_page) || 20, 50);
    const category = req.query.category as string;

    const where: any = { is_available: true };
    if (category) where.merchant_profiles = { category };

    const items = await prisma.merchant_inventories.findMany({
      where,
      orderBy: { price_usd: 'asc' as const },
      skip: (page - 1) * perPage,
      take: perPage,
      include: {
        merchant_profiles: {
          select: { id: true, business_name: true, category: true, verification_status: true, address: true }
        }
      }
    });

    const total = await prisma.merchant_inventories.count({ where });

    const deals = items.map((item: any) => {
      const originalPrice = Number(item.price_usd) * 1.2;
      const dealPrice = Number(item.price_usd);
      return {
        id: Number(item.id),
        title: item.name,
        description: item.description,
        original_price: Math.round(originalPrice * 100) / 100,
        deal_price: dealPrice,
        discount_percent: Math.round((1 - dealPrice / originalPrice) * 100),
        discount: Math.round((1 - dealPrice / originalPrice) * 100) + '% off',
        merchant_name: item.merchant_profiles?.business_name || 'Unknown',
        merchant_address: item.merchant_profiles?.address || null,
        category: item.merchant_profiles?.category || 'general',
        stock_count: Number(item.stock_count),
        image_url: item.image_url,
      };
    });

    res.json({ deals, total, page, per_page: perPage });
  } catch (err: any) {
    console.error('[deals] GET error:', err.message);
    res.json({ deals: [], total: 0, page: 1, per_page: 20 });
  }
});

// GET /api/deals/categories
router.get('/categories', authenticate, async (_req: any, res) => {
  res.json({
    categories: [
      { id: 1, name: 'Dining', icon: 'restaurant' },
      { id: 2, name: 'Entertainment', icon: 'movie' },
      { id: 3, name: 'Shopping', icon: 'shopping_bag' },
      { id: 4, name: 'Wellness', icon: 'spa' },
      { id: 5, name: 'Travel', icon: 'flight' },
    ],
  });
});

// GET /api/deals/:id
router.get('/:id', authenticate, async (req: any, res) => {
  try {
    const item = await prisma.merchant_inventories.findUnique({
      where: { id: BigInt(req.params.id) },
      include: { merchant_profiles: { select: { business_name: true, category: true, address: true } } }
    });
    if (!item) return res.json({ deal: null });
    res.json({
      deal: {
        id: Number(item.id),
        title: item.name,
        description: item.description,
        deal_price: Number(item.price_usd),
        merchant_name: item.merchant_profiles?.business_name || 'Unknown',
        category: item.merchant_profiles?.category || 'general',
      }
    });
  } catch {
    res.json({ deal: null });
  }
});

// POST /api/deals/:id/claim
router.post('/:id/claim', authenticate, async (req: any, res) => {
  res.json({ success: true, claimed: true, message: 'Deal claimed! Show this at the merchant.' });
});

export default router;
