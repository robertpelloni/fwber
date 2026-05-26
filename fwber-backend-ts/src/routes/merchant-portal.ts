import { Router } from 'express';
import { authenticate } from '../middleware/auth.js';
import prisma from '../lib/prisma.js';

const router = Router();
router.use(authenticate);

// Helper: serialize BigInt
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

// Helper: get merchant profile for current user
async function getMerchantProfile(userId: bigint) {
  return prisma.merchant_profiles.findUnique({
    where: { user_id: userId },
  });
}

// POST /api/merchant-portal/register — register as merchant
router.post('/register', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { business_name, description, category, address } = req.body;

    if (!business_name || !category) {
      return res.status(400).json({ message: 'business_name and category are required' });
    }

    // Check if already registered
    const existing = await getMerchantProfile(userId);
    if (existing) {
      return res.status(400).json({ message: 'Already registered as merchant', merchant: serialize(existing) });
    }

    const profile = await prisma.merchant_profiles.create({
      data: {
        user_id: userId,
        business_name,
        description: description || null,
        category,
        address: address || null,
        verification_status: 'pending',
      },
    });

    res.json({ success: true, merchant: serialize(profile) });
  } catch (error: any) {
    console.error('[Merchant] Register error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to register' });
  }
});

// GET /api/merchant-portal/profile
router.get('/profile', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.json({ is_merchant: false });
    }

    res.json(serialize({
      ...profile,
      is_merchant: true,
    }));
  } catch (error: any) {
    console.error('[Merchant] Profile error:', error.message);
    res.json({ is_merchant: false });
  }
});

// PUT /api/merchant-portal/profile
router.put('/profile', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.status(404).json({ message: 'Merchant profile not found' });
    }

    const { business_name, description, category, address } = req.body;
    const updated = await prisma.merchant_profiles.update({
      where: { id: profile.id },
      data: {
        ...(business_name && { business_name }),
        ...(description !== undefined && { description }),
        ...(category && { category }),
        ...(address !== undefined && { address }),
      },
    });

    res.json({ success: true, profile: serialize(updated) });
  } catch (error: any) {
    console.error('[Merchant] Update error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to update profile' });
  }
});

// GET /api/merchant-portal/dashboard
router.get('/dashboard', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.json({ is_merchant: false });
    }

    const [inventoryCount, paymentCount, totalRevenue] = await Promise.all([
      prisma.merchant_inventories.count({ where: { merchant_profile_id: profile.id } }),
      prisma.merchant_payments.count({ where: { merchant_id: userId, status: 'paid' } }),
      prisma.merchant_payments.aggregate({
        where: { merchant_id: userId, status: 'paid' },
        _sum: { amount: true },
      }),
    ]);

    res.json(serialize({
      is_merchant: true,
      business_name: profile.business_name,
      verification_status: profile.verification_status,
      stats: {
        total_inventory: inventoryCount,
        total_payments: paymentCount,
        total_revenue: totalRevenue._sum.amount || 0,
      },
    }));
  } catch (error: any) {
    console.error('[Merchant] Dashboard error:', error.message);
    res.json([]);
  }
});

// GET /api/merchant-portal/promotions
router.get('/promotions', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    // Promotions linked to merchant's events
    const promos = await prisma.promotion_events.findMany({
      where: { user_id: userId },
      orderBy: { created_at: 'desc' },
      take: 20,
    });

    res.json(serialize(promos));
  } catch (error: any) {
    console.error('[Merchant] Promotions error:', error.message);
    res.json([]);
  }
});

// POST /api/merchant-portal/promotions
router.post('/promotions', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const { promotion_id, type, metadata } = req.body;

    const promo = await prisma.promotion_events.create({
      data: {
        promotion_id: promotion_id ? BigInt(promotion_id) : BigInt(0),
        user_id: userId,
        type: type || 'view',
        metadata: metadata || null,
      },
    });

    res.json({ success: true, promotion: serialize(promo) });
  } catch (error: any) {
    console.error('[Merchant] Create promo error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to create promotion' });
  }
});

// GET /api/merchant-portal/analytics
router.get('/analytics', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.json({ kpis: { kFactor: 0, totalReach: 0, conversionRate: 0, totalRevenue: 0, revenueChange: 0 }, retention: [], promotions: [], broadcasts: [] });
    }

    const [totalViews, totalClicks, totalRedemptions, totalRevenue] = await Promise.all([
      prisma.promotion_events.count({ where: { user_id: userId, type: 'view' } }),
      prisma.promotion_events.count({ where: { user_id: userId, type: 'click' } }),
      prisma.promotion_events.count({ where: { user_id: userId, type: 'redemption' } }),
      prisma.merchant_payments.aggregate({
        where: { merchant_id: userId, status: 'paid' },
        _sum: { amount: true },
      }),
    ]);

    res.json({
      kpis: {
        kFactor: totalViews > 0 ? Number((totalRedemptions / totalViews).toFixed(2)) : 0,
        totalReach: totalViews,
        conversionRate: totalViews > 0 ? Number(((totalClicks / totalViews) * 100).toFixed(1)) : 0,
        totalRevenue: Number(totalRevenue._sum.amount || 0),
        revenueChange: 0,
      },
      retention: [],
      promotions: [],
      broadcasts: [],
    });
  } catch (error: any) {
    console.error('[Merchant] Analytics error:', error.message);
    res.json({ kpis: { kFactor: 0, totalReach: 0, conversionRate: 0, totalRevenue: 0, revenueChange: 0 }, retention: [], promotions: [], broadcasts: [] });
  }
});

// GET /api/merchant-portal/inventory
router.get('/inventory', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.json({ items: [] });
    }

    const items = await prisma.merchant_inventories.findMany({
      where: { merchant_profile_id: profile.id },
      orderBy: { created_at: 'desc' },
    });

    res.json({
      items: serialize(items.map((item: any) => ({
        ...item,
        price_usd: Number(item.price_usd),
        stock_count: Number(item.stock_count),
      }))),
    });
  } catch (error: any) {
    console.error('[Merchant] Inventory error:', error.message);
    res.json({ items: [] });
  }
});

// POST /api/merchant-portal/inventory
router.post('/inventory', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.status(403).json({ message: 'Not a merchant' });
    }

    const { name, description, price_usd, stock_count, image_url } = req.body;

    if (!name || !price_usd) {
      return res.status(400).json({ message: 'name and price_usd are required' });
    }

    const item = await prisma.merchant_inventories.create({
      data: {
        merchant_profile_id: profile.id,
        name,
        description: description || null,
        price_usd,
        stock_count: stock_count || 0,
        image_url: image_url || null,
        is_available: true,
      },
    });

    res.json({ item: serialize(item) });
  } catch (error: any) {
    console.error('[Merchant] Inventory create error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to create inventory item' });
  }
});

// POST /api/merchant-portal/inventory/redeem
router.post('/inventory/redeem', async (req: any, res) => {
  try {
    const { code } = req.body;

    if (!code) {
      return res.status(400).json({ message: 'Redemption code required' });
    }

    // Look up redemption
    const redemption = await prisma.inventory_redemptions.findFirst({
      where: { id: BigInt(code) },
    });

    if (!redemption) {
      return res.status(404).json({ message: 'Invalid redemption code' });
    }

    res.json({ success: true, redeemed: serialize(redemption) });
  } catch (error: any) {
    console.error('[Merchant] Redeem error:', error.message);
    res.status(500).json({ success: false, message: 'Failed to redeem' });
  }
});

// GET /api/merchant-portal/loyalty/settings
router.get('/loyalty/settings', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.status(403).json({ message: 'Not a merchant' });
    }

    const key = `loyalty_settings_merchant_${profile.id}`;
    const settings = await prisma.autonomous_settings.findUnique({
      where: { key },
    });

    if (!settings) {
      return res.json({
        enabled: true,
        checkin_threshold: 5,
        nft_collection_name: 'Merchant Vibe Gold',
      });
    }

    res.json(JSON.parse(settings.value));
  } catch (error: any) {
    console.error('[Merchant] Loyalty settings error:', error.message);
    res.status(500).json({ message: 'Failed to fetch loyalty settings' });
  }
});

// POST /api/merchant-portal/loyalty/settings
router.post('/loyalty/settings', async (req: any, res) => {
  try {
    const userId = BigInt(req.user.id);
    const profile = await getMerchantProfile(userId);

    if (!profile) {
      return res.status(403).json({ message: 'Not a merchant' });
    }

    const key = `loyalty_settings_merchant_${profile.id}`;
    const value = JSON.stringify(req.body);

    await prisma.autonomous_settings.upsert({
      where: { key },
      update: { value },
      create: { key, value },
    });

    res.json({ success: true });
  } catch (error: any) {
    console.error('[Merchant] Loyalty settings save error:', error.message);
    res.status(500).json({ message: 'Failed to save loyalty settings' });
  }
});

export default router;
