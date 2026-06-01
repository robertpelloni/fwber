import request from 'supertest';
import { jest } from '@jest/globals';
import express from 'express';

const mockFindUnique = jest.fn();
const mockUpsert = jest.fn();
const mockFindMerchantProfile = jest.fn();

const mockPrisma = {
  merchant_profiles: { findUnique: mockFindMerchantProfile },
  autonomous_settings: {
    findUnique: mockFindUnique,
    upsert: mockUpsert
  },
  merchant_inventories: {
    count: jest.fn(),
    findMany: jest.fn(),
  },
  merchant_payments: {
    count: jest.fn(),
    aggregate: jest.fn(),
  },
  promotion_events: {
    findMany: jest.fn(),
    count: jest.fn(),
  },
  inventory_redemptions: {
    findFirst: jest.fn(),
  }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: (obj: any) => obj,
  sanitizeUser: (obj: any) => obj,
}));

jest.unstable_mockModule('../src/middleware/auth.js', () => ({
  authenticate: (req: any, res: any, next: any) => {
    req.user = { id: 1 };
    next();
  },
}));

const { default: merchantPortalRoutes } = await import('../src/routes/merchant-portal.js');

const app = express();
app.use(express.json());
app.use('/api/merchant-portal', merchantPortalRoutes);

describe('Merchant Loyalty API (Isolated)', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('GET /api/merchant-portal/loyalty/settings should return default if not set', async () => {
    mockFindMerchantProfile.mockResolvedValue({ id: 10n });
    mockFindUnique.mockResolvedValue(null);

    const response = await request(app)
      .get('/api/merchant-portal/loyalty/settings');

    expect(response.status).toBe(200);
    expect(response.body).toEqual({
      enabled: true,
      checkin_threshold: 5,
      nft_collection_name: 'Merchant Vibe Gold',
    });
  });

  it('POST /api/merchant-portal/loyalty/settings should upsert settings', async () => {
    mockFindMerchantProfile.mockResolvedValue({ id: 10n });
    const payload = { enabled: false, checkin_threshold: 10 };

    const response = await request(app)
      .post('/api/merchant-portal/loyalty/settings')
      .send(payload);

    expect(response.status).toBe(200);
    expect(response.body).toEqual({ success: true });
    expect(mockUpsert).toHaveBeenCalledWith(expect.objectContaining({
      where: { key: 'loyalty_settings_merchant_10' },
      update: { value: JSON.stringify(payload) }
    }));
  });

  it('GET /api/merchant-portal/loyalty/settings should return 403 if not a merchant', async () => {
    mockFindMerchantProfile.mockResolvedValue(null);

    const response = await request(app)
      .get('/api/merchant-portal/loyalty/settings');

    expect(response.status).toBe(403);
    expect(response.body.message).toBe('Not a merchant');
  });
});
