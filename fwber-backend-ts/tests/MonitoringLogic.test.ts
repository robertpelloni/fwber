import { jest } from '@jest/globals';

const mockFindMany = jest.fn();
const mockUpsert = jest.fn();
const mockGroupBy = jest.fn();
const mockGetPerformanceSummary = jest.fn();

const mockPrisma = {
  autonomous_actions: { findMany: mockFindMany, groupBy: mockGroupBy },
  autonomous_settings: { findMany: mockFindMany, upsert: mockUpsert }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: (obj: any) => JSON.parse(JSON.stringify(obj)),
  sanitizeUser: (obj: any) => obj
}));

jest.unstable_mockModule('../src/services/AutonomousPerformanceService.js', () => ({
  AutonomousPerformanceService: {
    getPerformanceSummary: mockGetPerformanceSummary
  }
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
      mockGetPerformanceSummary.mockResolvedValueOnce({});

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
