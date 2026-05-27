import { jest } from '@jest/globals';

const mockFindMany = jest.fn();
const mockPrisma = {
  autonomous_actions: { findMany: mockFindMany },
  autonomous_settings: { findMany: jest.fn().mockResolvedValue([]) }
};

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: mockPrisma,
  serialize: (val: any) => val,
  sanitizeUser: (user: any) => user
}));

const { ProtocolVerificationService } = await import('../src/services/ProtocolVerificationService.js');

describe('ProtocolVerificationService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should detect dangling tasks', async () => {
    const startTime = new Date(Date.now() - 15 * 60 * 1000); // 15 mins ago
    mockFindMany.mockResolvedValueOnce([
      { task: 'Dangling Task', status: 'Started', created_at: startTime }
    ]);

    const result = await ProtocolVerificationService.verifyProtocol();

    expect(result.summary.danglingTasks).toBe(1);
    expect(result.anomalies[0].type).toBe('Dangling Tasks');
  });

  it('should detect settings flapping', async () => {
    mockFindMany.mockResolvedValueOnce([
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: true }, created_at: new Date() },
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: false }, created_at: new Date() },
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: true }, created_at: new Date() },
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: false }, created_at: new Date() },
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: true }, created_at: new Date() },
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: false }, created_at: new Date() },
      { task: 'System Maintenance', status: 'Completed', metadata: { strict_mode: true }, created_at: new Date() }
    ]);

    const result = await ProtocolVerificationService.verifyProtocol();

    expect(result.summary.flappingSettings).toBe(6);
    expect(result.anomalies[0].type).toBe('Settings Flapping');
  });
});
