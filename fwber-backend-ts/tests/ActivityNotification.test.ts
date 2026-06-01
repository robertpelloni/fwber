import { jest } from '@jest/globals';

const mockCreate = jest.fn();
const mockPush = jest.fn();
const mockLog = jest.fn();

jest.unstable_mockModule('../src/lib/prisma.js', () => ({
  default: {
    notifications: { create: mockCreate }
  },
  serialize: (val: any) => val,
  sanitizeUser: (user: any) => user
}));

jest.unstable_mockModule('../src/socket.js', () => ({
  pushNotification: mockPush
}));

jest.unstable_mockModule('../src/services/AutonomousService.js', () => ({
  AutonomousService: { logAction: mockLog }
}));

const { ActivityNotificationService } = await import('../src/services/ActivityNotificationService.js');

describe('ActivityNotificationService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should create a notification and push via socket', async () => {
    const userId = 1n;
    mockCreate.mockResolvedValue({ id: 123n, created_at: new Date() });

    await ActivityNotificationService.notify(userId, 'Test Title', 'Test Body', { type: 'test' });

    expect(mockCreate).toHaveBeenCalled();
    expect(mockPush).toHaveBeenCalled();
    expect(mockLog).toHaveBeenCalled();
  });
});
