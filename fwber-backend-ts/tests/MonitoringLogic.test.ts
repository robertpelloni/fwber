import { jest } from '@jest/globals';

const mockFindMany = jest.fn();
const mockUpsert = jest.fn();
const mockGroupBy = jest.fn();

const mockPrisma = {
  autonomous_actions: { findMany: mockFindMany, groupBy: mockGroupBy },
  autonomous_settings: { findMany: mockFindMany, upsert: mockUpsert }
};

// Mock the serialization logic since we are bypassing the real lib/prisma.js in this test
const mockSerialize = (obj: any): any => {
  if (obj === null || obj === undefined) return obj;
  if (typeof obj === 'bigint') return Number(obj);
  if (Array.isArray(obj)) return obj.map(mockSerialize);
  if (typeof obj === 'object') {
    const out: any = {};
    for (const key of Object.keys(obj)) {
      out[key] = mockSerialize(obj[key]);
    }
    return out;
  }
  return obj;
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: mockSerialize,
  sanitizeUser: (obj: any) => obj
}));

const { getAutonomousStatus, updateAdjustment } = await import('../src/routes/monitoring.js');

describe('Monitoring Route Handlers', () => {
  let req: any;
  let res: any;

  beforeEach(() => {
    jest.clearAllMocks();
    req = {
      user: { id: 1, is_moderator: true },
      body: {},
      query: {},
    };
    res = {
      status: jest.fn().mockReturnThis(),
      json: jest.fn().mockReturnThis(),
    };
  });

  describe('getAutonomousStatus', () => {
    it('should return 403 if user is not a moderator', async () => {
      req.user.is_moderator = false;
      await getAutonomousStatus(req, res);
      expect(res.status).toHaveBeenCalledWith(403);
    });

    it('should return protocol status for moderators and reflect DB settings', async () => {
      mockFindMany.mockResolvedValueOnce([]); // recentActions
      mockFindMany.mockResolvedValueOnce([
        { key: 'auto_lint_fix', value: 'false' }
      ]); // settings
      mockGroupBy.mockResolvedValueOnce([]); // dailyStats

      await getAutonomousStatus(req, res);
      expect(res.json).toHaveBeenCalled();
      const payload = res.json.mock.calls[0][0];

      const adjustments = payload.automated_adjustments;
      let found = false;
      adjustments.forEach((item: any) => {
        if (item.key === 'auto_lint_fix') {
          expect(item.enabled).toBe(false);
          found = true;
        }
      });
      expect(found).toBe(true);
    });
  });

  describe('updateAdjustment', () => {
    it('should update and return success', async () => {
      req.body = { key: 'test', enabled: true };
      await updateAdjustment(req, res);
      expect(mockUpsert).toHaveBeenCalled();
      expect(res.json).toHaveBeenCalledWith({ success: true, key: 'test', enabled: true });
    });
  });
});
