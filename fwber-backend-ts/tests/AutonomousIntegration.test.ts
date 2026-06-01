import { jest } from '@jest/globals';

// 1. Setup Mocks
const mockActions: any[] = [];
const mockSettings: Record<string, string> = {};

const mockPrisma = {
  autonomous_actions: {
    create: jest.fn().mockImplementation(({ data }: any) => {
      const action = { ...data, id: BigInt(mockActions.length + 1), created_at: new Date() };
      mockActions.push(action);
      return Promise.resolve(action);
    }),
    findMany: jest.fn().mockImplementation(() => Promise.resolve(mockActions)),
    groupBy: jest.fn().mockImplementation(() => Promise.resolve([])),
    deleteMany: jest.fn().mockResolvedValue({ count: 0 })
  },
  autonomous_settings: {
    findUnique: jest.fn().mockImplementation(({ where }: any) =>
      Promise.resolve(mockSettings[where.key] ? { key: where.key, value: mockSettings[where.key] } : null)
    ),
    upsert: jest.fn().mockImplementation(({ where, update, create }: any) => {
      mockSettings[where.key] = update.value;
      return Promise.resolve({ key: where.key, value: update.value });
    }),
    update: jest.fn().mockImplementation(({ where, data }: any) => {
      mockSettings[where.key] = data.value;
      return Promise.resolve({ key: where.key, value: data.value });
    }),
    findMany: jest.fn().mockImplementation(() => Promise.resolve(
        Object.entries(mockSettings).map(([key, value]) => ({ key, value }))
    ))
  }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: (obj: any) => obj,
  sanitizeUser: (obj: any) => obj,
}));

// Mock ProtocolVerificationService to avoid actual database calls in this layer
jest.unstable_mockModule('../src/services/ProtocolVerificationService.js', () => ({
  ProtocolVerificationService: {
    verifyProtocol: jest.fn().mockResolvedValue({ score: 100, passed: true })
  }
}));

// 2. Import Services
const { AutonomousTaskExecutor } = await import('../src/services/AutonomousTaskExecutor.js');
const { MaintenanceService } = await import('../src/services/MaintenanceService.js');
const { AutonomousHealer } = await import('../src/services/AutonomousHealer.js');

describe('Autonomous Execution Protocol Integration', () => {
  beforeEach(() => {
    mockActions.length = 0;
    Object.keys(mockSettings).forEach(k => delete mockSettings[k]);
    jest.clearAllMocks();
  });

  it('should execute a task, log it, and trigger self-correction on failure', async () => {
    // 1. Execute a failing task
    const failingTask = async () => { throw new Error('Simulated Failure'); };

    await expect(AutonomousTaskExecutor.execute(
      { type: 'Critical Update', impact: 'low', module: 'Auth' },
      failingTask
    )).rejects.toThrow('Simulated Failure');

    expect(mockActions).toHaveLength(2); // Started, Failed
    expect(mockActions[1].status).toBe('Failed');

    // 2. Run maintenance - should detect failure and enable strict mode
    // Note: MaintenanceService uses real 'Date' logic, so we might need to mock Date if findMany filters.
    // In our mock findMany, it returns everything.

    await MaintenanceService.performMaintenance();

    // failureRate = 1/2 * 100 = 50% (> 20%)
    expect(mockSettings['strict_mode']).toBe('true');

    // 3. System heals: Execute many successful tasks to drop failure rate
    for (let i = 0; i < 20; i++) {
        await AutonomousTaskExecutor.execute(
            { type: 'Routine Task', impact: 'low', module: 'Auth' },
            async () => 'ok'
        );
    }

    // failureRate is now 1 / 23 (approx 4%)
    await MaintenanceService.performMaintenance();
    expect(mockSettings['strict_mode']).toBe('false');
  });

  it('should allow AutonomousHealer to reset strict_mode', async () => {
    mockSettings['strict_mode'] = 'true';

    // Low failure environment
    for (let i = 0; i < 10; i++) {
        await AutonomousTaskExecutor.execute(
            { type: 'Healthy Task', impact: 'low', module: 'Auth' },
            async () => 'ok'
        );
    }

    const result = await AutonomousHealer.healStrictMode();
    expect(result.healed).toBe(true);
    expect(mockSettings['strict_mode']).toBe('false');
  });
});
