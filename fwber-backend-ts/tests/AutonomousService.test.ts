import { jest } from '@jest/globals';

const mockCreate = jest.fn();
const mockFindUnique = jest.fn();
const mockPrisma = {
  autonomous_actions: { create: mockCreate },
  autonomous_settings: { findUnique: mockFindUnique }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
}));

const { AutonomousService } = await import('../src/services/AutonomousService.js');

describe('AutonomousService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('logAction', () => {
    it('should call prisma create with correct data', async () => {
      await AutonomousService.logAction('Test Task', 'Completed', { foo: 'bar' });
      expect(mockCreate).toHaveBeenCalledWith({
        data: {
          task: 'Test Task',
          status: 'Completed',
          metadata: { foo: 'bar' }
        }
      });
    });
  });

  describe('isAdjustmentEnabled', () => {
    it('should return true if setting is not found', async () => {
      mockFindUnique.mockResolvedValueOnce(null);
      const enabled = await AutonomousService.isAdjustmentEnabled('test-key');
      expect(enabled).toBe(true);
    });

    it('should return true if setting value is "true"', async () => {
      mockFindUnique.mockResolvedValueOnce({ value: 'true' });
      const enabled = await AutonomousService.isAdjustmentEnabled('test-key');
      expect(enabled).toBe(true);
    });

    it('should return false if setting value is "false"', async () => {
      mockFindUnique.mockResolvedValueOnce({ value: 'false' });
      const enabled = await AutonomousService.isAdjustmentEnabled('test-key');
      expect(enabled).toBe(false);
    });
  });
});
