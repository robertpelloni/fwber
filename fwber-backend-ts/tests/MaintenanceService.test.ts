import { jest } from '@jest/globals';

// Mock Prisma
const mockPrisma = {
  autonomous_actions: {
    findMany: jest.fn(),
    create: jest.fn()
  },
  autonomous_settings: {
    upsert: jest.fn(),
    findUnique: jest.fn(),
    update: jest.fn()
  }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: (val: any) => val,
  sanitizeUser: (user: any) => user
}));

const { MaintenanceService } = await import('../src/services/MaintenanceService.js');

describe('MaintenanceService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should enable strict_mode if failure rate > 20%', async () => {
    const mockActions = [
      { status: 'Failed' },
      { status: 'Failed' },
      { status: 'Completed' },
      { status: 'Completed' },
      { status: 'Completed' }
    ]; // 40% failure

    (mockPrisma.autonomous_actions.findMany as any).mockResolvedValue(mockActions);

    await MaintenanceService.performMaintenance();

    expect(mockPrisma.autonomous_settings.upsert).toHaveBeenCalledWith(
      expect.objectContaining({
        where: { key: 'strict_mode' },
        update: { value: 'true' }
      })
    );
  });

  it('should disable strict_mode if failure rate < 5% and it was enabled', async () => {
    const mockActions = [
      { status: 'Completed' },
      { status: 'Completed' },
      { status: 'Completed' },
      { status: 'Completed' },
      { status: 'Completed' }
    ]; // 0% failure

    (mockPrisma.autonomous_actions.findMany as any).mockResolvedValue(mockActions);
    (mockPrisma.autonomous_settings.findUnique as any).mockResolvedValue({ key: 'strict_mode', value: 'true' });

    await MaintenanceService.performMaintenance();

    expect(mockPrisma.autonomous_settings.update).toHaveBeenCalledWith(
      expect.objectContaining({
        where: { key: 'strict_mode' },
        data: { value: 'false' }
      })
    );
  });
});
