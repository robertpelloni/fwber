import request from 'supertest';
import { jest } from '@jest/globals';

const mockFindUnique = jest.fn();
const mockUpsert = jest.fn();
const mockFindMerchantProfile = jest.fn();

const mockPrisma = {
  merchant_profiles: { findUnique: mockFindMerchantProfile },
  autonomous_settings: {
    findUnique: mockFindUnique,
    upsert: mockUpsert
  },
  users: {
    findUnique: jest.fn(),
  },
  $queryRaw: jest.fn().mockResolvedValue([{ 1: 1 }]),
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: (obj: any) => JSON.parse(JSON.stringify(obj, (k, v) => typeof v === 'bigint' ? v.toString() : v)),
  sanitizeUser: (obj: any) => obj,
}));

// Mock the middleware to bypass real JWT verification
jest.unstable_mockModule('../src/middleware/auth.js', () => ({
  authenticate: (req: any, res: any, next: any) => {
    req.user = { id: 1 };
    next();
  },
}));

const { default: app } = await import('../src/index.js');

describe('Merchant Loyalty API', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  afterAll(async () => {
    if ((app as any).close) (app as any).close();
  });

  it('GET /api/merchant-portal/loyalty/settings should return default if not set', async () => {
    mockFindMerchantProfile.mockResolvedValue({ id: 10n });
    mockFindUnique.mockResolvedValue(null);

    const response = await request(app)
      .get('/api/merchant-portal/loyalty/settings')
      .set('Authorization', 'Bearer fake-token');

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
      .send(payload)
      .set('Authorization', 'Bearer fake-token');

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
      .get('/api/merchant-portal/loyalty/settings')
      .set('Authorization', 'Bearer fake-token');

    expect(response.status).toBe(403);
    expect(response.body.message).toBe('Not a merchant');
  });
});
