import { jest } from '@jest/globals';

const mockExecute = jest.fn();
jest.unstable_mockModule('../src/services/AutonomousTaskExecutor.js', () => ({
  AutonomousTaskExecutor: { execute: mockExecute }
}));

const mockFindMany = jest.fn();
const mockUpsert = jest.fn();
const mockDeleteMany = jest.fn();
const mockPrisma = {
  autonomous_actions: { findMany: mockFindMany, deleteMany: mockDeleteMany },
  autonomous_settings: { upsert: mockUpsert }
};
jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
}));

const { AutonomousHealer } = await import('../src/services/AutonomousHealer.js');

describe('AutonomousHealer', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Simulate execute just running the callback
    mockExecute.mockImplementation((action, task: any) => task());
  });

  describe('healStrictMode', () => {
    it('should reset strict_mode if failure rate is low', async () => {
      mockFindMany.mockResolvedValue([
        { status: 'Completed' },
        { status: 'Completed' }
      ]);

      const result = await AutonomousHealer.healStrictMode();

      expect(result.healed).toBe(true);
      expect(mockUpsert).toHaveBeenCalledWith(expect.objectContaining({
        where: { key: 'strict_mode' },
        update: { value: 'false' }
      }));
    });

    it('should NOT reset strict_mode if failure rate is high', async () => {
      mockFindMany.mockResolvedValue([
        { status: 'Failed' },
        { status: 'Completed' }
      ]);

      const result = await AutonomousHealer.healStrictMode();

      expect(result.healed).toBe(false);
      expect(mockUpsert).not.toHaveBeenCalled();
    });
  });

  describe('cleanupLogs', () => {
    it('should call deleteMany for old logs', async () => {
      mockDeleteMany.mockResolvedValue({ count: 50 });

      const result = await AutonomousHealer.cleanupLogs();

      expect(result.deletedCount).toBe(50);
      expect(mockDeleteMany).toHaveBeenCalledWith(expect.objectContaining({
        where: { created_at: { lt: expect.any(Date) } }
      }));
    });
  });
});
